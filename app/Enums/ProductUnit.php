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

    /**
     * The smallest amount that can be bought, and the steps the quantity
     * moves in: cable by the metre can be cut at half metres; units and rolls
     * are whole.
     */
    public function step(): string
    {
        return match ($this) {
            self::Meter => '0.50',
            self::Unit, self::Roll => '1.00',
        };
    }

    /**
     * Whether a quantity ("2.50") is positive and a multiple of the step.
     */
    public function accepts(string $quantity): bool
    {
        if (bccomp($quantity, '0', 2) <= 0) {
            return false;
        }

        return bccomp(bcmod($quantity, $this->step(), 2), '0', 2) === 0;
    }

    /**
     * What to tell the customer when a quantity is not accepted.
     */
    public function stepHint(): string
    {
        return match ($this) {
            self::Meter => 'Los cables se venden por metro, de a medio metro (por ejemplo 2 o 2,5).',
            self::Unit => 'Se vende por unidad: ingresá un número entero.',
            self::Roll => 'Se vende por rollo: ingresá un número entero.',
        };
    }
}
