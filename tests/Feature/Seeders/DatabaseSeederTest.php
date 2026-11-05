<?php

namespace Tests\Feature\Seeders;

use App\Enums\ProductUnit;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FiscalData;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Support\Cuit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_it_creates_the_six_main_categories_with_subcategories(): void
    {
        $roots = Category::query()->roots()->ordered()->get();

        $this->assertSame(
            ['Cables', 'Protección eléctrica', 'Mecanismos', 'Iluminación LED', 'Canalización', 'Herramientas'],
            $roots->pluck('name')->all(),
        );

        foreach ($roots as $root) {
            $this->assertGreaterThanOrEqual(2, Category::query()->where('parent_id', $root->id)->count(), "{$root->name} has no subcategories.");
        }
    }

    public function test_products_hang_from_subcategories_never_from_a_main_category(): void
    {
        $parentIds = Category::query()->whereNotNull('parent_id')->pluck('parent_id')->unique();

        $this->assertSame(0, Product::query()->whereIn('category_id', $parentIds)->count());
        $this->assertSame(Product::query()->count(), Product::query()->whereNotIn('category_id', $parentIds)->count());
    }

    public function test_it_creates_around_forty_realistic_products(): void
    {
        $this->assertGreaterThanOrEqual(40, Product::query()->count());
        $this->assertLessThanOrEqual(50, Product::query()->count());

        Product::query()->each(function (Product $product) {
            $this->assertTrue($product->is_active);
            $this->assertGreaterThan(0, bccomp($product->list_price, '0', 2), "{$product->sku} has no price.");
            $this->assertNotNull($product->manufacturer_code, "{$product->sku} has no manufacturer code.");
            $this->assertNotSame('', trim($product->description), "{$product->sku} has no description.");

            if ($product->professional_price !== null) {
                $this->assertLessThanOrEqual(0, bccomp($product->professional_price, $product->list_price, 2), "{$product->sku} costs more for professionals.");
            }
        });
    }

    public function test_every_category_of_products_is_represented(): void
    {
        foreach (Category::query()->roots()->get() as $root) {
            $count = Product::query()->whereIn('category_id', $root->descendantIds())->count();

            $this->assertGreaterThanOrEqual(5, $count, "{$root->name} has too few products.");
        }
    }

    public function test_every_product_has_a_technical_sheet_with_the_filterable_attributes(): void
    {
        $this->assertSame(0, Product::query()->whereDoesntHave('specs')->count());

        $names = ProductAttribute::query()->distinct()->pluck('name');

        foreach (['Sección', 'Amperaje', 'Color', 'Largo'] as $expected) {
            $this->assertTrue($names->contains($expected), "No product has the attribute {$expected}.");
        }
    }

    public function test_it_covers_the_cases_the_catalog_has_to_handle(): void
    {
        $this->assertGreaterThan(0, Product::query()->where('stock', 0)->count(), 'No sold-out product.');
        $this->assertGreaterThan(0, Product::query()->whereNull('professional_price')->count(), 'No product without professional price.');
        $this->assertGreaterThan(0, Product::query()->whereNull('brand_id')->count(), 'No product without brand.');
        $this->assertGreaterThan(0, Brand::query()->count());

        foreach (ProductUnit::cases() as $unit) {
            $this->assertGreaterThan(0, Product::query()->where('unit', $unit->value)->count(), "No product sold by {$unit->label()}.");
        }
    }

    public function test_it_puts_some_products_on_offer_with_a_believable_discount(): void
    {
        $offers = Product::query()->onSale()->get();

        $this->assertGreaterThanOrEqual(6, $offers->count());
        $this->assertLessThanOrEqual(12, $offers->count());
        $this->assertSame($offers->count(), Product::query()->whereNotNull('compare_at_price')->count(), 'A previous price that is not higher than the price is not an offer.');

        foreach ($offers as $product) {
            $discount = $product->priceDisplayFor(null)->discountPercent;

            $this->assertGreaterThanOrEqual(5, $discount, "{$product->sku} has a negligible discount.");
            $this->assertLessThanOrEqual(40, $discount, "{$product->sku} has an unbelievable discount.");
        }
    }

    public function test_the_products_were_not_all_created_on_the_same_day(): void
    {
        $this->assertGreaterThan(1, Product::query()->distinct()->count('created_at'), 'The "Novedades" order would mean nothing.');
    }

    public function test_it_creates_the_admin_and_sample_customers(): void
    {
        $admin = User::query()->where('email', config('shop.admin_email'))->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(1, User::query()->where('role', UserRole::Sales->value)->count());
        $this->assertSame(1, User::query()->where('role', UserRole::Warehouse->value)->count());
        $this->assertSame(1, User::query()->where('professional_status', 'approved')->count());
        $this->assertSame(1, User::query()->where('professional_status', 'pending')->count());

        FiscalData::query()->each(fn (FiscalData $fiscal) => $this->assertTrue(Cuit::isValid($fiscal->cuit)));
    }

    public function test_running_it_again_does_not_duplicate_anything(): void
    {
        $counts = fn () => [
            Category::query()->count(),
            Brand::query()->count(),
            Product::query()->count(),
            ProductAttribute::query()->count(),
            User::query()->count(),
        ];

        $before = $counts();

        $this->seed();

        $this->assertSame($before, $counts());
    }
}
