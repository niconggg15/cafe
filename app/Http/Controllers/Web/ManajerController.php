<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\Resep;
use App\Models\StokMasuk;
use App\Services\HppService;
use App\Services\MenuStatusService;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManajerController extends Controller
{
    public function dashboard(): View
    {
        $pesananHariIni = Pesanan::whereDate('created_at', today())->where('status', '<>', 'Dibatalkan');
        $bahanMenipis = Bahan::whereColumn('stok', '<=', 'batas_minimum')->orderBy('nama_bahan')->get();
        $awalTren = today()->subDays(6);
        $tanggalTren = collect(range(0, 6))->map(fn ($hari) => $awalTren->copy()->addDays($hari));
        $penjualanMenuHarian = DetailPesanan::query()
            ->select('detail_pesanan.nama_menu')
            ->selectRaw('DATE(pesanan.created_at) as tanggal')
            ->selectRaw('SUM(detail_pesanan.jumlah) as jumlah_terjual')
            ->join('pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->where('pesanan.status', '<>', 'Dibatalkan')
            ->whereBetween('pesanan.created_at', [$awalTren->copy()->startOfDay(), today()->endOfDay()])
            ->groupBy('detail_pesanan.nama_menu', DB::raw('DATE(pesanan.created_at)'))
            ->get()
            ->groupBy('nama_menu');

        $trenMenu = $penjualanMenuHarian
            ->map(fn ($rows) => (int) $rows->sum('jumlah_terjual'))
            ->sortDesc()
            ->take(5)
            ->keys()
            ->map(function ($namaMenu) use ($penjualanMenuHarian, $tanggalTren) {
                $rowsByDate = $penjualanMenuHarian->get($namaMenu)->keyBy('tanggal');
                $values = $tanggalTren->map(function ($tanggal) use ($rowsByDate) {
                    $row = $rowsByDate->get($tanggal->toDateString());

                    return $row ? (int) $row->jumlah_terjual : 0;
                });

                return ['nama_menu' => $namaMenu, 'values' => $values];
            })
            ->values();
        $maxPenjualanHarian = max(1, (int) ($trenMenu->flatMap(fn ($series) => $series['values'])->max() ?? 0));
        $trenMenu = $trenMenu->map(function ($series) use ($maxPenjualanHarian) {
            $series['points'] = $series['values']
                ->map(fn ($jumlah, $index) => (50 + $index * 90).','.round(170 - ($jumlah / $maxPenjualanHarian * 140), 1))
                ->implode(' ');

            return $series;
        });

        return view('manajer.dashboard', [
            'menuAktif' => Menu::where('status', 'Aktif')->count(),
            'bahanMenipis' => $bahanMenipis,
            'jumlahPesananHariIni' => (clone $pesananHariIni)->count(),
            'penjualanHariIni' => $pesananHariIni->sum('total_bayar'),
            'tanggalTren' => $tanggalTren,
            'trenMenu' => $trenMenu,
        ]);
    }

    public function bahan(Request $request): View
    {
        $editBahan = $request->filled('edit_bahan_id')
            ? Bahan::withCount(['resep', 'stokMasuk'])->findOrFail($request->query('edit_bahan_id'))
            : null;

        return view('manajer.bahan', [
            'bahans' => Bahan::withCount(['resep', 'stokMasuk'])->orderBy('nama_bahan')->paginate(20),
            'editBahan' => $editBahan,
        ]);
    }

    public function simpanBahan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'in:g,ml,pcs'],
            'batas_minimum' => ['required', 'numeric', 'min:0'],
            'harga_per_satuan' => ['required', 'numeric', 'min:0'],
        ]);
        $data['nama_bahan'] = trim($data['nama_bahan']);
        if (Bahan::where('nama_bahan', $data['nama_bahan'])->exists()) {
            return back()->withErrors(['nama_bahan' => 'Nama bahan sudah digunakan.'])->withInput();
        }

        Bahan::create($data + [
            'stok' => 0,
            'harga_diperbarui_at' => now(),
        ]);

        return back()->with('success', 'Data bahan berhasil disimpan.');
    }

    public function ubahBahan(Request $request, Bahan $bahan): RedirectResponse
    {
        $request->merge(['nama_bahan' => trim((string) $request->input('nama_bahan'))]);
        $data = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'in:g,ml,pcs'],
            'batas_minimum' => ['required', 'numeric', 'min:0'],
            'harga_per_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        if (Bahan::where('nama_bahan', $data['nama_bahan'])->where('id', '<>', $bahan->id)->exists()) {
            return back()->withErrors(['nama_bahan' => 'Nama bahan sudah digunakan.'])->withInput();
        }

        $satuanTerpakai = (float) $bahan->stok > 0
            || $bahan->resep()->exists()
            || $bahan->stokMasuk()->exists();
        if ($satuanTerpakai && $data['satuan'] !== $bahan->satuan) {
            return back()->withErrors(['satuan' => 'Satuan tidak bisa diubah karena bahan sudah memiliki stok atau riwayat pemakaian.'])->withInput();
        }

        if ((float) $data['harga_per_satuan'] !== (float) $bahan->harga_per_satuan) {
            $data['harga_diperbarui_at'] = now();
        }
        $bahan->update($data);

        return redirect()->route('manajer.bahan')->with('success', 'Data bahan berhasil diperbarui.');
    }

    public function hapusBahan(Bahan $bahan): RedirectResponse
    {
        if ((float) $bahan->stok > 0 || $bahan->resep()->exists() || $bahan->stokMasuk()->exists()) {
            return redirect()->route('manajer.bahan')
                ->with('error', 'Bahan tidak dapat dihapus karena masih memiliki stok atau riwayat.');
        }

        $bahan->delete();

        return redirect()->route('manajer.bahan')->with('success', 'Data bahan berhasil dihapus.');
    }

    public function menu(Request $request): View
    {
        $editMenu = $request->filled('edit_menu_id')
            ? Menu::findOrFail($request->query('edit_menu_id'))
            : null;

        return view('manajer.menu', [
            'menus' => Menu::withCount(['resep', 'detailPesanan'])->orderBy('nama_menu')->paginate(20),
            'kategoriMenus' => DB::table('kategori_menu')->orderBy('nama_kategori')->pluck('nama_kategori'),
            'editMenu' => $editMenu,
        ]);
    }

    public function simpanMenu(Request $request, MenuStatusService $menuStatus, ?Menu $menu = null): RedirectResponse
    {
        $menuBaru = $menu === null;
        $data = $request->validate([
            'nama_menu' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'exists:kategori_menu,nama_kategori'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
        ]);
        $data['nama_menu'] = trim($data['nama_menu']);

        if ($menu) {
            $menu->update($data);
        } else {
            $menu = Menu::create($data + ['status' => 'Habis']);
        }

        $menuStatus->segarkan([$menu->id]);

        return redirect()->route('manajer.menu')
            ->with('success', $menuBaru
                ? 'Menu berhasil dibuat. Lengkapi resepnya di halaman Kelola Resep.'
                : 'Data menu berhasil disimpan.');
    }

    public function ubahStatusMenu(Menu $menu, MenuStatusService $menuStatus): RedirectResponse
    {
        $menu->status = $menu->status === 'Nonaktif' ? 'Aktif' : 'Nonaktif';
        $menu->save();

        if ($menu->status === 'Aktif') {
            $menuStatus->segarkan([$menu->id]);
            $menu->refresh();
        }

        return back()->with('success', "Menu {$menu->nama_menu} sekarang {$menu->status}.");
    }

    public function hapusMenu(Menu $menu): RedirectResponse
    {
        if ($menu->detailPesanan()->exists()) {
            return redirect()->route('manajer.menu')
                ->with('error', "Menu {$menu->nama_menu} tidak dapat dihapus karena sudah tercatat dalam pesanan. Nonaktifkan menu untuk mempertahankan riwayat.");
        }

        DB::transaction(function () use ($menu) {
            $menu->resep()->delete();
            $menu->delete();
        });

        return redirect()->route('manajer.menu')->with('success', 'Data menu dan resepnya berhasil dihapus.');
    }

    public function resep(Request $request): View
    {
        $menus = Menu::with('resep.bahan')->orderBy('nama_menu')->get();
        $selectedMenu = $menus->firstWhere('id', (int) $request->query('menu_id')) ?? $menus->first();

        return view('manajer.resep', [
            'menus' => $menus,
            'selectedMenu' => $selectedMenu,
            'bahans' => Bahan::orderBy('nama_bahan')->get(),
        ]);
    }

    public function simpanResep(Request $request, MenuStatusService $menuStatus): RedirectResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'exists:menu,id'],
            'bahan' => ['required', 'array', 'min:1'],
            'bahan.*' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $resep = collect($data['bahan'])
            ->filter(fn ($takaran) => $takaran !== null && $takaran !== '')
            ->map(fn ($takaran, $bahanId) => [
                'bahan_id' => $bahanId,
                'takaran' => $takaran,
            ]);

        if ($resep->isEmpty()) {
            return back()->withErrors(['bahan' => 'Tambahkan minimal satu bahan ke resep.'])->withInput();
        }
        $bahanIds = $resep->pluck('bahan_id')->unique();
        if (Bahan::whereIn('id', $bahanIds)->count() !== $bahanIds->count()) {
            return back()->withErrors(['bahan' => 'Salah satu bahan resep tidak ditemukan.'])->withInput();
        }
        DB::transaction(function () use ($data, $resep, $menuStatus) {
            Resep::where('menu_id', $data['menu_id'])->delete();
            foreach ($resep as $item) {
                Resep::create([
                    'menu_id' => $data['menu_id'],
                    'bahan_id' => $item['bahan_id'],
                    'takaran' => $item['takaran'],
                ]);
            }
            $menuStatus->segarkan([(int) $data['menu_id']]);
        });

        $menu = Menu::findOrFail($data['menu_id']);
        $pesan = $menu->status === 'Habis'
            ? 'Resep berhasil disimpan, tetapi status menu tetap Habis karena stok bahan belum mencukupi.'
            : 'Resep berhasil disimpan.';

        return redirect()->route('manajer.resep', ['menu_id' => $data['menu_id']])
            ->with('success', $pesan);
    }

    public function hapusResep(Menu $menu): RedirectResponse
    {
        if (! $menu->resep()->exists()) {
            return redirect()->route('manajer.resep', ['menu_id' => $menu->id])
                ->with('error', 'Menu ini belum memiliki resep.');
        }

        DB::transaction(function () use ($menu) {
            $menu->resep()->delete();
            if ($menu->status !== 'Nonaktif') {
                $menu->update(['status' => 'Habis']);
            }
        });

        return redirect()->route('manajer.resep', ['menu_id' => $menu->id])
            ->with('success', 'Resep berhasil dihapus. Status menu menjadi Habis sampai resep dibuat kembali.');
    }

    public function stokMasuk(): View
    {
        return view('manajer.stok-masuk', [
            'bahans' => Bahan::orderBy('nama_bahan')->get(),
            'riwayat' => StokMasuk::with('bahan', 'user')->latest('tanggal')->latest()->paginate(20),
        ]);
    }

    public function simpanStokMasuk(Request $request, MenuStatusService $menuStatus): RedirectResponse
    {
        $data = $request->validate([
            'bahan_id' => ['required', 'exists:bahan,id'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($data, $request, $menuStatus) {
            $bahan = Bahan::whereKey($data['bahan_id'])->lockForUpdate()->firstOrFail();
            $bahan->stok = round((float) $bahan->stok + (float) $data['jumlah'], 3);
            $bahan->save();
            StokMasuk::create($data + ['user_id' => $request->user()->id]);
            $menuStatus->segarkan($bahan->resep()->pluck('menu_id')->all());
        });

        return back()->with('success', 'Stok masuk berhasil dicatat.');
    }

    public function batasMinimum(): View
    {
        return view('manajer.batas-minimum', [
            'bahans' => Bahan::orderBy('nama_bahan')->get(),
        ]);
    }

    public function simpanBatasMinimum(Request $request, Bahan $bahan): RedirectResponse
    {
        $data = $request->validate(['batas_minimum' => ['required', 'numeric', 'min:0']]);
        $bahan->update($data);

        return back()->with('success', 'Batas minimum diperbarui.');
    }

    public function hppMargin(HppService $hppService): View
    {
        $menus = Menu::with('resep.bahan')->orderBy('nama_menu')->get();
        foreach ($menus as $menu) {
            $menu->hpp = $hppService->untuk($menu);
            $menu->margin = (float) $menu->harga_jual - $menu->hpp;
            $menu->margin_percent = (float) $menu->harga_jual > 0
                ? ($menu->margin / (float) $menu->harga_jual) * 100
                : 0;
        }

        $hargaTerbaru = Bahan::whereNotNull('harga_diperbarui_at')->max('harga_diperbarui_at');

        return view('manajer.hpp-margin', compact('menus', 'hargaTerbaru'));
    }

    public function simpanHarga(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate(['harga_jual' => ['required', 'numeric', 'min:0']]);
        $menu->harga_jual = $data['harga_jual'];
        $menu->save();

        return back()->with('success', 'Harga jual berhasil diperbarui.');
    }

    public function sinkronSupplier(SupplierService $supplierService): RedirectResponse
    {
        $result = $supplierService->sinkron();

        return back()->with($result['ok'] ? 'success' : 'error', $result['pesan']);
    }

    public function laporan(Request $request, HppService $hppService): View
    {
        $filter = $request->validate([
            'tab' => ['nullable', 'in:penjualan,bahan-menipis,margin'],
            'mulai' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:mulai'],
        ]);
        $tab = $filter['tab'] ?? 'penjualan';
        $penjualan = DetailPesanan::query()
            ->select('detail_pesanan.nama_menu')
            ->selectRaw('SUM(detail_pesanan.jumlah) as jumlah_terjual')
            ->selectRaw('SUM(detail_pesanan.jumlah * detail_pesanan.harga_saat_transaksi) as total_penjualan')
            ->join('pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->where('pesanan.status', '<>', 'Dibatalkan')
            ->when($filter['mulai'] ?? null, fn ($query, $mulai) => $query->whereDate('pesanan.created_at', '>=', $mulai))
            ->when($filter['sampai'] ?? null, fn ($query, $sampai) => $query->whereDate('pesanan.created_at', '<=', $sampai))
            ->groupBy('detail_pesanan.nama_menu')
            ->orderByDesc('total_penjualan')
            ->get();
        $menus = Menu::with('resep.bahan')->get();
        foreach ($menus as $menu) {
            $menu->hpp = $hppService->untuk($menu);
            $menu->margin = (float) $menu->harga_jual - $menu->hpp;
        }

        return view('manajer.laporan', [
            'mulai' => $filter['mulai'] ?? '',
            'sampai' => $filter['sampai'] ?? '',
            'tab' => $tab,
            'penjualan' => $penjualan,
            'menipis' => Bahan::whereColumn('stok', '<=', 'batas_minimum')->orderBy('nama_bahan')->get(),
            'menus' => $menus,
        ]);
    }
}
