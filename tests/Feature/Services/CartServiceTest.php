<?php

namespace Tests\Feature\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function merge(string $sessionId, User $user): void
    {
        app(CartService::class)->mergeGuestCart($sessionId, $user);
    }

    /**
     * @return array<int, array{int, string}> The cart's lines as product id => quantity.
     */
    private function lines(Cart $cart): array
    {
        return $cart->items()->orderBy('product_id')->get()
            ->mapWithKeys(fn (CartItem $item) => [$item->product_id => $item->quantity])
            ->all();
    }

    public function test_without_a_guest_cart_nothing_happens(): void
    {
        $user = User::factory()->create();

        $this->merge('no-such-session', $user);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_the_guest_cart_becomes_the_customers_when_they_had_none(): void
    {
        $user = User::factory()->create();
        $guest = Cart::factory()->create(['session_id' => 'guest-session']);
        $item = CartItem::factory()->create(['cart_id' => $guest->id, 'quantity' => '2.00']);

        $this->merge('guest-session', $user);

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame($guest->id, $cart->id);
        $this->assertNull($cart->session_id);
        $this->assertSame([$item->product_id => '2.00'], $this->lines($cart));
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_both_carts_are_combined_adding_up_the_shared_products(): void
    {
        $user = User::factory()->create();
        $shared = Product::factory()->create();
        $onlyGuest = Product::factory()->create();
        $onlyUser = Product::factory()->create();

        $userCart = Cart::factory()->forUser($user)->create();
        CartItem::factory()->create(['cart_id' => $userCart->id, 'product_id' => $shared->id, 'quantity' => '3.00']);
        CartItem::factory()->create(['cart_id' => $userCart->id, 'product_id' => $onlyUser->id, 'quantity' => '1.00']);

        $guest = Cart::factory()->create(['session_id' => 'guest-session']);
        CartItem::factory()->create(['cart_id' => $guest->id, 'product_id' => $shared->id, 'quantity' => '2.50']);
        CartItem::factory()->create(['cart_id' => $guest->id, 'product_id' => $onlyGuest->id, 'quantity' => '4.00']);

        $this->merge('guest-session', $user);

        $this->assertSame(
            [$shared->id => '5.50', $onlyGuest->id => '4.00', $onlyUser->id => '1.00'],
            $this->lines($userCart),
        );
        $this->assertDatabaseMissing('carts', ['session_id' => 'guest-session']);
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 3);
    }

    public function test_an_empty_guest_cart_is_just_thrown_away(): void
    {
        $user = User::factory()->create();
        Cart::factory()->create(['session_id' => 'guest-session']);

        $this->merge('guest-session', $user);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_other_visitors_carts_are_left_alone(): void
    {
        $user = User::factory()->create();
        $mine = Cart::factory()->create(['session_id' => 'my-session']);
        CartItem::factory()->create(['cart_id' => $mine->id]);
        $other = Cart::factory()->create(['session_id' => 'someone-else']);
        CartItem::factory()->create(['cart_id' => $other->id]);

        $this->merge('my-session', $user);

        $this->assertDatabaseHas('carts', ['id' => $other->id, 'session_id' => 'someone-else', 'user_id' => null]);
        $this->assertSame(1, $other->items()->count());
    }

    public function test_merging_twice_does_not_double_the_quantities(): void
    {
        $user = User::factory()->create();
        $guest = Cart::factory()->create(['session_id' => 'guest-session']);
        $item = CartItem::factory()->create(['cart_id' => $guest->id, 'quantity' => '2.00']);

        $this->merge('guest-session', $user);
        $this->merge('guest-session', $user);

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame([$item->product_id => '2.00'], $this->lines($cart));
    }
}
