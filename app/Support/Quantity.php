<?php

namespace App\Support;

class Quantity
{
    /**
     * Format a quantity the Argentine way without useless zeros:
     * "1200.00" => "1.200", "12.50" => "12,5", null => "0".
     * For display only: quantities are never calculated from this string.
     */
    public static function format(string|int|float|null $quantity): string
    {
        $formatted = number_format((float) $quantity, 2, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
