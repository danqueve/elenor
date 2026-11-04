<?php

namespace App\Enums;

enum IvaCondition: string
{
    case RegisteredResponsible = 'registered';
    case Monotax = 'monotax';
    case Exempt = 'exempt';
    case FinalConsumer = 'final_consumer';

    public function label(): string
    {
        return match ($this) {
            self::RegisteredResponsible => 'Responsable Inscripto',
            self::Monotax => 'Monotributista',
            self::Exempt => 'Exento',
            self::FinalConsumer => 'Consumidor final',
        };
    }
}
