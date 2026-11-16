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

    /**
     * The methods the checkout offers. Elenor is online only (no shop to pick
     * up from), so pickup stays in the enum for the future but is offered only
     * when SHOP_PICKUP_ENABLED is on.
     *
     * @return list<self>
     */
    public static function offered(): array
    {
        return config('shop.pickup.enabled') ? [self::Delivery, self::Pickup] : [self::Delivery];
    }

    public function isOffered(): bool
    {
        return in_array($this, self::offered(), true);
    }
}
