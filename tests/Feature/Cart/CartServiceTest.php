<?php

namespace Tests\Feature\Cart;

use App\Enums\ProductUnit;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartException;
use App\Services\Cart\CartProblem;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    public function test_a_guest_cart_is_kept_by_session(): void
    {
        Session::setId(str_repeat('a', 40));
        $product = Product::factory()->create(['stock' => '10.00']);

        $this->cart()->add($product, '2');

        $cart = Cart::query()->sole();
        $this->assertSame(str_repeat('a', 40), $cart->session_id);
        $this->assertNull($cart->user_id);
        $this->assertSame(1, $this->cart()->count());
    }

    public function test_a_customer_cart_is_kept_by_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->cart()->add(Product::factory()->create(), '1');

        $cart = Cart::query()->sole();
        $this->assertSame($user->id, $cart->user_id);
        $this->assertNull($cart->session_id);
    }

    public function test_adding_the_same_product_adds_up_the_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);

        $this->cart()->add($product, '2');
        $total = $this->cart()->add($product, '3');

        $this->assertSame('5.00', $total);
        $this->assertSame(1, $this->cart()->count());
    }

    public function test_cable_by_the_metre_takes_half_metres(): void
    {
        $this->actingAs(User::factory()->create());
        $cable = Product::factory()->soldBy(ProductUnit::Meter)->create(['stock' => '100.00']);

        $this->assertSame('2.50', $this->cart()->add($cable, '2,5'));

        $this->expectException(CartException::class);
        $this->cart()->add($cable, '0.3');
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidQuantities(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'half a unit' => ['1.5'],
            'letters' => ['dos'],
            'empty' => [''],
            'array' => [['1']],
            'thousands separator' => ['1.000,5'],
        ];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantities_are_rejected(mixed $quantity): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(CartException::class);

        $this->cart()->add(Product::factory()->create(['stock' => '100.00']), $quantity);
    }

    public function test_the_stock_limits_what_can_be_added_counting_what_the_cart_has(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '5.00']);

        $this->cart()->add($product, '4');

        try {
            $this->cart()->add($product, '2');
            $this->fail('It should not add more than the stock.');
        } catch (CartException $exception) {
            $this->assertStringContainsString('ya tenés 4 u.', $exception->getMessage());
        }

        $this->assertSame('4.00', Cart::query()->sole()->items()->sole()->quantity);
    }

    public function test_hidden_or_out_of_stock_products_cannot_be_added(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([Product::factory()->inactive()->create(), Product::factory()->outOfStock()->create()] as $product) {
            try {
                $this->cart()->add($product, '1');
                $this->fail('It should not be added.');
            } catch (CartException) {
                // Expected.
            }
        }

        $this->assertSame(0, $this->cart()->count());
    }

    public function test_update_replaces_the_quantity_and_remove_takes_the_product_out(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);
        $this->cart()->add($product, '2');

        $this->assertSame('7.00', $this->cart()->update($product->id, '7'));

        $this->cart()->remove($product->id);
        $this->assertSame(0, $this->cart()->count());
    }

    public function test_update_checks_the_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);
        $this->cart()->add($product, '2');

        $this->expectException(CartException::class);
        $this->cart()->update($product->id, '11');
    }

    public function test_contents_use_the_price_of_each_customer(): void
    {
        $product = Product::factory()->create(['list_price' => '1000.00', 'professional_price' => '800.00', 'stock' => '10.00']);
        $professional = User::factory()->professional()->create();
        $this->actingAs($professional);
        $this->cart()->add($product, '3');

        $contents = $this->cart()->contents($professional);
        $this->assertSame('800.00', $contents->lines[0]->unitPrice);
        $this->assertSame('2400.00', $contents->subtotal);

        // The same cart read for a final customer: list price.
        $this->assertSame('3000.00', $this->cart()->contents(null, Cart::query()->sole())->subtotal);
    }

    public function test_metre_subtotals_are_rounded_to_cents(): void
    {
        $this->actingAs($user = User::factory()->create());
        $cable = Product::factory()->soldBy(ProductUnit::Meter)->create(['list_price' => '1234.57', 'stock' => '100.00']);

        $this->cart()->add($cable, '2.5');

        // 1234.57 × 2.5 = 3086.425 => 3086.43
        $this->assertSame('3086.43', $this->cart()->contents($user)->subtotal);
    }

    public function test_contents_flag_what_cannot_be_bought_anymore(): void
    {
        $this->actingAs($user = User::factory()->create());
        $hidden = Product::factory()->create(['stock' => '10.00']);
        $sold = Product::factory()->create(['stock' => '10.00']);
        $short = Product::factory()->create(['stock' => '10.00']);
        $fine = Product::factory()->create(['stock' => '10.00']);

        foreach ([$hidden, $sold, $short, $fine] as $product) {
            $this->cart()->add($product, '5');
        }

        $hidden->update(['is_active' => false]);
        $sold->update(['stock' => '0.00']);
        $short->update(['stock' => '3.00']);

        $contents = $this->cart()->contents($user);
        $problems = array_map(fn ($line) => $line->problem, $contents->lines);

        $this->assertSame([CartProblem::Unavailable, CartProblem::OutOfStock, CartProblem::NotEnoughStock, null], $problems);
        $this->assertTrue($contents->hasProblems());
    }
}
