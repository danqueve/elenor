<?php

namespace App\Services\Checkout;

use App\Enums\ShippingMethod;

/**
 * Where Elenor ships and what it costs (config/shop.php, "shipping").
 */
class ShippingZones
{
    /**
     * The provinces orders can be shipped to, in the configured order.
     *
     * @return list<string>
     */
    public function provinces(): array
    {
        return array_keys(config('shop.shipping.zones', []));
    }

    public function covers(?string $province): bool
    {
        return $province !== null && in_array($province, $this->provinces(), true);
    }

    /**
     * The shipping cost as a decimal string ("8500.00"), "0.00" for pickup
     * or free shipping, or null when it is to be agreed with the customer
     * (the zone has no price yet, or a price that isn't a valid amount).
     */
    public function costFor(ShippingMethod $method, ?string $province): ?string
    {
        if ($method === ShippingMethod::Pickup) {
            return '0.00';
        }

        if (! $this->covers($province)) {
            return null;
        }

        $cost = trim((string) config("shop.shipping.zones.{$province}"));

        if (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $cost)) {
            return null;
        }

        return bcadd($cost, '0', 2);
    }
}
