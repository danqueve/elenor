<?php

namespace App\Services\Cart;

use App\Support\Decimal;

/**
 * The cart as the customer sees it: lines with prices and the subtotal.
 */
final readonly class CartContents
{
    public string $subtotal;

    /**
     * @param  list<CartLine>  $lines
     */
    public function __construct(public array $lines)
    {
        $this->subtotal = Decimal::sum(array_map(fn (CartLine $line) => $line->subtotal, $lines));
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function count(): int
    {
        return count($this->lines);
    }

    /**
     * Whether some line can't be bought as it is (no stock, hidden product...).
     */
    public function hasProblems(): bool
    {
        foreach ($this->lines as $line) {
            if (! $line->canBeBought()) {
                return true;
            }
        }

        return false;
    }
}
