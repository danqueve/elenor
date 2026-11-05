<?php

namespace Tests\Unit;

use App\Enums\ProductUnit;
use App\Support\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{
    /**
     * @return array<string, array{string|int|float|null, string}>
     */
    public static function quantities(): array
    {
        return [
            'whole number from the database' => ['1200.00', '1.200'],
            'one decimal' => ['12.50', '12,5'],
            'two decimals' => ['12.75', '12,75'],
            'zero' => ['0.00', '0'],
            'null' => [null, '0'],
            'integer' => [5, '5'],
            'less than one' => [0.5, '0,5'],
            'thousands and decimals' => ['1234567.89', '1.234.567,89'],
            'trailing zeros of a round number are kept' => ['100.00', '100'],
        ];
    }

    #[DataProvider('quantities')]
    public function test_it_drops_useless_zeros(string|int|float|null $quantity, string $expected): void
    {
        $this->assertSame($expected, Quantity::format($quantity));
    }

    /**
     * @return array<string, array{ProductUnit, string, string}>
     */
    public static function withUnit(): array
    {
        return [
            'units' => [ProductUnit::Unit, '120.00', '120 u.'],
            'meters' => [ProductUnit::Meter, '1200.00', '1.200 m'],
            'half a meter' => [ProductUnit::Meter, '0.50', '0,5 m'],
            'one roll' => [ProductUnit::Roll, '1.00', '1 rollo'],
            'several rolls' => [ProductUnit::Roll, '18.00', '18 rollos'],
            'a fraction of a roll' => [ProductUnit::Roll, '0.50', '0,5 rollos'],
        ];
    }

    #[DataProvider('withUnit')]
    public function test_the_unit_adds_its_label(ProductUnit $unit, string $quantity, string $expected): void
    {
        $this->assertSame($expected, $unit->format($quantity));
    }

    public function test_the_price_suffix_is_only_for_units_of_measure(): void
    {
        $this->assertSame('', ProductUnit::Unit->priceSuffix());
        $this->assertSame(' / m', ProductUnit::Meter->priceSuffix());
        $this->assertSame(' / rollo', ProductUnit::Roll->priceSuffix());
    }
}
