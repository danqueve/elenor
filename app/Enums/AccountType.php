<?php

namespace App\Enums;

enum AccountType: string
{
    case FinalConsumer = 'final';
    case Professional = 'professional';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::FinalConsumer => 'Consumidor final',
            self::Professional => 'Profesional',
            self::Company => 'Empresa',
        };
    }
}
