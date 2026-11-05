<?php

use App\Support\Money;

if (! function_exists('money')) {
    /**
     * Format an amount as currency, e.g. "$ 12.345,67" ("$ 12.346" with 0 decimals).
     */
    function money(int|float|string|null $amount, ?int $decimals = null): string
    {
        return Money::format($amount, $decimals);
    }
}
