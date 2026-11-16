<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.bank' => ['holder' => 'Elenor S.R.L.', 'alias' => 'ELENOR.PRUEBA', 'cbu' => null]]);
    }

    public function test_the_list_shows_only_the_customers_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->withItems(2)->create(['number' => 'EL-000001']);
        Order::factory()->withItems()->create(['number' => 'EL-000002']);

        $this->actingAs($user)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pedido EL-000001')
            ->assertSee('2 productos')
            ->assertDontSee('EL-000002');
    }

    public function test_without_orders_the_list_invites_to_buy(): void
    {
        $this->actingAs(User::factory()->create())->get(route('orders.index'))->assertSee('Todavía no hiciste pedidos');
    }

    public function test_the_detail_shows_items_totals_and_timeline(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['number' => 'EL-000007', 'status' => OrderStatus::Preparing]);
        OrderItem::factory()->for($order)->create(['product_name' => 'Cable unipolar 2,5 mm²', 'quantity' => '100.00', 'product_unit' => 'm']);

        foreach ([OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing] as $status) {
            $order->statusHistory()->create(['status' => $status]);
        }

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Pedido EL-000007')
            ->assertSee('Cable unipolar 2,5 mm²')
            ->assertSee('100 m')
            ->assertSeeInOrder(['Recibido', '(hecho)', 'Confirmado', '(hecho)', 'En preparación', '(hecho)', 'Enviado', '(pendiente)', 'Entregado', '(pendiente)'])
            ->assertSee('Repetir pedido');
    }

    public function test_the_bank_details_are_shown_while_the_transfer_is_pending(): void
    {
        $user = User::factory()->create();
        $pending = Order::factory()->for($user)->create();
        $paid = Order::factory()->for($user)->create(['payment_status' => PaymentStatus::Paid]);

        $this->actingAs($user)->get(route('orders.show', $pending))->assertSee('Datos para la transferencia')->assertSee('ELENOR.PRUEBA');
        $this->actingAs($user)->get(route('orders.show', $paid))->assertDontSee('Datos para la transferencia');
    }

    public function test_other_customers_orders_do_not_exist(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('orders.reorder', $order))->assertNotFound();
    }

    public function test_orders_need_a_signed_in_customer(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
    }

    public function test_reorder_puts_the_available_products_back_in_the_cart(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $available = Product::factory()->create(['stock' => '50.00']);
        $short = Product::factory()->create(['stock' => '2.00']);
        $hidden = Product::factory()->inactive()->create(['name' => 'Producto discontinuado']);
        $deleted = Product::factory()->create();

        OrderItem::factory()->for($order)->forProduct($available, '4.00')->create();
        OrderItem::factory()->for($order)->forProduct($short, '5.00')->create();
        OrderItem::factory()->for($order)->forProduct($hidden, '1.00')->create();
        OrderItem::factory()->for($order)->forProduct($deleted, '1.00')->create(['product_name' => 'Producto borrado']);
        $deleted->delete();

        $this->actingAs($user)->post(route('orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Producto discontinuado') && str_contains($status, 'Producto borrado'));

        $lines = Cart::query()->where('user_id', $user->id)->sole()->items()->pluck('quantity', 'product_id')->all();

        $this->assertSame([$available->id => '4.00', $short->id => '2.00'], $lines);
    }

    public function test_the_confirmation_email(): void
    {
        $user = User::factory()->create(['name' => 'Marcela Gómez']);
        $order = Order::factory()->for($user)->withItems(1)->create(['number' => 'EL-000099', 'shipping_cost' => null]);

        $mail = new OrderPlaced($order);

        $mail->assertHasSubject('Recibimos tu pedido EL-000099');
        $mail->assertSeeInHtml('¡Gracias por tu compra, Marcela!');
        $mail->assertSeeInHtml('A coordinar');
        $mail->assertSeeInHtml('ELENOR.PRUEBA');
        $mail->assertSeeInHtml(route('orders.show', $order));
    }
}
