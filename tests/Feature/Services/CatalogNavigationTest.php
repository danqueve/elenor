<?php

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CatalogNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CatalogNavigationTest extends TestCase
{
    use RefreshDatabase;

    private Category $cables;

    private Category $unipolares;

    private Category $subterraneos;

    private Category $herramientas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cables = Category::factory()->create(['name' => 'Cables', 'slug' => 'cables', 'sort_order' => 10]);
        $this->herramientas = Category::factory()->create(['name' => 'Herramientas', 'slug' => 'herramientas', 'sort_order' => 20]);
        $this->unipolares = Category::factory()->childOf($this->cables)->create(['name' => 'Unipolares', 'slug' => 'unipolares', 'sort_order' => 10]);
        $this->subterraneos = Category::factory()->childOf($this->cables)->create(['name' => 'Subterráneos', 'slug' => 'subterraneos', 'sort_order' => 20]);

        Product::factory()->count(3)->create(['category_id' => $this->unipolares->id]);
        Product::factory()->count(1)->create(['category_id' => $this->subterraneos->id]);
        Product::factory()->count(2)->create(['category_id' => $this->herramientas->id]);
        Product::factory()->count(5)->inactive()->create(['category_id' => $this->unipolares->id]);
    }

    /**
     * @param  array{items: Collection<int, object{category: Category, total: int}>}  $navigation
     * @return array<string, int>
     */
    private function totals(array $navigation): array
    {
        return $navigation['items']->mapWithKeys(fn ($item) => [$item->category->name => $item->total])->all();
    }

    public function test_without_a_category_it_lists_the_main_ones_counting_everything_below(): void
    {
        $navigation = app(CatalogNavigation::class)->for(null);

        $this->assertSame('Categorías', $navigation['title']);
        $this->assertNull($navigation['parent']);
        // Inactive products are not counted; subcategories add up in their parent.
        $this->assertSame(['Cables' => 4, 'Herramientas' => 2], $this->totals($navigation));
    }

    public function test_a_category_with_subcategories_lists_them(): void
    {
        $navigation = app(CatalogNavigation::class)->for($this->cables);

        $this->assertSame('Cables', $navigation['title']);
        $this->assertTrue($navigation['parent']->is($this->cables));
        $this->assertSame(['Unipolares' => 3, 'Subterráneos' => 1], $this->totals($navigation));
    }

    public function test_a_category_without_subcategories_lists_its_siblings(): void
    {
        $navigation = app(CatalogNavigation::class)->for($this->subterraneos);

        $this->assertSame('Cables', $navigation['title']);
        $this->assertTrue($navigation['parent']->is($this->cables));
        $this->assertSame(['Unipolares' => 3, 'Subterráneos' => 1], $this->totals($navigation));
    }

    public function test_a_main_category_without_subcategories_has_nothing_to_list(): void
    {
        $navigation = app(CatalogNavigation::class)->for($this->herramientas);

        $this->assertSame('Herramientas', $navigation['title']);
        $this->assertTrue($navigation['items']->isEmpty());
    }

    public function test_the_roots_come_with_their_totals_in_display_order(): void
    {
        $roots = app(CatalogNavigation::class)->roots();

        $this->assertSame(['Cables', 'Herramientas'], $roots->pluck('category.name')->all());
        $this->assertSame([4, 2], $roots->pluck('total')->all());
    }

    public function test_an_empty_catalog_gives_no_categories(): void
    {
        Product::query()->delete();
        Category::query()->whereNotNull('parent_id')->delete();
        Category::query()->delete();

        $this->assertTrue(app(CatalogNavigation::class)->roots()->isEmpty());
    }
}
