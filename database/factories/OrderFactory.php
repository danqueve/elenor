<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * A received order, for delivery, with no items (totals at zero).
     * Use withItems() to add items and get consistent totals.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$city, $province, $postalCode] = AddressFactory::place();

        return [
            'number' => 'EL-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'status' => OrderStatus::Received,
            'shipping_method' => ShippingMethod::Delivery,
            'shipping_cost' => '0.00',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'subtotal' => '0.00',
            'total' => '0.00',
            'notes' => null,
            'shipping_street' => fake()->streetName(),
            'shipping_number' => (string) fake()->numberBetween(1, 4999),
            'shipping_city' => $city,
            'shipping_province' => $province,
            'shipping_postal_code' => $postalCode,
            'shipping_reference' => null,
        ];
    }

    /**
     * Picked up at the shop: no address snapshot.
     */
    public function pickup(): static
    {
        return $this->state([
            'shipping_method' => ShippingMethod::Pickup,
            'payment_method' => PaymentMethod::CashOnPickup,
            'shipping_street' => null,
            'shipping_number' => null,
            'shipping_city' => null,
            'shipping_province' => null,
            'shipping_postal_code' => null,
            'shipping_reference' => null,
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    /**
     * Create items for the order and set subtotal and total from them.
     */
    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (Order $order) use ($count) {
            $items = OrderItem::factory()->count($count)->for($order)->create();

            $subtotal = $items->reduce(fn (string $sum, OrderItem $item) => bcadd($sum, $item->subtotal, 2), '0.00');

            $order->update([
                'subtotal' => $subtotal,
                'total' => bcadd($subtotal, $order->shipping_cost, 2),
            ]);
        });
    }
}
