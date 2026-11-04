<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{int|float|string|null, string}>
     */
    public static function amounts(): array
    {
        // The space after "$" is non-breaking (U+00A0).
        $peso = '$'."\u{00A0}";

        return [
            'decimals' => [12345.67, $peso.'12.345,67'],
            'integer' => [1500, $peso.'1.500,00'],
            'decimal string as stored in the database' => ['1500.5', $peso.'1.500,50'],
            'millions' => [1234567.891, $peso.'1.234.567,89'],
            'zero' => [0, $peso.'0,00'],
            'null' => [null, $peso.'0,00'],
            'rounds half up' => [0.005, $peso.'0,01'],
            'negative' => [-250.5, '-'.$peso.'250,50'],
            'negative that rounds to zero has no sign' => [-0.001, $peso.'0,00'],
        ];
    }

    #[DataProvider('amounts')]
    public function test_it_formats_amounts_as_argentine_pesos(int|float|string|null $amount, string $expected): void
    {
        $this->assertSame($expected, Money::format($amount));
    }

    public function test_the_money_helper_uses_the_same_format(): void
    {
        $this->assertSame(Money::format(12345.67), money(12345.67));
    }
}
