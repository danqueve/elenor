<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Envío a domicilio',
            self::Pickup => 'Retiro en el local',
        };
    }
}
