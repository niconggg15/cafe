<?php

namespace Database\Seeders;

use App\Models\Bahan;
use App\Support\CsvReader;
use App\Support\UnitConverter;
use Illuminate\Database\Seeder;
use RuntimeException;


class DemoSeeder extends Seeder
{
    public function run()
    {
        $csv = CsvReader::read(database_path('data-seeder-csv/seed_stok_demo.csv'));
        $requiredHeaders = ['nama_bahan', 'jumlah', 'satuan'];
        $missingHeaders = array_diff($requiredHeaders, $csv['headers']);
        if ($missingHeaders !== []) {
            throw new RuntimeException('Header stok demo tidak lengkap: '.implode(', ', $missingHeaders));
        }

        foreach ($csv['rows'] as $record) {
            if ($record['error'] !== null) {
                throw new RuntimeException("CSV stok demo baris {$record['line']}: {$record['error']}");
            }

            $name = trim($record['data']['nama_bahan']);
            $amount = $record['data']['jumlah'];
            $unit = trim($record['data']['satuan']);
            if ($name === '' || ! is_numeric($amount) || (float) $amount < 0) {
                throw new RuntimeException("CSV stok demo baris {$record['line']} berisi nilai yang tidak valid.");
            }

            $converted = UnitConverter::toBase((float) $amount, $unit);
            if ($converted === null) {
                throw new RuntimeException("CSV stok demo baris {$record['line']} memakai satuan yang tidak didukung.");
            }

            $bahan = Bahan::whereRaw('LOWER(TRIM(nama_bahan)) = ?', [mb_strtolower($name, 'UTF-8')])->first();
            if (! $bahan) {
                throw new RuntimeException("Bahan {$name} belum ada; jalankan impor data awal sebelum DemoSeeder.");
            }
            if ($bahan->satuan !== $converted['unit']) {
                throw new RuntimeException("Satuan stok demo untuk {$name} tidak cocok dengan satuan bahan di database.");
            }

            $bahan->stok = round($converted['amount'], 3);
            $bahan->save();
        }
    }
}
