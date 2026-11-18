<?php

namespace App\Console\Commands;

use App\Models\Bahan;
use App\Models\Menu;
use App\Models\Resep;
use App\Services\MenuStatusService;
use App\Support\CsvReader;
use App\Support\UnitConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ImporDataAwal extends Command
{
    protected $signature = 'impor:data-awal';

    protected $description = 'Impor data awal bahan, menu, dan resep dari CSV.';

    public function handle(MenuStatusService $menuStatus): int
    {
        if (! Schema::hasTable('kategori_menu')) {
            $this->error('Tabel kategori_menu belum tersedia. Jalankan migrasi terlebih dahulu.');

            return self::FAILURE;
        }

        $kategoriByName = DB::table('kategori_menu')
            ->get(['nama_kategori'])
            ->mapWithKeys(fn ($kategori) => [$this->nameKey($kategori->nama_kategori) => trim($kategori->nama_kategori)])
            ->all();
        if ($kategoriByName === []) {
            $this->error('Kategori menu belum disiapkan. Jalankan KategoriMenuSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        try {
            $bahanRows = $this->readRows('bahan.csv', ['kode', 'nama_bahan', 'stok_awal', 'satuan', 'harga_per_satuan']);
            $menuRows = $this->readRows('menu.csv', ['kode', 'nama_menu', 'kategori', 'harga_jual']);
            $resepRows = $this->readRows('resep.csv', ['kode_menu', 'nama_bahan', 'takaran', 'satuan']);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = [
            'bahan' => ['accepted' => 0, 'rejected' => 0],
            'menu' => ['accepted' => 0, 'rejected' => 0],
            'resep' => ['accepted' => 0, 'rejected' => 0],
        ];

        DB::transaction(function () use ($bahanRows, $menuRows, $resepRows, $kategoriByName, $menuStatus, &$summary) {
            $bahanByName = $this->importBahan($bahanRows, $summary);
            [$menuByCode, $menuIds] = $this->importMenu($menuRows, $kategoriByName, $summary);
            $this->importResep($resepRows, $menuByCode, $bahanByName, $summary);
            $menuStatus->segarkan($menuIds);
        });

        $this->table(
            ['Data', 'Diterima', 'Dilewati'],
            [
                ['bahan.csv', $summary['bahan']['accepted'], $summary['bahan']['rejected']],
                ['menu.csv', $summary['menu']['accepted'], $summary['menu']['rejected']],
                ['resep.csv', $summary['resep']['accepted'], $summary['resep']['rejected']],
            ]
        );

        return self::SUCCESS;
    }

    private function readRows(string $file, array $requiredHeaders): array
    {
        $csv = CsvReader::read(database_path('data/'.$file));
        $missingHeaders = array_diff($requiredHeaders, $csv['headers']);
        if ($missingHeaders !== []) {
            throw new RuntimeException("Header {$file} tidak lengkap: ".implode(', ', $missingHeaders));
        }

        return $csv['rows'];
    }

    private function importBahan(array $rows, array &$summary): array
    {
        $existingByName = Bahan::all()->mapWithKeys(fn (Bahan $bahan) => [
            $this->nameKey($bahan->nama_bahan) => $bahan,
        ])->all();
        $bahanByName = [];
        $seenNames = [];
        $seenCodes = [];

        foreach ($rows as $record) {
            if ($record['error'] !== null) {
                $this->reject($summary, 'bahan', 'bahan.csv', $record['line'], $record['error']);
                continue;
            }

            $data = $record['data'];
            $code = trim($data['kode']);
            $name = trim($data['nama_bahan']);
            $nameKey = $this->nameKey($name);
            $stock = $this->number($data['stok_awal']);
            $price = $this->number($data['harga_per_satuan']);
            $unit = UnitConverter::toBase(1, $data['satuan']);

            if ($code === '' || $name === '' || $stock === null || $stock < 0 || $price === null || $price < 0 || $unit === null) {
                $this->reject($summary, 'bahan', 'bahan.csv', $record['line'], 'Kolom wajib kosong atau nilainya tidak valid.');
                continue;
            }
            if (isset($seenCodes[$code]) || isset($seenNames[$nameKey])) {
                $this->reject($summary, 'bahan', 'bahan.csv', $record['line'], 'Kode atau nama bahan duplikat.');
                continue;
            }

            $seenCodes[$code] = true;
            $seenNames[$nameKey] = true;
            $bahan = $existingByName[$nameKey] ?? new Bahan;
            $values = [
                'nama_bahan' => $name,
                'satuan' => $unit['unit'],
                'stok' => round($stock * $unit['factor'], 3),
                'harga_per_satuan' => round($price / $unit['factor'], 2),
                'harga_diperbarui_at' => now(),
            ];
            if (! $bahan->exists) {
                $values['batas_minimum'] = 0;
            }

            $bahan->fill($values);
            $bahan->save();
            $existingByName[$nameKey] = $bahan;
            $bahanByName[$nameKey] = $bahan;
            $summary['bahan']['accepted']++;
        }

        return $bahanByName;
    }

    private function importMenu(array $rows, array $kategoriByName, array &$summary): array
    {
        $existingByName = Menu::all()->mapWithKeys(fn (Menu $menu) => [
            $this->nameKey($menu->nama_menu) => $menu,
        ])->all();
        $menuByCode = [];
        $menuIds = [];
        $seenCodes = [];
        $seenNames = [];

        foreach ($rows as $record) {
            if ($record['error'] !== null) {
                $this->reject($summary, 'menu', 'menu.csv', $record['line'], $record['error']);
                continue;
            }

            $data = $record['data'];
            $code = trim($data['kode']);
            $name = trim($data['nama_menu']);
            $nameKey = $this->nameKey($name);
            $categoryKey = $this->nameKey($data['kategori']);
            $price = $this->number($data['harga_jual']);

            if ($code === '' || $name === '' || $price === null || $price < 0 || ! isset($kategoriByName[$categoryKey])) {
                $this->reject($summary, 'menu', 'menu.csv', $record['line'], 'Kode, nama, kategori, atau harga tidak valid.');
                continue;
            }
            if (isset($seenCodes[$code]) || isset($seenNames[$nameKey])) {
                $this->reject($summary, 'menu', 'menu.csv', $record['line'], 'Kode atau nama menu duplikat.');
                continue;
            }

            $seenCodes[$code] = true;
            $seenNames[$nameKey] = true;
            $menu = $existingByName[$nameKey] ?? new Menu;
            $values = [
                'nama_menu' => $name,
                'kategori' => $kategoriByName[$categoryKey],
                'harga_jual' => $price,
            ];
            if (! $menu->exists) {
                $values['status'] = 'Aktif';
            }

            $menu->fill($values);
            $menu->save();
            $existingByName[$nameKey] = $menu;
            $menuByCode[$code] = $menu;
            $menuIds[] = $menu->id;
            $summary['menu']['accepted']++;
        }

        return [$menuByCode, $menuIds];
    }

    private function importResep(array $rows, array $menuByCode, array $bahanByName, array &$summary): void
    {
        $seenPairs = [];

        foreach ($rows as $record) {
            if ($record['error'] !== null) {
                $this->reject($summary, 'resep', 'resep.csv', $record['line'], $record['error']);
                continue;
            }

            $data = $record['data'];
            $menuCode = trim($data['kode_menu']);
            $ingredientName = trim($data['nama_bahan']);
            $quantity = $this->number($data['takaran']);
            if ($menuCode === '' || ! isset($menuByCode[$menuCode]) || $ingredientName === '' || $quantity === null || $quantity <= 0) {
                $this->reject($summary, 'resep', 'resep.csv', $record['line'], 'Menu, nama bahan, atau takaran tidak valid.');
                continue;
            }

            $match = $this->matchBahan($ingredientName, $bahanByName);
            if ($match === null) {
                $this->reject($summary, 'resep', 'resep.csv', $record['line'], "Bahan '{$ingredientName}' tidak ditemukan atau pencocokannya ambigu.");
                continue;
            }

            $bahan = $match['bahan'];
            if ($match['similarity'] !== null) {
                Log::warning('Nama bahan resep CSV dicocokkan secara mirip.', [
                    'file' => 'resep.csv',
                    'baris' => $record['line'],
                    'nama_csv' => $ingredientName,
                    'nama_dipakai' => $bahan->nama_bahan,
                    'similar_text' => round($match['similarity'], 2),
                ]);
            }

            $converted = UnitConverter::toBase($quantity, $data['satuan']);
            if ($converted === null || $converted['unit'] !== $bahan->satuan) {
                $this->reject($summary, 'resep', 'resep.csv', $record['line'], 'Satuan takaran tidak cocok dengan satuan dasar bahan.');
                continue;
            }

            $menu = $menuByCode[$menuCode];
            $pairKey = $menu->id.':'.$bahan->id;
            if (isset($seenPairs[$pairKey])) {
                $this->reject($summary, 'resep', 'resep.csv', $record['line'], 'Pasangan menu dan bahan duplikat.');
                continue;
            }

            $seenPairs[$pairKey] = true;
            Resep::updateOrCreate(
                ['menu_id' => $menu->id, 'bahan_id' => $bahan->id],
                ['takaran' => round($converted['amount'], 3)]
            );
            $summary['resep']['accepted']++;
        }
    }

    private function matchBahan(string $name, array $bahanByName): ?array
    {
        $key = $this->nameKey($name);
        if (isset($bahanByName[$key])) {
            return ['bahan' => $bahanByName[$key], 'similarity' => null];
        }

        $bestScore = 0.0;
        $bestMatches = [];
        foreach ($bahanByName as $candidateKey => $bahan) {
            similar_text($key, $candidateKey, $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatches = [$bahan];
            } elseif (abs($score - $bestScore) < 0.0001) {
                $bestMatches[] = $bahan;
            }
        }

        if ($bestScore < 80 || count($bestMatches) !== 1) {
            return null;
        }

        return ['bahan' => $bestMatches[0], 'similarity' => $bestScore];
    }

    private function reject(array &$summary, string $dataset, string $file, int $line, string $reason): void
    {
        $summary[$dataset]['rejected']++;
        Log::warning('Baris CSV data awal dilewati.', [
            'file' => $file,
            'baris' => $line,
            'alasan' => $reason,
        ]);
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }

    private function number(?string $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
