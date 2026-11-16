<?php

namespace App\Support;

class UnitConverter
{
    public static function toBase(float $amount, string $unit): ?array
    {
        $unit = strtolower(trim($unit));

        if ($unit === 'kg') {
            return ['amount' => $amount * 1000, 'unit' => 'g', 'factor' => 1000];
        }

        if ($unit === 'liter') {
            return ['amount' => $amount * 1000, 'unit' => 'ml', 'factor' => 1000];
        }

        if (in_array($unit, ['g', 'ml', 'pcs'], true)) {
            return ['amount' => $amount, 'unit' => $unit, 'factor' => 1];
        }

        return null;
    }
}
