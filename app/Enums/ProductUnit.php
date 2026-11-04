<?php

namespace App\Enums;

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
}
