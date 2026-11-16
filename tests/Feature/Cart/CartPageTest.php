<?php

namespace Tests\Feature\Cart;

use App\Livewire\Cart\AddToCart;
use App\Livewire\Cart\CartPage;
use App\Livewire\CartCounter;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    private function addToCart(Product $product, string $quantity): void
    {
        app(CartService::class)->add($product, $quantity);
    }

    public function test_an_empty_cart_invites_to_the_catalog(): void
    {
        $this->get('/carrito')
            ->assertOk()
            ->assertSee('Tu carrito está vacío')
            ->assertSee(route('catalog.index'), false);
    }

    public function test_it_lists_the_products_with_the_server_prices(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => 'Térmica bipolar 2x20A', 'list_price' => '15000.00', 'stock' => '10.00']);
        $this->addToCart($product, '2');

        $this->get('/carrito')
            ->assertOk()
            ->assertSee('Térmica bipolar 2x20A')
            ->assertSee(money('30000.00'))
            ->assertSee(route('checkout.delivery'), false);
    }

    public function test_changing_a_quantity_saves_it(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['list_price' => '100.00', 'stock' => '10.00']);
        $this->addToCart($product, '1');

        Livewire::test(CartPage::class)
            ->set("quantities.{$product->id}", '4')
            ->assertHasNoErrors()
            ->assertDispatched('cart-updated')
            ->assertSee(money('400.00'));

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '4.00']);
    }

    public function test_a_quantity_above_the_stock_shows_an_error_and_keeps_the_saved_one(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '3.00']);
        $this->addToCart($product, '1');

        Livewire::test(CartPage::class)
            ->set("quantities.{$product->id}", '9')
            ->assertHasErrors("quantities.{$product->id}")
            ->assertSee('No tenemos stock para esa cantidad');

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '1.00']);
    }

    public function test_plus_minus_and_remove(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);
        $this->addToCart($product, '2');

        $component = Livewire::test(CartPage::class)->call('increment', $product->id);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '3.00']);

        $component->call('decrement', $product->id);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '2.00']);

        $component->call('remove', $product->id)->assertSee('Tu carrito está vacío');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_minus_button_never_goes_below_the_minimum(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);
        $this->addToCart($product, '1');

        Livewire::test(CartPage::class)->call('decrement', $product->id);

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '1.00']);
    }

    public function test_the_checkout_is_blocked_while_something_cannot_be_bought(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);
        $this->addToCart($product, '5');
        $product->update(['stock' => '0.00']);

        $this->get('/carrito')
            ->assertSee('Se quedó sin stock')
            ->assertDontSee(route('checkout.delivery'), false);
    }

    public function test_the_header_counter_counts_the_products(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addToCart(Product::factory()->create(['stock' => '10.00']), '3');
        $this->addToCart(Product::factory()->create(['stock' => '10.00']), '1');

        Livewire::test(CartCounter::class)->assertSee('2 productos');
    }

    public function test_add_to_cart_adds_the_typed_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '10.00']);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('increment')
            ->assertSet('quantity', '2')
            ->call('add')
            ->assertHasNoErrors()
            ->assertDispatched('cart-updated')
            ->assertSee('Listo: tenés 2 u. en el carrito');

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => '2.00']);
    }

    public function test_add_to_cart_shows_why_it_could_not_add(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => '2.00']);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->set('quantity', '5')
            ->call('add')
            ->assertHasErrors('quantity')
            ->assertNotDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_product_cannot_be_changed_from_the_browser(): void
    {
        $product = Product::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(AddToCart::class, ['product' => $product])->set('productId', Product::factory()->create()->id);
    }

    public function test_the_product_page_offers_add_to_cart_only_with_stock(): void
    {
        config(['shop.contact.whatsapp' => '5493810000000']);
        $inStock = Product::factory()->create(['stock' => '5.00']);
        $outOfStock = Product::factory()->outOfStock()->create();

        $this->get(route('products.show', $inStock))->assertSee('Agregar al carrito')->assertSee('Consultar por WhatsApp');
        $this->get(route('products.show', $outOfStock))->assertDontSee('Agregar al carrito')->assertSee('Consultar por WhatsApp');
    }
}
