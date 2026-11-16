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

    /**
     * Read a quantity typed by a person: "2", "2,5" or "2.5" => "2.50".
     * Anything else (empty, negative, letters, more than two decimals,
     * thousands separators) gives null.
     */
    public static function parse(mixed $input): ?string
    {
        if (is_int($input)) {
            $input = (string) $input;
        }

        if (! is_string($input)) {
            return null;
        }

        $input = str_replace(',', '.', trim($input));

        if (! preg_match('/^\d{1,8}(\.\d{1,2})?$/', $input)) {
            return null;
        }

        return bcadd($input, '0', 2);
    }
}
