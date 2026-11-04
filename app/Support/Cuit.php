<?php

namespace App\Support;

/**
 * Argentine CUIT/CUIL helpers. CUITs are stored as 11 digits, without dashes.
 */
final class Cuit
{
    /** Prefixes assigned to people (20, 23, 24, 27) and companies (30, 33, 34). */
    private const PREFIXES = ['20', '23', '24', '27', '30', '33', '34'];

    private const WEIGHTS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    /**
     * Check digit (modulo 11) for the first ten digits, or null when those
     * digits can't produce a valid CUIT (the remainder leaves a "10").
     */
    public static function checkDigit(string $firstTenDigits): ?int
    {
        $sum = 0;

        foreach (str_split($firstTenDigits) as $position => $digit) {
            $sum += (int) $digit * self::WEIGHTS[$position];
        }

        $remainder = $sum % 11;
        $digit = $remainder === 0 ? 0 : 11 - $remainder;

        return $digit === 10 ? null : $digit;
    }

    /**
     * Whether the value is a well-formed CUIT. Accepts dashes and spaces.
     */
    public static function isValid(string $cuit): bool
    {
        $digits = self::digits($cuit);

        if (strlen($digits) !== 11 || ! in_array(substr($digits, 0, 2), self::PREFIXES, true)) {
            return false;
        }

        $expected = self::checkDigit(substr($digits, 0, 10));

        return $expected !== null && $expected === (int) $digits[10];
    }

    /**
     * Strip everything that is not a digit: "20-12345678-6" => "20123456786".
     */
    public static function digits(string $cuit): string
    {
        return preg_replace('/\D+/', '', $cuit);
    }

    /**
     * Display format: "20123456786" => "20-12345678-6".
     */
    public static function format(string $cuit): string
    {
        $digits = self::digits($cuit);

        if (strlen($digits) !== 11) {
            return $cuit;
        }

        return substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10);
    }
}
