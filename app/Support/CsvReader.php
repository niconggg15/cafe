<?php

namespace App\Support;

use RuntimeException;

class CsvReader
{
    public static function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Berkas CSV tidak dapat dibaca: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Berkas CSV tidak dapat dibuka: {$path}");
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            if ($headers === false) {
                throw new RuntimeException("Berkas CSV kosong: {$path}");
            }

            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            }
            $headers = array_map(static fn ($header) => trim((string) $header), $headers);

            if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
                throw new RuntimeException("Header CSV kosong atau ganda: {$path}");
            }

            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $line++;
                if (count($values) === 1 && trim((string) $values[0]) === '') {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    $rows[] = [
                        'line' => $line,
                        'data' => null,
                        'error' => 'Jumlah kolom tidak sesuai dengan header.',
                    ];

                    continue;
                }

                $rows[] = [
                    'line' => $line,
                    'data' => array_combine($headers, array_map(static fn ($value) => trim((string) $value), $values)),
                    'error' => null,
                ];
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }
}
