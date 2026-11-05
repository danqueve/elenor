<?php

namespace App\Enums;

use App\Support\Quantity;

enum ProductUnit: string
{
    case Unit = 'u';
    case Meter = 'm';
    case Roll = 'rollo';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unidad',
            self::Meter => 'Metro',
            self::Roll => 'Rollo',
        };
    }

    /**
     * A quantity with its unit: "120 u.", "1.200 m", "1 rollo", "18 rollos".
     */
    public function format(string|int|float|null $quantity): string
    {
        $formatted = Quantity::format($quantity);

        return match ($this) {
            self::Unit => $formatted.' u.',
            self::Meter => $formatted.' m',
            self::Roll => $formatted.($formatted === '1' ? ' rollo' : ' rollos'),
        };
    }

    /**
     * Added after a price when it is not per unit: " / m", " / rollo".
     */
    public function priceSuffix(): string
    {
        return match ($this) {
            self::Unit => '',
            self::Meter => ' / m',
            self::Roll => ' / rollo',
        };
    }
}
