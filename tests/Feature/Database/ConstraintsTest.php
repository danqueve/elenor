<?php

namespace Tests\Feature\Database;

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
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The database is the last line of defense: these rules hold even if the
 * application code has a bug.
 */
class ConstraintsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Assert that MySQL rejects the statement, naming the expected constraint
     * or index in its error.
     */
    private function assertRejected(Closure $statement, string $constraint): void
    {
        try {
            $statement();
        } catch (QueryException $exception) {
            $this->assertStringContainsString($constraint, $exception->getMessage());

            return;
        }

        $this->fail("MySQL accepted a row that violates {$constraint}.");
    }

    public function test_stock_and_prices_can_never_be_negative(): void
    {
        $product = Product::factory()->create(['stock' => '5.00']);

        // Straight to the table: a model that failed to save would keep the
        // invalid value as a pending change and contaminate the next case.
        $update = fn (array $values) => Product::query()->whereKey($product->id)->update($values);

        $this->assertRejected(fn () => $update(['stock' => '-0.01']), 'products_stock_non_negative');
        $this->assertRejected(fn () => $update(['list_price' => '-1.00']), 'products_list_price_non_negative');
        $this->assertRejected(fn () => $update(['professional_price' => '-1.00']), 'products_professional_price_non_negative');

        $this->assertSame('5.00', $product->fresh()->stock);
    }

    public function test_the_professional_price_is_optional_and_stock_can_reach_zero(): void
    {
        $product = Product::factory()->withoutProfessionalPrice()->outOfStock()->create();

        $this->assertNull($product->fresh()->professional_price);
        $this->assertSame('0.00', $product->fresh()->stock);
    }

    public function test_quantities_must_be_positive(): void
    {
        $this->assertRejected(fn () => CartItem::factory()->create(['quantity' => '0.00']), 'cart_items_quantity_positive');
        $this->assertRejected(fn () => CartItem::factory()->create(['quantity' => '-1.00']), 'cart_items_quantity_positive');
        $this->assertRejected(fn () => OrderItem::factory()->create(['quantity' => '0.00']), 'order_items_quantity_positive');
    }

    public function test_order_amounts_can_never_be_negative(): void
    {
        $this->assertRejected(fn () => Order::factory()->create(['shipping_cost' => '-1.00']), 'orders_shipping_cost_non_negative');
        $this->assertRejected(fn () => Order::factory()->create(['subtotal' => '-1.00']), 'orders_subtotal_non_negative');
        $this->assertRejected(fn () => Order::factory()->create(['total' => '-1.00']), 'orders_total_non_negative');
        $this->assertRejected(fn () => OrderItem::factory()->create(['unit_price' => '-1.00']), 'order_items_unit_price_non_negative');
        $this->assertRejected(fn () => OrderItem::factory()->create(['subtotal' => '-1.00']), 'order_items_subtotal_non_negative');
    }

    public function test_unique_values_are_enforced(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->withSpecs(['Color' => 'Rojo'])->create();
        $order = Order::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $this->assertRejected(fn () => User::factory()->create(['email' => $user->email]), 'users_email_unique');
        $this->assertRejected(fn () => User::factory()->create(['google_id' => $user->google_id]), 'users_google_id_unique');
        $this->assertRejected(fn () => Product::factory()->create(['sku' => $product->sku]), 'products_sku_unique');
        $this->assertRejected(fn () => Product::factory()->create(['slug' => $product->slug]), 'products_slug_unique');
        $this->assertRejected(fn () => $product->specs()->create(['name' => 'Color', 'value' => 'Azul']), 'product_attributes_product_id_name_unique');
        $this->assertRejected(fn () => Order::factory()->create(['number' => $order->number]), 'orders_number_unique');
        $this->assertRejected(fn () => Category::factory()->create(['slug' => $category->slug]), 'categories_slug_unique');
        $this->assertRejected(fn () => Brand::factory()->create(['name' => $brand->name]), 'brands_name_unique');
        $this->assertRejected(fn () => FiscalData::factory()->count(2)->for($user)->create(), 'fiscal_data_user_id_unique');
    }

    public function test_several_users_without_a_google_id_are_allowed(): void
    {
        User::factory()->count(2)->create(['google_id' => null]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_a_cart_belongs_to_one_user_or_one_session_and_has_each_product_once(): void
    {
        $user = User::factory()->create();
        $userCart = Cart::factory()->forUser($user)->create();
        $guestCart = Cart::factory()->create();
        $product = Product::factory()->create();

        $this->assertRejected(fn () => Cart::factory()->forUser($user)->create(), 'carts_user_id_unique');
        $this->assertRejected(fn () => Cart::factory()->create(['session_id' => $guestCart->session_id]), 'carts_session_id_unique');

        CartItem::factory()->create(['cart_id' => $userCart->id, 'product_id' => $product->id]);

        $this->assertRejected(
            fn () => CartItem::factory()->create(['cart_id' => $userCart->id, 'product_id' => $product->id]),
            'cart_items_cart_id_product_id_unique',
        );
    }

    /**
     * The key rule for orders: what the customer bought stays as it was.
     */
    public function test_deleting_a_product_keeps_the_frozen_data_of_past_orders(): void
    {
        $product = Product::factory()->create(['sku' => 'CAB-0099', 'list_price' => '1000.00']);
        $item = OrderItem::factory()->forProduct($product, '3.00')->create();

        $product->delete();

        $item = OrderItem::query()->find($item->id);

        $this->assertNull($item->product_id);
        $this->assertSame('CAB-0099', $item->product_sku);
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame('1000.00', $item->unit_price);
        $this->assertSame('3.00', $item->quantity);
        $this->assertSame('3000.00', $item->subtotal);
    }

    public function test_deleting_a_product_removes_its_images_specs_and_cart_lines(): void
    {
        $product = Product::factory()->withSpecs(['Color' => 'Rojo'])->withImages(2)->create();
        CartItem::factory()->create(['product_id' => $product->id]);

        $product->delete();

        $this->assertDatabaseCount('product_images', 0);
        $this->assertDatabaseCount('product_attributes', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_deleting_an_order_removes_its_items_and_history(): void
    {
        $order = Order::factory()->withItems(2)->create();
        OrderStatusHistory::factory()->for($order)->create();

        $order->delete();

        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_status_history', 0);
    }

    public function test_a_user_with_orders_cannot_be_deleted(): void
    {
        $order = Order::factory()->create();
        $customer = User::query()->find($order->user_id);

        $this->assertRejected(fn () => $customer->delete(), 'foreign key constraint fails');
    }

    public function test_deleting_a_user_removes_their_cart_but_status_history_only_forgets_the_author(): void
    {
        $user = User::factory()->create();
        Cart::factory()->forUser($user)->create();
        $staff = User::factory()->sales()->create();
        $change = OrderStatusHistory::factory()->by($staff)->create();

        $user->delete();
        $staff->delete();

        $this->assertDatabaseCount('carts', 0);
        $this->assertNull(OrderStatusHistory::query()->find($change->id)->user_id);
    }

    public function test_categories_in_use_cannot_be_deleted(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();
        $withProduct = Category::factory()->create();
        Product::factory()->create(['category_id' => $withProduct->id]);

        $this->assertRejected(fn () => $parent->delete(), 'foreign key constraint fails');
        $this->assertRejected(fn () => $withProduct->delete(), 'foreign key constraint fails');
    }

    public function test_deleting_a_brand_keeps_its_products(): void
    {
        $product = Product::factory()->create();
        Brand::query()->find($product->brand_id)->delete();

        $this->assertNull($product->fresh()->brand_id);
    }

    public function test_images_are_removed_with_their_product_only(): void
    {
        $kept = Product::factory()->withImages(1)->create();
        $removed = Product::factory()->withImages(1)->create();

        $removed->delete();

        $this->assertSame(1, ProductImage::query()->count());
        $this->assertSame($kept->id, ProductImage::query()->first()->product_id);
    }
}
