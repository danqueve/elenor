<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * An item that is not tied to a product (as after the product was
     * deleted). Use forProduct() to freeze a real product.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = number_format(fake()->numberBetween(1500, 90000), 2, '.', '');
        $quantity = number_format(fake()->numberBetween(1, 5), 2, '.', '');

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_sku' => strtoupper(fake()->bothify('???-####')),
            'product_name' => fake()->randomElement(['Cable unipolar 2,5 mm²', 'Termomagnética 2 x 20 A', 'Lámpara LED 9 W']),
            'product_unit' => ProductUnit::Unit,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => bcmul($unitPrice, $quantity, 2),
        ];
    }

    /**
     * Freeze the data of a product the way checkout does: name, SKU, unit and
     * price are copied, and the subtotal is calculated from them.
     */
    public function forProduct(Product $product, string $quantity = '1.00', ?string $unitPrice = null): static
    {
        $unitPrice ??= $product->list_price;

        return $this->state([
            'product_id' => $product->getKey(),
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'product_unit' => $product->unit,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => bcmul($unitPrice, $quantity, 2),
        ]);
    }
}
