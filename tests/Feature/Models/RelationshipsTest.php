<?php

namespace Tests\Feature\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Enums\ShippingMethod;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\FiscalData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Models are strict outside production (lazy loading throws), so every
 * relation is loaded explicitly here, as the application must do.
 */
class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_has_addresses_fiscal_data_a_cart_and_orders(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->for($user)->create();
        FiscalData::factory()->for($user)->create();
        Cart::factory()->forUser($user)->create();
        Order::factory()->count(3)->for($user)->create();

        $user = $user->fresh(['addresses', 'fiscalData', 'cart', 'orders']);

        $this->assertCount(2, $user->addresses);
        $this->assertInstanceOf(FiscalData::class, $user->fiscalData);
        $this->assertInstanceOf(Cart::class, $user->cart);
        $this->assertCount(3, $user->orders);
    }

    public function test_categories_form_a_tree(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        Product::factory()->create(['category_id' => $child->id]);

        $root = $root->fresh(['children']);
        $child = $child->fresh(['parent', 'products']);

        $this->assertCount(1, $root->children);
        $this->assertTrue($root->children->first()->is($child));
        $this->assertTrue($child->parent->is($root));
        $this->assertCount(1, $child->products);
    }

    public function test_a_product_has_a_category_a_brand_images_and_a_technical_sheet(): void
    {
        $product = Product::factory()->withSpecs(['Sección' => '2,5 mm²', 'Color' => 'Rojo'])->create();
        ProductImage::factory()->for($product)->create(['sort_order' => 2]);
        ProductImage::factory()->for($product)->create(['sort_order' => 0]);
        ProductImage::factory()->for($product)->create(['sort_order' => 1]);

        $product = $product->fresh(['category', 'brand', 'images', 'specs']);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertSame([0, 1, 2], $product->images->pluck('sort_order')->all());
        $this->assertSame(['Sección' => '2,5 mm²', 'Color' => 'Rojo'], $product->specs->pluck('value', 'name')->all());
    }

    public function test_a_product_without_a_brand_is_allowed(): void
    {
        $product = Product::factory()->create(['brand_id' => null]);

        $this->assertNull($product->fresh(['brand'])->brand);
    }

    public function test_a_brand_has_products(): void
    {
        $brand = Brand::factory()->create();
        Product::factory()->count(2)->create(['brand_id' => $brand->id]);

        $this->assertCount(2, $brand->fresh(['products'])->products);
    }

    public function test_a_cart_has_items_that_point_to_products(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->for($cart)->create();

        $cart = $cart->fresh(['items.product']);

        $this->assertCount(2, $cart->items);
        $this->assertInstanceOf(Product::class, $cart->items->first()->product);
    }

    public function test_an_order_has_items_and_a_chronological_status_history(): void
    {
        $order = Order::factory()->withItems(2)->create();
        $staff = User::factory()->warehouse()->create();

        foreach ([OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing] as $status) {
            OrderStatusHistory::factory()->for($order)->by($staff)->create(['status' => $status]);
        }

        $order = $order->fresh(['items', 'statusHistory.user']);

        $this->assertCount(2, $order->items);
        $this->assertSame(
            [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing],
            $order->statusHistory->pluck('status')->all(),
        );
        $this->assertTrue($order->statusHistory->first()->user->is($staff));
    }

    public function test_an_order_item_can_be_traced_back_to_its_product(): void
    {
        $product = Product::factory()->create();
        $item = OrderItem::factory()->forProduct($product)->create();

        $this->assertTrue($item->fresh(['product'])->product->is($product));
    }

    public function test_enums_and_decimals_are_cast(): void
    {
        $order = Order::factory()->create(['subtotal' => '1234.5', 'total' => '1234.5'])->fresh();
        $product = Product::factory()->soldBy(ProductUnit::Meter)->create(['list_price' => '99.9', 'stock' => '12.5'])->fresh();

        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertSame(ShippingMethod::Delivery, $order->shipping_method);
        $this->assertSame(PaymentMethod::BankTransfer, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('1234.50', $order->subtotal);
        $this->assertSame(ProductUnit::Meter, $product->unit);
        $this->assertSame('99.90', $product->list_price);
        $this->assertSame('12.50', $product->stock);
        $this->assertTrue($product->is_active);
    }

    public function test_a_pickup_order_has_no_address_snapshot(): void
    {
        $order = Order::factory()->pickup()->create()->fresh();

        $this->assertSame(ShippingMethod::Pickup, $order->shipping_method);
        $this->assertNull($order->shipping_street);
        $this->assertNull($order->shipping_city);
    }

    public function test_the_fiscal_data_cuit_is_shown_with_dashes(): void
    {
        $fiscal = FiscalData::factory()->create(['cuit' => '20123456786']);

        $this->assertSame('20-12345678-6', $fiscal->formatted_cuit);
    }
}
