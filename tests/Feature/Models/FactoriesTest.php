<?php

namespace Tests\Feature\Models;

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
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\Cuit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every factory must produce rows the database accepts (constraints included).
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string}>
     */
    public static function models(): array
    {
        return [
            'User' => [User::class],
            'Address' => [Address::class],
            'FiscalData' => [FiscalData::class],
            'Category' => [Category::class],
            'Brand' => [Brand::class],
            'Product' => [Product::class],
            'ProductImage' => [ProductImage::class],
            'ProductAttribute' => [ProductAttribute::class],
            'Cart' => [Cart::class],
            'CartItem' => [CartItem::class],
            'Order' => [Order::class],
            'OrderItem' => [OrderItem::class],
            'OrderStatusHistory' => [OrderStatusHistory::class],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('models')]
    public function test_the_default_factory_creates_a_valid_row(string $model): void
    {
        $record = $model::factory()->create();

        $this->assertTrue($record->exists);
        $this->assertDatabaseHas($record->getTable(), [$record->getKeyName() => $record->getKey()]);
    }

    public function test_an_order_with_items_has_consistent_totals(): void
    {
        $order = Order::factory()->withItems(3)->create()->fresh(['items']);

        $subtotal = $order->items->reduce(fn (string $sum, OrderItem $item) => bcadd($sum, $item->subtotal, 2), '0.00');

        $this->assertCount(3, $order->items);
        $this->assertSame($subtotal, $order->subtotal);
        $this->assertSame($subtotal, $order->total);
        $this->assertGreaterThan(0, bccomp($order->total, '0', 2));
    }

    public function test_an_order_item_for_a_product_copies_and_multiplies_its_data(): void
    {
        $product = Product::factory()->create(['list_price' => '1250.50']);

        $item = OrderItem::factory()->forProduct($product, '2.00')->create();

        $this->assertSame($product->sku, $item->product_sku);
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame('1250.50', $item->unit_price);
        $this->assertSame('2501.00', $item->subtotal);
    }

    public function test_generated_cuits_have_a_valid_check_digit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue(Cuit::isValid(FiscalData::factory()->make()->cuit));
        }
    }
}
