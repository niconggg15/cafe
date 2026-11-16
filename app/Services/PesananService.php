<?php

namespace App\Services;

use App\Models\DetailPesanan;
use App\Models\Bahan;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PesananService
{
    public function __construct(private MenuStatusService $menuStatus)
    {
    }

    public function buat(array $data, int $kasirId): Pesanan
    {
        return DB::transaction(function () use ($data, $kasirId) {
            $menuIds = array_keys($data['items']);
            $menus = Menu::with('resep.bahan')
                ->whereIn('id', $menuIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $bahanIds = $menus->flatMap(fn ($menu) => $menu->resep->pluck('bahan_id'))->unique();
            $bahans = Bahan::whereIn('id', $bahanIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $perubahanStok = [];
            $items = [];

            foreach ($data['items'] as $menuId => $jumlah) {
                $menu = $menus->get($menuId);

                if (! $menu || $menu->status !== 'Aktif' || $menu->resep->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'Menu yang dipilih sudah tidak tersedia. Perbarui daftar menu lalu coba lagi.',
                    ]);
                }

                foreach ($menu->resep as $resep) {
                    $bahan = $bahans->get($resep->bahan_id);
                    if (! $bahan) {
                        throw ValidationException::withMessages([
                            'items' => 'Resep menu tidak lengkap. Hubungi Manajer untuk memperbaikinya.',
                        ]);
                    }
                    $dibutuhkan = (float) $resep->takaran * $jumlah;
                    $tersedia = (float) $bahan->stok;

                    if ($tersedia < $dibutuhkan) {
                        throw ValidationException::withMessages([
                            'items' => $this->pesanStokKurang($bahan, $dibutuhkan),
                        ]);
                    }

                    $perubahanStok[$bahan->id] = ($perubahanStok[$bahan->id] ?? 0) + $dibutuhkan;
                }

                $subtotal += (float) $menu->harga_jual * $jumlah;
                $items[] = [
                    'menu' => $menu,
                    'jumlah' => $jumlah,
                    'harga' => $menu->harga_jual,
                    'resep' => $menu->resep->map(fn ($resep) => [
                        'bahan_id' => $resep->bahan_id,
                        'takaran' => $resep->takaran,
                    ])->values()->all(),
                ];
            }

            foreach ($perubahanStok as $bahanId => $jumlah) {
                $jumlah = round($jumlah, 3);
                $bahan = $bahans->get($bahanId);
                if (! $bahan || (float) $bahan->stok < $jumlah) {
                    throw ValidationException::withMessages([
                        'items' => $bahan
                            ? $this->pesanStokKurang($bahan, $jumlah)
                            : 'Resep menu tidak lengkap. Hubungi Manajer untuk memperbaikinya.',
                    ]);
                }
                $bahan->stok = round((float) $bahan->stok - $jumlah, 3);
                $bahan->save();
            }

            $service = round($subtotal * 0.05);
            $pajak = round(($subtotal + $service) * 0.10);
            $pesanan = Pesanan::create([
                'kasir_id' => $kasirId,
                'no_meja' => $data['no_meja'],
                'metode_bayar' => $data['metode_bayar'],
                'status' => 'Baru',
                'subtotal' => $subtotal,
                'service_charge' => $service,
                'pajak' => $pajak,
                'total_bayar' => $subtotal + $service + $pajak,
            ]);

            foreach ($items as $item) {
                DetailPesanan::create([
                    'pesanan_id' => $pesanan->id,
                    'menu_id' => $item['menu']->id,
                    'jumlah' => $item['jumlah'],
                    'harga_saat_transaksi' => $item['harga'],
                    'nama_menu' => $item['menu']->nama_menu,
                    'resep_saat_transaksi' => $item['resep'],
                ]);
            }

            $this->menuStatus->segarkan($menuIds);

            return $pesanan;
        });
    }

    public function ubahStatus(Pesanan $pesanan, string $status): void
    {
        DB::transaction(function () use ($pesanan, $status) {
            $terkunci = Pesanan::lockForUpdate()->findOrFail($pesanan->id);
            $statusBerikutnya = [
                'Baru' => 'Diproses',
                'Diproses' => 'Selesai',
            ];

            if (($statusBerikutnya[$terkunci->status] ?? null) !== $status) {
                throw ValidationException::withMessages(['status' => 'Perubahan status pesanan tidak valid.']);
            }

            $terkunci->status = $status;
            $terkunci->save();
        });
    }

    public function batalkan(Pesanan $pesanan): void
    {
        DB::transaction(function () use ($pesanan) {
            $pesanan = Pesanan::with('detail.menu.resep')->lockForUpdate()->findOrFail($pesanan->id);

            if ($pesanan->status !== 'Baru') {
                throw ValidationException::withMessages(['status' => 'Hanya pesanan berstatus Baru yang dapat dibatalkan.']);
            }

            $menuIds = $pesanan->detail->pluck('menu_id')->all();
            $perubahanStok = [];
            foreach ($pesanan->detail as $detail) {
                foreach ($detail->menu->resep as $resep) {
                    $bahanId = $resep->bahan_id;
                    $perubahanStok[$bahanId] = ($perubahanStok[$bahanId] ?? 0)
                        + (float) $resep->takaran * $detail->jumlah;
                }
            }

            $bahans = Bahan::whereIn('id', array_keys($perubahanStok))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            foreach ($perubahanStok as $bahanId => $jumlah) {
                $bahan = $bahans->get($bahanId);
                if ($bahan) {
                    $bahan->stok = round((float) $bahan->stok + $jumlah, 3);
                    $bahan->save();
                }
            }

            $pesanan->status = 'Dibatalkan';
            $pesanan->save();
            $this->menuStatus->segarkan($menuIds);
        });
    }

    private function pesanStokKurang(Bahan $bahan, float $dibutuhkan): string
    {
        return 'Stok '.$bahan->nama_bahan.' tidak cukup: butuh '
            .number_format($dibutuhkan, 3, ',', '.').' '.$bahan->satuan.', tersedia '
            .number_format((float) $bahan->stok, 3, ',', '.').' '.$bahan->satuan.'.';
    }
}
