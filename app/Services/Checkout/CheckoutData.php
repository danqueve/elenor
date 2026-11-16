<?php

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;

/**
 * What the customer chose in the first two checkout steps.
 */
final readonly class CheckoutData
{
    /**
     * @param  array{street: string, number: string, city: string, province: string, postal_code: string, reference: ?string}|null  $address  Null for pickup.
     */
    public function __construct(
        public ShippingMethod $shippingMethod,
        public ?array $address,
        public PaymentMethod $paymentMethod,
        public ?string $notes = null,
    ) {}
}
