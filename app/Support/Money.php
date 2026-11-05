<?php

namespace App\Support;

class Money
{
    /**
     * Format an amount for display, e.g. 12345.67 => "$ 12.345,67".
     *
     * The space after the symbol is non-breaking so the symbol can never wrap
     * onto a different line than the number. This is for display only: prices
     * and totals are always calculated on the server, never from this string.
     */
    public static function format(int|float|string|null $amount, ?int $decimals = null): string
    {
        $currency = config('shop.currency');
        $decimals ??= $currency['decimals'];

        $value = round((float) $amount, $decimals);

        return ($value < 0 ? '-' : '')
            .$currency['symbol']
            ."\u{00A0}"
            .number_format(
                abs($value),
                $decimals,
                $currency['decimal_separator'],
                $currency['thousands_separator'],
            );
    }
}
