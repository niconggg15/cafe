<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function baru(): View
    {
        return view('kasir.pesanan-baru', [
            'menus' => Menu::with('resep')->orderBy('nama_menu')->get(),
            'kategoriMenus' => DB::table('kategori_menu')->orderBy('nama_kategori')->pluck('nama_kategori'),
        ]);
    }

    public function simpan(Request $request, PesananService $pesananService): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'min:1'],
            'no_meja' => ['required', 'integer', 'between:1,10'],
            'metode_bayar' => ['required', 'in:Tunai,QRIS'],
        ]);

        $kekuranganStok = $pesananService->kekuranganStok($data['items']);
        if ($kekuranganStok !== []) {
            return back()
                ->withInput()
                ->with('order_stock_shortages', $kekuranganStok);
        }

        $pesanan = $pesananService->buat($data, (int) $request->user()->id);

        return redirect()->route('kasir.struk', $pesanan)->with('success', 'Pesanan berhasil dibuat.');
    }

    public function index(): View
    {
        return view('kasir.daftar-pesanan', [
            'pesanans' => Pesanan::with('detail')->latest()->paginate(15),
        ]);
    }

    public function status(Request $request, Pesanan $pesanan, PesananService $pesananService): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:Diproses,Selesai']]);
        $pesananService->ubahStatus($pesanan, $data['status']);

        return back()->with('success', 'Status pesanan diperbarui.');
    }

    public function batalkan(Pesanan $pesanan, PesananService $pesananService): RedirectResponse
    {
        $pesananService->batalkan($pesanan);

        return back()->with('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }

    public function struk(Pesanan $pesanan): View
    {
        return view('kasir.struk', [
            'pesanan' => $pesanan->load('detail', 'kasir'),
        ]);
    }
}
