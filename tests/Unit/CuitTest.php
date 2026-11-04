<?php

namespace Tests\Unit;

use App\Support\Cuit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CuitTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validCuits(): array
    {
        return [
            'only digits' => ['20123456786'],
            'with dashes' => ['20-12345678-6'],
            'with spaces' => ['20 12345678 6'],
            'company (AFIP)' => ['33-69345023-9'],
            'company prefix 30' => ['30712345671'],
        ];
    }

    #[DataProvider('validCuits')]
    public function test_it_accepts_valid_cuits(string $cuit): void
    {
        $this->assertTrue(Cuit::isValid($cuit));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCuits(): array
    {
        return [
            'wrong check digit' => ['20123456787'],
            'too short' => ['2012345678'],
            'too long' => ['201234567866'],
            'letters' => ['20-ABCDEFGH-6'],
            'unknown prefix' => ['10123456786'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidCuits')]
    public function test_it_rejects_invalid_cuits(string $cuit): void
    {
        $this->assertFalse(Cuit::isValid($cuit));
    }

    public function test_the_check_digit_is_computed_with_modulo_11(): void
    {
        $this->assertSame(6, Cuit::checkDigit('2012345678'));
        $this->assertSame(9, Cuit::checkDigit('3369345023'));
        // A remainder of 0 gives a check digit of 0.
        $this->assertSame(0, Cuit::checkDigit('2006000000'));
    }

    public function test_prefixes_that_cannot_produce_a_valid_check_digit_return_null(): void
    {
        $this->assertNull(Cuit::checkDigit('2000000001'));
    }

    public function test_it_formats_and_strips_dashes(): void
    {
        $this->assertSame('20-12345678-6', Cuit::format('20123456786'));
        $this->assertSame('20-12345678-6', Cuit::format('20-12345678-6'));
        $this->assertSame('20123456786', Cuit::digits('20-12345678-6'));
        $this->assertSame('123', Cuit::format('123'));
    }
}
