<?php

namespace App\Services\Cart;

use App\Models\Product;

/**
 * One product in the cart with the price this customer pays, calculated on
 * the server when the cart is read (carts never store prices).
 */
final readonly class CartLine
{
    public function __construct(
        public Product $product,
        public string $quantity,
        public string $unitPrice,
        public string $subtotal,
        public ?CartProblem $problem = null,
    ) {}

    public function canBeBought(): bool
    {
        return $this->problem === null;
    }
}
