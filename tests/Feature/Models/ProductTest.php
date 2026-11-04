<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{?Closure, ?string, string}>
     */
    public static function prices(): array
    {
        return [
            'guest' => [null, '900.00', '1000.00'],
            'final consumer' => [fn () => User::factory()->make(), '900.00', '1000.00'],
            'approved professional' => [fn () => User::factory()->professional()->make(), '900.00', '900.00'],
            'approved company' => [fn () => User::factory()->company()->make(), '900.00', '900.00'],
            'pending professional' => [fn () => User::factory()->pendingProfessional()->make(), '900.00', '1000.00'],
            'approved professional, product without professional price' => [fn () => User::factory()->professional()->make(), null, '1000.00'],
        ];
    }

    #[DataProvider('prices')]
    public function test_the_price_is_decided_on_the_server_by_who_is_buying(?Closure $buyer, ?string $professionalPrice, string $expected): void
    {
        $product = new Product(['list_price' => '1000.00', 'professional_price' => $professionalPrice]);

        $this->assertSame($expected, $product->priceFor($buyer === null ? null : $buyer()));
    }

    public function test_scopes_separate_active_and_available_products(): void
    {
        $available = Product::factory()->create();
        $inactive = Product::factory()->inactive()->create();
        $soldOut = Product::factory()->outOfStock()->create();

        $this->assertEqualsCanonicalizing(
            [$available->id, $soldOut->id],
            Product::query()->active()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$available->id, $inactive->id],
            Product::query()->inStock()->pluck('id')->all(),
        );
        $this->assertSame([$available->id], Product::query()->active()->inStock()->pluck('id')->all());
    }

    public function test_stock_is_checked_with_decimal_precision(): void
    {
        $this->assertTrue((new Product(['stock' => '0.50']))->isInStock());
        $this->assertTrue((new Product(['stock' => '12']))->isInStock());
        $this->assertFalse((new Product(['stock' => '0.00']))->isInStock());
    }
}
