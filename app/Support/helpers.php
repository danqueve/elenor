<?php

use App\Support\Money;

if (! function_exists('money')) {
    /**
     * Format an amount as currency, e.g. "$ 12.345,67".
     */
    function money(int|float|string|null $amount): string
    {
        return Money::format($amount);
    }
}
