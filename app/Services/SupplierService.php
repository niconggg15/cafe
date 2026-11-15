<?php

namespace App\Services;

use App\Models\Bahan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    public function sinkron(): array
    {
        $config = config('services.supplier');
        if (empty($config['url']) || empty($config['api_key'])) {
            return [
                'ok' => false,
                'pesan' => 'Integrasi supplier belum dikonfigurasi. Isi SUPPLIER_URL dan SUPPLIER_API_KEY di .env.',
            ];
        }
        if (! filter_var($config['url'], FILTER_VALIDATE_URL)) {
            Log::warning('URL supplier tidak valid.');

            return [
                'ok' => false,
                'pesan' => 'URL supplier tidak valid. Harga terakhir tetap digunakan.',
            ];
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $config['api_key']])
                ->timeout($config['timeout'])
                ->get($config['url']);
        } catch (ConnectionException $exception) {
            Log::warning('Supplier tidak dapat dihubungi.', ['error' => $exception->getMessage()]);

            return [
                'ok' => false,
                'pesan' => 'Supplier tidak dapat dihubungi. Harga terakhir tetap digunakan.',
            ];
        }

        if (! $response->successful()) {
            Log::warning('Supplier mengembalikan respons gagal.', ['status' => $response->status()]);

            return [
                'ok' => false,
                'pesan' => 'Supplier menolak permintaan. Harga terakhir tetap digunakan.',
            ];
        }

        $rows = $response->json();
        if (! is_array($rows) || $rows === []) {
            Log::warning('Format respons supplier tidak valid.');

            return [
                'ok' => false,
                'pesan' => 'Format data dari supplier tidak valid. Harga terakhir tetap digunakan.',
            ];
        }

        $hargaSupplier = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['nama'], $row['harga']) ||
                ! is_string($row['nama']) || trim($row['nama']) === '' ||
                ! is_numeric($row['harga']) || ! is_finite((float) $row['harga']) || (float) $row['harga'] < 0) {
                Log::warning('Data harga supplier tidak valid.');

                return [
                    'ok' => false,
                    'pesan' => 'Data harga dari supplier tidak valid. Harga terakhir tetap digunakan.',
                ];
            }

            $nama = mb_strtolower(trim($row['nama']), 'UTF-8');
            if (array_key_exists($nama, $hargaSupplier)) {
                Log::warning('Respons supplier berisi nama bahan duplikat.');

                return [
                    'ok' => false,
                    'pesan' => 'Data supplier memuat bahan duplikat. Harga terakhir tetap digunakan.',
                ];
            }
            $hargaSupplier[$nama] = (float) $row['harga'];
        }

        $bahans = Bahan::all()->keyBy(fn (Bahan $bahan) => mb_strtolower(trim($bahan->nama_bahan), 'UTF-8'));
        $diperbarui = 0;
        DB::transaction(function () use ($bahans, $hargaSupplier, &$diperbarui) {
            foreach ($hargaSupplier as $nama => $harga) {
                $bahan = $bahans->get($nama);
                if (! $bahan) {
                    continue;
                }

                $bahan->harga_per_satuan = $harga;
                $bahan->harga_diperbarui_at = now();
                $bahan->save();
                $diperbarui++;
            }
        });

        return [
            'ok' => true,
            'pesan' => $diperbarui
                ? "{$diperbarui} harga bahan berhasil disinkronkan."
                : 'Supplier merespons, tetapi tidak ada bahan yang cocok untuk diperbarui.',
        ];
    }
}
