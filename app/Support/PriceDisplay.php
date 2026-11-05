<?php

namespace App\Support;

/**
 * What the catalog shows for the price of a product to a given visitor.
 * Amounts are decimal strings ("1234.50"), as everywhere else.
 */
final readonly class PriceDisplay
{
    public function __construct(
        /** The price the visitor pays. */
        public string $price,
        /** The price before the offer, shown struck through. Null when there is no offer. */
        public ?string $wasPrice = null,
        /** How much cheaper than the previous price, in whole percent. */
        public ?int $discountPercent = null,
        /** Whether $price is the professional price. */
        public bool $isProfessional = false,
    ) {}
}
