<?php

namespace Database\Seeders;

use App\Support\CsvReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KategoriMenuSeeder extends Seeder
{
    public function run()
    {
        $csv = CsvReader::read(database_path('data-seeder-csv/master_kategori_menu.csv'));
        $requiredHeaders = ['kode_kategori', 'nama_kategori'];
        $missingHeaders = array_diff($requiredHeaders, $csv['headers']);
        if ($missingHeaders !== []) {
            throw new RuntimeException('Header master kategori tidak lengkap: '.implode(', ', $missingHeaders));
        }

        $records = [];
        $seenNames = [];
        $seenCodes = [];
        foreach ($csv['rows'] as $record) {
            if ($record['error'] !== null) {
                throw new RuntimeException("CSV master kategori baris {$record['line']}: {$record['error']}");
            }

            $code = trim($record['data']['kode_kategori']);
            $name = trim($record['data']['nama_kategori']);
            $nameKey = mb_strtolower($name, 'UTF-8');
            if ($code === '' || $name === '' || isset($seenCodes[$code]) || isset($seenNames[$nameKey])) {
                throw new RuntimeException("CSV master kategori baris {$record['line']} kosong atau duplikat.");
            }

            $seenCodes[$code] = true;
            $seenNames[$nameKey] = true;
            $records[] = [
                'kode_kategori' => $code,
                'nama_kategori' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records === []) {
            throw new RuntimeException('CSV master kategori tidak berisi data.');
        }

        DB::table('kategori_menu')->upsert($records, ['nama_kategori'], ['kode_kategori', 'updated_at']);
    }
}
