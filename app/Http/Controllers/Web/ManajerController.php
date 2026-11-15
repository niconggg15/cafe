<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\DetailPesanan;
use App\Models\Menu;
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
    public function bahan(): View
    {
        return view('manajer.bahan', ['bahans' => Bahan::orderBy('nama_bahan')->paginate(20)]);
    }

    public function simpanBahan(Request $request, MenuStatusService $menuStatus, ?Bahan $bahan = null): RedirectResponse
    {
        $data = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'in:g,ml,pcs'],
            'batas_minimum' => ['required', 'numeric', 'min:0'],
            'harga_per_satuan' => ['required', 'numeric', 'min:0'],
        ]);
        $data['nama_bahan'] = trim($data['nama_bahan']);
        if (! $bahan || (float) $bahan->harga_per_satuan !== (float) $data['harga_per_satuan']) {
            $data['harga_diperbarui_at'] = now();
        }

        if ($bahan && Bahan::where('nama_bahan', $data['nama_bahan'])->where('id', '<>', $bahan->id)->exists()) {
            return back()->withErrors(['nama_bahan' => 'Nama bahan sudah digunakan.'])->withInput();
        }
        if (! $bahan && Bahan::where('nama_bahan', $data['nama_bahan'])->exists()) {
            return back()->withErrors(['nama_bahan' => 'Nama bahan sudah digunakan.'])->withInput();
        }

        if ($bahan) {
            $bahan->update($data);
        } else {
            $bahan = Bahan::create($data + ['stok' => 0]);
        }

        $menuStatus->segarkan($bahan->resep()->pluck('menu_id')->all());

        return back()->with('success', 'Data bahan berhasil disimpan.');
    }

    public function menu(): View
    {
        return view('manajer.menu', ['menus' => Menu::withCount('resep')->orderBy('nama_menu')->paginate(20)]);
    }

    public function simpanMenu(Request $request, ?Menu $menu = null): RedirectResponse
    {
        $data = $request->validate([
            'nama_menu' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Kopi,Non-kopi,Makanan'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Aktif,Habis,Nonaktif'],
        ]);
        $data['nama_menu'] = trim($data['nama_menu']);

        if ($menu) {
            $menu->update($data);
        } else {
            $menu = Menu::create($data);
        }

        return back()->with('success', 'Data menu berhasil disimpan.');
    }

    public function resep(Request $request): View
    {
        $menus = Menu::with('resep.bahan')->orderBy('nama_menu')->get();
        $selectedMenu = $menus->firstWhere('id', (int) $request->query('menu_id')) ?? $menus->first();

        return view('manajer.resep', [
            'menus' => $menus,
            'bahans' => Bahan::orderBy('nama_bahan')->get(),
            'selectedMenu' => $selectedMenu,
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
        if (Bahan::whereIn('id', $resep->pluck('bahan_id'))->count() !== $resep->count()) {
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

        return back()->with('success', 'Resep berhasil disimpan.');
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
            'menipis' => Bahan::whereColumn('stok', '<=', 'batas_minimum')->orderBy('nama_bahan')->get(),
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
