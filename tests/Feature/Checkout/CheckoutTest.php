<?php

namespace Tests\Feature\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Enums\ShippingMethod;
use App\Mail\OrderPlaced;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = [
        'street' => 'Av. Mate de Luna',
        'number' => '1234',
        'city' => 'San Miguel de Tucumán',
        'province' => 'Tucumán',
        'postal_code' => '4000',
        'reference' => 'Portón negro',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'shop.shipping.zones' => ['Tucumán' => '5000', 'Salta' => null],
            'shop.pickup.enabled' => false,
        ]);
    }

    /**
     * A customer with a product in their cart.
     */
    private function customerWithCart(?User $user = null, ?Product $product = null, string $quantity = '2.00'): User
    {
        $user ??= User::factory()->create();
        $product ??= Product::factory()->create(['list_price' => '1000.00', 'stock' => '10.00']);

        Cart::factory()->forUser($user)->create()->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);

        return $user;
    }

    /**
     * The session after the first two steps.
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function checkoutSession(array $address = self::ADDRESS): array
    {
        return ['checkout' => [
            'delivery' => ['shipping_method' => 'delivery', ...$address],
            'payment' => ['payment_method' => 'bank_transfer', 'notes' => 'Después de las 17'],
        ]];
    }

    public function test_an_empty_cart_goes_back_to_the_cart(): void
    {
        $this->get(route('checkout.delivery'))->assertRedirect(route('cart.index'));
        $this->get(route('checkout.confirm'))->assertRedirect(route('cart.index'));
    }

    public function test_step_one_offers_only_shipping_to_the_noa(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)->get(route('checkout.delivery'))
            ->assertOk()
            ->assertSee('¿Dónde te lo enviamos?')
            ->assertSee('Tucumán · envío '.money('5000'))
            ->assertSee('<option value="Salta"', false)
            ->assertDontSee('Retiro en el local');
    }

    public function test_step_one_is_filled_in_with_the_last_address(): void
    {
        $user = $this->customerWithCart();
        Address::factory()->for($user)->create(['street' => 'Calle Vieja']);
        Address::factory()->for($user)->create(['street' => 'Calle Nueva']);

        $this->actingAs($user)->get(route('checkout.delivery'))->assertSee('value="Calle Nueva"', false);
    }

    public function test_step_one_validates_the_address(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)
            ->from(route('checkout.delivery'))
            ->post(route('checkout.delivery.store'), [...self::ADDRESS, 'province' => 'Córdoba', 'postal_code' => '40', 'street' => ''])
            ->assertRedirect(route('checkout.delivery'))
            ->assertSessionHasErrors(['province', 'postal_code', 'street']);
    }

    public function test_pickup_is_not_offered_while_the_store_is_online_only(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)
            ->post(route('checkout.delivery.store'), ['shipping_method' => 'pickup'])
            ->assertSessionHasErrors('shipping_method');
    }

    public function test_step_one_saves_and_goes_to_the_payment(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)
            ->post(route('checkout.delivery.store'), [...self::ADDRESS, 'postal_code' => 't4000abc'])
            ->assertRedirect(route('checkout.payment'))
            ->assertSessionHas('checkout.delivery.postal_code', 'T4000ABC');
    }

    public function test_the_payment_step_needs_the_delivery_step(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)->get(route('checkout.payment'))->assertRedirect(route('checkout.delivery'));
    }

    public function test_only_bank_transfer_is_offered_for_now(): void
    {
        $user = $this->customerWithCart();
        $session = ['checkout' => ['delivery' => ['shipping_method' => 'delivery', ...self::ADDRESS]]];

        $this->actingAs($user)->withSession($session)->get(route('checkout.payment'))
            ->assertOk()
            ->assertSee('Transferencia bancaria')
            ->assertDontSee('Efectivo al retirar');

        foreach (['cash_on_pickup', 'mercado_pago'] as $method) {
            $this->actingAs($user)->withSession($session)
                ->post(route('checkout.payment.store'), ['payment_method' => $method])
                ->assertSessionHasErrors('payment_method');
        }

        $this->actingAs($user)->withSession($session)
            ->post(route('checkout.payment.store'), ['payment_method' => 'bank_transfer'])
            ->assertRedirect(route('checkout.confirm'));
    }

    public function test_a_guest_reaches_the_confirmation_and_is_asked_to_sign_in_there(): void
    {
        // The guest's browser session, by its cookie.
        $sessionId = str_repeat('g', 40);
        $this->withCookie(config('session.cookie'), $sessionId);

        $this->get(route('checkout.delivery'))->assertRedirect(route('cart.index'));

        Cart::factory()->create(['session_id' => $sessionId])->items()->create([
            'product_id' => Product::factory()->create(['stock' => '10.00'])->id,
            'quantity' => '1.00',
        ]);

        $this->get(route('checkout.delivery'))->assertOk();

        $this->withSession($this->checkoutSession())->get(route('checkout.confirm'))
            ->assertOk()
            ->assertSee('Ingresar con Google y confirmar')
            ->assertSee(route('auth.google.redirect'), false)
            ->assertDontSee('Confirmar pedido</', false)
            ->assertSessionHas('url.intended', route('checkout.confirm'));
    }

    public function test_placing_the_order_needs_a_signed_in_customer_with_a_phone(): void
    {
        $this->withSession($this->checkoutSession())->post(route('checkout.place'))->assertRedirect(route('login'));

        $user = $this->customerWithCart(User::factory()->create(['phone' => null]));

        $this->actingAs($user)->withSession($this->checkoutSession())
            ->post(route('checkout.place'))
            ->assertRedirect(route('account.phone.edit'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_confirmation_shows_the_totals(): void
    {
        $user = $this->customerWithCart();

        $this->actingAs($user)->withSession($this->checkoutSession())->get(route('checkout.confirm'))
            ->assertOk()
            ->assertSee('Confirmar pedido')
            ->assertSee('Av. Mate de Luna 1234')
            ->assertSee(money('2000'))
            ->assertSee(money('5000'))
            ->assertSee(money('7000'));
    }

    public function test_placing_the_order(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['name' => 'Disyuntor 2x40A', 'sku' => 'DIS-0001', 'list_price' => '1000.00', 'stock' => '10.00']);
        $user = $this->customerWithCart(product: $product, quantity: '3.00');

        $response = $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'));

        $order = Order::query()->sole();
        $response->assertRedirect(route('orders.show', $order))->assertSessionMissing('checkout');

        $this->assertSame(sprintf('EL-%06d', $order->id), $order->number);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertSame(ShippingMethod::Delivery, $order->shipping_method);
        $this->assertSame(PaymentMethod::BankTransfer, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('3000.00', $order->subtotal);
        $this->assertSame('5000.00', $order->shipping_cost);
        $this->assertSame('8000.00', $order->total);
        $this->assertSame('Después de las 17', $order->notes);
        $this->assertSame('Av. Mate de Luna', $order->shipping_street);
        $this->assertSame('Tucumán', $order->shipping_province);

        $item = $order->items()->sole();
        $this->assertSame([$product->id, 'DIS-0001', 'Disyuntor 2x40A', ProductUnit::Unit, '1000.00', '3.00', '3000.00'], [
            $item->product_id, $item->product_sku, $item->product_name, $item->product_unit, $item->unit_price, $item->quantity, $item->subtotal,
        ]);

        $this->assertSame('7.00', $product->fresh()->stock);
        $this->assertSame([OrderStatus::Received], $order->statusHistory()->get()->pluck('status')->all());
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'street' => 'Av. Mate de Luna', 'postal_code' => '4000']);

        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail) => $mail->hasTo($user->email) && $mail->order->is($order));
    }

    public function test_the_same_address_is_not_saved_twice(): void
    {
        Mail::fake();
        $user = $this->customerWithCart();
        Address::factory()->for($user)->create(self::ADDRESS);

        $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'));

        $this->assertSame(1, $user->addresses()->count());
    }

    public function test_a_zone_without_a_fixed_cost_leaves_the_shipping_to_be_agreed(): void
    {
        Mail::fake();
        $user = $this->customerWithCart();
        $address = [...self::ADDRESS, 'city' => 'Salta', 'province' => 'Salta', 'postal_code' => '4400'];

        $this->actingAs($user)->withSession($this->checkoutSession($address))->get(route('checkout.confirm'))
            ->assertSee('A coordinar')
            ->assertSee('Total (sin envío)');

        $this->actingAs($user)->withSession($this->checkoutSession($address))->post(route('checkout.place'));

        $order = Order::query()->sole();
        $this->assertNull($order->shipping_cost);
        $this->assertSame('2000.00', $order->total);
    }

    public function test_approved_professionals_pay_the_professional_price(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['list_price' => '1000.00', 'professional_price' => '850.00', 'stock' => '10.00']);
        $user = $this->customerWithCart(User::factory()->professional()->create(), $product);

        $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'));

        $this->assertSame('850.00', Order::query()->sole()->items()->sole()->unit_price);
    }

    public function test_prices_sent_by_the_browser_are_ignored(): void
    {
        Mail::fake();
        $user = $this->customerWithCart();

        $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'), [
            'unit_price' => '1.00', 'total' => '1.00', 'subtotal' => '1.00', 'shipping_cost' => '0',
        ]);

        $this->assertSame('7000.00', Order::query()->sole()->total);
    }

    public function test_without_enough_stock_the_order_is_not_placed(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['stock' => '10.00']);
        $user = $this->customerWithCart(product: $product, quantity: '5.00');

        // Someone else bought most of it in the meantime.
        $product->update(['stock' => '3.00']);

        $this->actingAs($user)->withSession($this->checkoutSession())
            ->post(route('checkout.place'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('3.00', $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 1);
        Mail::assertNothingQueued();
    }

    public function test_the_products_are_locked_while_the_stock_is_taken(): void
    {
        Mail::fake();
        $user = $this->customerWithCart();
        DB::enableQueryLog();

        $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'));

        $locked = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn (string $sql) => str_contains($sql, 'from `products`') && str_ends_with($sql, 'for update'));

        $this->assertCount(1, $locked);
    }

    public function test_the_order_number_follows_the_id(): void
    {
        Mail::fake();
        Order::factory()->create(['number' => 'EL-000041']);
        $user = $this->customerWithCart();

        $this->actingAs($user)->withSession($this->checkoutSession())->post(route('checkout.place'));

        $order = Order::query()->latest('id')->first();
        $this->assertSame(sprintf('EL-%06d', $order->id), $order->number);
    }
}
