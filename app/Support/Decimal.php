<?php

namespace App\Support;

/**
 * Money arithmetic on decimal strings (bcmath), never on floats.
 */
class Decimal
{
    /**
     * Price × quantity rounded half up to cents: "1234.50" × "2.50" => "3086.25".
     * Both values are non-negative (CHECK constraints in the database).
     */
    public static function multiply(string $price, string $quantity): string
    {
        return bcadd(bcmul($price, $quantity, 4), '0.005', 2);
    }

    /**
     * @param  iterable<string>  $amounts
     */
    public static function sum(iterable $amounts): string
    {
        $total = '0.00';

        foreach ($amounts as $amount) {
            $total = bcadd($total, $amount, 2);
        }

        return $total;
    }
}
