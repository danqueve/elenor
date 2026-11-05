<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\PriceDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{?string, bool}>
     */
    public static function comparePrices(): array
    {
        return [
            'higher than the price: on sale' => ['1250.00', true],
            'a cent higher still counts' => ['1000.01', true],
            'equal: not on sale' => ['1000.00', false],
            'lower: not on sale' => ['800.00', false],
            'no previous price' => [null, false],
        ];
    }

    #[DataProvider('comparePrices')]
    public function test_a_product_is_on_sale_when_the_previous_price_is_higher(?string $compareAt, bool $expected): void
    {
        $product = new Product(['list_price' => '1000.00', 'compare_at_price' => $compareAt]);

        $this->assertSame($expected, $product->isOnSale());
    }

    public function test_the_on_sale_scope_matches_the_same_rule(): void
    {
        $sale = Product::factory()->onSale()->create();
        Product::factory()->create(['compare_at_price' => null]);
        Product::factory()->create(['list_price' => '500.00', 'compare_at_price' => '500.00']);

        $this->assertSame([$sale->id], Product::query()->onSale()->pluck('id')->all());
    }

    public function test_the_factory_state_makes_the_previous_price_25_percent_higher(): void
    {
        $product = Product::factory()->onSale()->create(['list_price' => '1000.00'])->fresh();

        $this->assertSame('1250.00', $product->compare_at_price);
        $this->assertSame(20, $product->priceDisplayFor(null)->discountPercent);
    }

    public function test_an_offer_is_shown_with_the_previous_price_and_the_discount(): void
    {
        $product = new Product(['list_price' => '9800.00', 'professional_price' => '8700.00', 'compare_at_price' => '11500.00']);

        $display = $product->priceDisplayFor(null);

        $this->assertEquals(new PriceDisplay('9800.00', '11500.00', 15), $display);
        $this->assertEquals($display, $product->priceDisplayFor(User::factory()->make()));
    }

    public function test_the_discount_is_at_least_one_percent_when_there_is_an_offer(): void
    {
        $product = new Product(['list_price' => '1000.00', 'compare_at_price' => '1000.50']);

        $this->assertSame(1, $product->priceDisplayFor(null)->discountPercent);
    }

    public function test_professionals_see_their_price_and_no_discount(): void
    {
        $product = new Product(['list_price' => '9800.00', 'professional_price' => '8700.00', 'compare_at_price' => '11500.00']);

        $display = $product->priceDisplayFor(User::factory()->professional()->make());

        $this->assertEquals(new PriceDisplay('8700.00', isProfessional: true), $display);
    }

    public function test_a_professional_without_a_professional_price_sees_the_offer(): void
    {
        $product = new Product(['list_price' => '9800.00', 'professional_price' => null, 'compare_at_price' => '11500.00']);

        $display = $product->priceDisplayFor(User::factory()->professional()->make());

        $this->assertSame('9800.00', $display->price);
        $this->assertSame('11500.00', $display->wasPrice);
        $this->assertFalse($display->isProfessional);
    }

    public function test_a_product_that_is_not_on_sale_shows_just_the_price(): void
    {
        $product = new Product(['list_price' => '9800.00', 'compare_at_price' => null]);

        $this->assertEquals(new PriceDisplay('9800.00'), $product->priceDisplayFor(null));
    }

    public function test_the_cover_is_the_first_photo_in_the_same_query(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->for($product)->create(['path' => 'products/second.jpg', 'sort_order' => 2]);
        ProductImage::factory()->for($product)->create(['path' => 'products/first.jpg', 'sort_order' => 1]);
        $withoutPhotos = Product::factory()->create();

        $listed = Product::query()->withFirstImage()->get()->keyBy('id');

        $this->assertSame('/storage/products/first.jpg', $listed[$product->id]->coverImageUrl());
        $this->assertNull($listed[$withoutPhotos->id]->coverImageUrl());
    }

    public function test_the_cover_can_also_come_from_the_loaded_images(): void
    {
        $product = Product::factory()->withImages(2)->create();

        $loaded = Product::query()->with('images')->findOrFail($product->id);
        $notLoaded = Product::query()->findOrFail($product->id);

        $this->assertNotNull($loaded->coverImageUrl());
        $this->assertNull($notLoaded->coverImageUrl());
    }

    public function test_a_product_is_found_by_its_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'cable-unipolar-2-5']);

        $this->assertSame('cable-unipolar-2-5', $product->getRouteKey());
        $this->assertStringEndsWith('/producto/cable-unipolar-2-5', route('products.show', $product));
    }
}
