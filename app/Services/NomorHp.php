<?php

namespace App\Services;

class NomorHp
{
    public static function normal(string $raw): ?string
    {
        $n = preg_replace('/[\s\-().]/', '', trim($raw));
        $n = ltrim($n, '+');
        if (!ctype_digit($n)) return null;
        if (str_starts_with($n, '62')) $n = '0' . substr($n, 2);
        if (!str_starts_with($n, '0')) return null;
        $len = strlen($n);
        return ($len >= 10 && $len <= 15) ? $n : null;
    }
}
