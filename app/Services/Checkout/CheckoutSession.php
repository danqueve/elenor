<?php

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use Illuminate\Contracts\Session\Session;

/**
 * The checkout steps are kept in the session until the order is placed, so
 * a visitor can fill them in before signing in (signing in keeps the session
 * data). Only already validated data is stored, and it is checked again when
 * read: the offered methods can change in between.
 */
class CheckoutSession
{
    private const KEY = 'checkout';

    public function __construct(private Session $session) {}

    /**
     * @param  array<string, mixed>  $delivery  Validated by DeliveryRequest.
     */
    public function putDelivery(array $delivery): void
    {
        $this->session->put(self::KEY.'.delivery', $delivery);

        // Another shipping method can change which payments are possible.
        $payment = $this->payment();

        if ($payment !== null && ! in_array($payment['payment_method'], PaymentMethod::offeredFor($this->shippingMethod()), true)) {
            $this->session->forget(self::KEY.'.payment');
        }
    }

    /**
     * @param  array<string, mixed>  $payment  Validated by PaymentRequest.
     */
    public function putPayment(array $payment): void
    {
        $this->session->put(self::KEY.'.payment', $payment);
    }

    /**
     * Step 1, or null when it is missing or no longer valid.
     *
     * @return array{shipping_method: ShippingMethod, address: array<string, ?string>|null}|null
     */
    public function delivery(): ?array
    {
        $stored = $this->session->get(self::KEY.'.delivery');
        $method = ShippingMethod::tryFrom((string) ($stored['shipping_method'] ?? ''));

        if ($method === null || ! $method->isOffered()) {
            return null;
        }

        if ($method === ShippingMethod::Pickup) {
            return ['shipping_method' => $method, 'address' => null];
        }

        $address = [
            'street' => $stored['street'] ?? null,
            'number' => $stored['number'] ?? null,
            'city' => $stored['city'] ?? null,
            'province' => $stored['province'] ?? null,
            'postal_code' => $stored['postal_code'] ?? null,
            'reference' => $stored['reference'] ?? null,
        ];

        if (! app(ShippingZones::class)->covers($address['province']) || in_array(null, array_slice($address, 0, 5), true)) {
            return null;
        }

        return ['shipping_method' => $method, 'address' => $address];
    }

    /**
     * Step 2, or null when it is missing or no longer valid.
     *
     * @return array{payment_method: PaymentMethod, notes: ?string}|null
     */
    public function payment(): ?array
    {
        $stored = $this->session->get(self::KEY.'.payment');
        $method = PaymentMethod::tryFrom((string) ($stored['payment_method'] ?? ''));

        if ($method === null || ! in_array($method, PaymentMethod::offeredFor($this->shippingMethod()), true)) {
            return null;
        }

        return ['payment_method' => $method, 'notes' => $stored['notes'] ?? null];
    }

    /**
     * Both steps together, or null if one is missing.
     */
    public function data(): ?CheckoutData
    {
        $delivery = $this->delivery();
        $payment = $this->payment();

        if ($delivery === null || $payment === null) {
            return null;
        }

        return new CheckoutData($delivery['shipping_method'], $delivery['address'], $payment['payment_method'], $payment['notes']);
    }

    /**
     * What the forms show: the raw step data, to fill the inputs back.
     *
     * @return array<string, mixed>
     */
    public function stored(string $step): array
    {
        return (array) $this->session->get(self::KEY.'.'.$step, []);
    }

    public function forget(): void
    {
        $this->session->forget(self::KEY);
    }

    /**
     * The chosen shipping method, or the first offered one.
     */
    public function shippingMethod(): ShippingMethod
    {
        return $this->delivery()['shipping_method'] ?? ShippingMethod::offered()[0];
    }
}
