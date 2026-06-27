<?php

namespace App\Support;

class Terbilang
{
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Ubah angka menjadi terbilang Bahasa Indonesia (tanpa kata "rupiah").
     * Contoh: 1500000 => "Satu Juta Lima Ratus Ribu".
     */
    public static function make(int|float|string $number): string
    {
        $number = (int) round((float) $number);

        if ($number === 0) {
            return 'Nol';
        }

        if ($number < 0) {
            return 'Minus ' . self::make(abs($number));
        }

        $words = preg_replace('/\s+/', ' ', trim(self::convert($number)));

        return ucwords($words);
    }

    private static function convert(int $n): string
    {
        if ($n < 12) {
            return self::SATUAN[$n];
        }
        if ($n < 20) {
            return self::convert($n - 10) . ' belas';
        }
        if ($n < 100) {
            return self::convert(intdiv($n, 10)) . ' puluh ' . self::convert($n % 10);
        }
        if ($n < 200) {
            return 'seratus ' . self::convert($n - 100);
        }
        if ($n < 1000) {
            return self::convert(intdiv($n, 100)) . ' ratus ' . self::convert($n % 100);
        }
        if ($n < 2000) {
            return 'seribu ' . self::convert($n - 1000);
        }
        if ($n < 1_000_000) {
            return self::convert(intdiv($n, 1000)) . ' ribu ' . self::convert($n % 1000);
        }
        if ($n < 1_000_000_000) {
            return self::convert(intdiv($n, 1_000_000)) . ' juta ' . self::convert($n % 1_000_000);
        }
        if ($n < 1_000_000_000_000) {
            return self::convert(intdiv($n, 1_000_000_000)) . ' miliar ' . self::convert($n % 1_000_000_000);
        }

        return self::convert(intdiv($n, 1_000_000_000_000)) . ' triliun ' . self::convert($n % 1_000_000_000_000);
    }
}
