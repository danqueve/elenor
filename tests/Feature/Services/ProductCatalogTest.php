<?php

namespace Tests\Feature\Services;

use App\Enums\CatalogSort;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductCatalog;
use App\Services\Catalog\ProductFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private ProductCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = app(ProductCatalog::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $specs
     */
    private function product(array $attributes = [], array $specs = []): Product
    {
        return Product::factory()->withSpecs($specs)->create($attributes);
    }

    /**
     * @return list<string> Names of the products that match, in order.
     */
    private function names(ProductFilters $filters): array
    {
        return $this->catalog->products($filters)->pluck('products.name')->all();
    }

    // What is listed

    public function test_only_active_products_are_listed(): void
    {
        $this->product(['name' => 'Activo']);
        $this->product(['name' => 'Oculto', 'is_active' => false]);

        $this->assertSame(['Activo'], $this->names(new ProductFilters));
    }

    public function test_a_category_includes_everything_below_it(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $grandchild = Category::factory()->childOf($child)->create();
        $other = Category::factory()->create();

        $this->product(['name' => 'En la raíz', 'category_id' => $root->id]);
        $this->product(['name' => 'En el hijo', 'category_id' => $child->id]);
        $this->product(['name' => 'En el nieto', 'category_id' => $grandchild->id]);
        $this->product(['name' => 'En otra', 'category_id' => $other->id]);

        $this->assertEqualsCanonicalizing(
            ['En la raíz', 'En el hijo', 'En el nieto'],
            $this->names(new ProductFilters(category: $root)),
        );
        $this->assertSame(['En el nieto'], $this->names(new ProductFilters(category: $grandchild)));
    }

    public function test_the_offers_filter_keeps_only_products_on_sale(): void
    {
        $this->product(['name' => 'En oferta', 'list_price' => '1000.00', 'compare_at_price' => '1250.00']);
        $this->product(['name' => 'Precio igual', 'list_price' => '1000.00', 'compare_at_price' => '1000.00']);
        $this->product(['name' => 'Sin precio anterior', 'compare_at_price' => null]);

        $this->assertSame(['En oferta'], $this->names(new ProductFilters(onlyOffers: true)));
    }

    // Search

    public function test_the_search_matches_name_sku_manufacturer_code_and_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Prysmian']);
        $this->product(['name' => 'Cable unipolar', 'sku' => 'CAB-0007', 'manufacturer_code' => 'RO-25-CE', 'brand_id' => $brand->id]);
        $this->product(['name' => 'Termomagnética', 'sku' => 'PRO-0001', 'manufacturer_code' => 'TM2P16C']);

        foreach (['unipolar', 'cab-0007', 'RO-25', 'prysmian', 'PRYS'] as $term) {
            $this->assertSame(['Cable unipolar'], $this->names(new ProductFilters(search: $term)), "Searching for \"{$term}\"");
        }
    }

    public function test_the_search_ignores_accents_and_case(): void
    {
        $this->product(['name' => 'Caño corrugado']);

        foreach (['CANO', 'caño', 'Cañ', 'CORRUGADO'] as $term) {
            $this->assertSame(['Caño corrugado'], $this->names(new ProductFilters(search: $term)), "Searching for \"{$term}\"");
        }
    }

    public function test_every_word_has_to_match_in_any_field(): void
    {
        $prysmian = Brand::factory()->create(['name' => 'Prysmian']);
        $cimet = Brand::factory()->create(['name' => 'Cimet']);

        $this->product(['name' => 'Cable rojo', 'brand_id' => $prysmian->id]);
        $this->product(['name' => 'Cable azul', 'brand_id' => $prysmian->id]);
        $this->product(['name' => 'Cable rojo ', 'brand_id' => $cimet->id, 'sku' => 'XXX-0001']);

        $this->assertSame(['Cable rojo'], $this->names(new ProductFilters(search: 'prysmian rojo')));
    }

    public function test_wildcards_in_the_search_are_taken_literally(): void
    {
        $this->product(['name' => 'Cinta 50% adhesiva']);
        $this->product(['name' => 'Cinta normal']);
        $this->product(['name' => 'cable_x']);
        $this->product(['name' => 'cableXx']);

        $this->assertSame(['Cinta 50% adhesiva'], $this->names(new ProductFilters(search: '%')));
        $this->assertSame(['cable_x'], $this->names(new ProductFilters(search: '_')));
    }

    public function test_a_decimal_dot_finds_the_decimal_comma(): void
    {
        $this->product(['name' => 'Cable 2,5 mm²']);
        $this->product(['name' => 'Cable 4 mm²']);

        $this->assertSame(['Cable 2,5 mm²'], $this->names(new ProductFilters(search: '2.5')));
        $this->assertSame(['Cable 2,5 mm²'], $this->names(new ProductFilters(search: '2,5')));
    }

    public function test_an_exact_code_comes_first_then_names_that_start_with_the_search(): void
    {
        $this->product(['name' => 'Zzz', 'sku' => 'ABC']);
        $this->product(['name' => 'ABC adaptador', 'sku' => 'XYZ-1']);
        $this->product(['name' => 'Adaptador ABC', 'sku' => 'XYZ-2']);

        $this->assertSame(['Zzz', 'ABC adaptador', 'Adaptador ABC'], $this->names(new ProductFilters(search: 'ABC')));
    }

    // Filters

    public function test_the_brand_filter_combines_several_brands(): void
    {
        $alfa = Brand::factory()->create();
        $beta = Brand::factory()->create();
        $gamma = Brand::factory()->create();

        $this->product(['name' => 'De alfa', 'brand_id' => $alfa->id]);
        $this->product(['name' => 'De beta', 'brand_id' => $beta->id]);
        $this->product(['name' => 'De gamma', 'brand_id' => $gamma->id]);
        $this->product(['name' => 'Sin marca', 'brand_id' => null]);

        $this->assertEqualsCanonicalizing(['De alfa', 'De beta'], $this->names(new ProductFilters(brandIds: [$alfa->id, $beta->id])));
    }

    public function test_the_price_filter_uses_the_price_the_visitor_pays(): void
    {
        $this->product(['name' => 'Barato', 'list_price' => '100.00', 'professional_price' => '80.00']);
        $this->product(['name' => 'Medio', 'list_price' => '500.00', 'professional_price' => null]);
        $this->product(['name' => 'Caro', 'list_price' => '1000.00', 'professional_price' => '900.00']);
        $professional = User::factory()->professional()->create();

        // Guests and final customers pay the list price.
        $this->assertSame(['Medio'], $this->names(new ProductFilters(minPrice: 200, maxPrice: 900)));
        $this->assertSame(['Medio'], $this->names(new ProductFilters(minPrice: 200, maxPrice: 900, user: User::factory()->make())));
        // Approved professionals pay the professional price, or the list price when there is none.
        $this->assertEqualsCanonicalizing(['Medio', 'Caro'], $this->names(new ProductFilters(minPrice: 200, maxPrice: 900, user: $professional)));
        // "Caro" costs 1000 for everyone else but only 900 for a professional.
        $this->assertSame(['Caro'], $this->names(new ProductFilters(minPrice: 901)));
        $this->assertSame([], $this->names(new ProductFilters(minPrice: 901, user: $professional)));
    }

    public function test_attribute_values_are_alternatives_and_different_attributes_all_have_to_match(): void
    {
        $this->product(['name' => 'A'], ['Sección' => '1,5 mm²', 'Color' => 'Rojo']);
        $this->product(['name' => 'B'], ['Sección' => '1,5 mm²', 'Color' => 'Azul']);
        $this->product(['name' => 'C'], ['Sección' => '2,5 mm²', 'Color' => 'Rojo']);
        $this->product(['name' => 'D'], ['Sección' => '4 mm²', 'Color' => 'Negro']);

        $this->assertEqualsCanonicalizing(['A', 'B'], $this->names(new ProductFilters(specs: ['Sección' => ['1,5 mm²']])));
        $this->assertEqualsCanonicalizing(['A', 'B', 'C'], $this->names(new ProductFilters(specs: ['Sección' => ['1,5 mm²', '2,5 mm²']])));
        $this->assertEqualsCanonicalizing(['A', 'C'], $this->names(new ProductFilters(specs: ['Sección' => ['1,5 mm²', '2,5 mm²'], 'Color' => ['Rojo']])));
        $this->assertSame([], $this->names(new ProductFilters(specs: ['Sección' => ['4 mm²'], 'Color' => ['Rojo']])));
    }

    // Order

    public function test_the_order_options(): void
    {
        $this->product(['name' => 'Banana', 'list_price' => '300.00', 'created_at' => now()->subDays(5)]);
        $this->product(['name' => 'Cereza', 'list_price' => '100.00', 'created_at' => now()->subDays(1)]);
        $this->product(['name' => 'Anana', 'list_price' => '200.00', 'created_at' => now()->subDays(3)]);

        $this->assertSame(['Anana', 'Banana', 'Cereza'], $this->names(new ProductFilters(sort: CatalogSort::Name)));
        $this->assertSame(['Cereza', 'Anana', 'Banana'], $this->names(new ProductFilters(sort: CatalogSort::PriceAsc)));
        $this->assertSame(['Banana', 'Anana', 'Cereza'], $this->names(new ProductFilters(sort: CatalogSort::PriceDesc)));
        $this->assertSame(['Cereza', 'Anana', 'Banana'], $this->names(new ProductFilters(sort: CatalogSort::Newest)));
    }

    public function test_sorting_by_price_uses_the_professional_price_for_professionals(): void
    {
        $this->product(['name' => 'Lista baja', 'list_price' => '100.00', 'professional_price' => null]);
        $this->product(['name' => 'Pro más barato', 'list_price' => '150.00', 'professional_price' => '50.00']);

        $this->assertSame(['Lista baja', 'Pro más barato'], $this->names(new ProductFilters(sort: CatalogSort::PriceAsc)));
        $this->assertSame(
            ['Pro más barato', 'Lista baja'],
            $this->names(new ProductFilters(sort: CatalogSort::PriceAsc, user: User::factory()->professional()->create())),
        );
    }

    public function test_products_with_the_same_name_and_price_keep_a_stable_order(): void
    {
        $first = $this->product(['name' => 'Igual', 'list_price' => '100.00']);
        $second = $this->product(['name' => 'Igual', 'list_price' => '100.00']);
        $third = $this->product(['name' => 'Igual', 'list_price' => '100.00']);

        foreach ([CatalogSort::Name, CatalogSort::PriceAsc, CatalogSort::PriceDesc] as $sort) {
            $this->assertSame(
                [$first->id, $second->id, $third->id],
                $this->catalog->products(new ProductFilters(sort: $sort))->pluck('products.id')->all(),
                "Order {$sort->value} is not stable.",
            );
        }
    }

    // Facets

    public function test_the_brand_facet_counts_what_each_brand_would_give(): void
    {
        $alfa = Brand::factory()->create(['name' => 'Alfa']);
        $beta = Brand::factory()->create(['name' => 'Beta']);
        $category = Category::factory()->create();
        $elsewhere = Category::factory()->create();

        $this->product(['category_id' => $category->id, 'brand_id' => $alfa->id, 'list_price' => '100.00']);
        $this->product(['category_id' => $category->id, 'brand_id' => $alfa->id, 'list_price' => '900.00']);
        $this->product(['category_id' => $category->id, 'brand_id' => $beta->id, 'list_price' => '100.00']);
        $this->product(['category_id' => $category->id, 'brand_id' => null]);
        $this->product(['category_id' => $elsewhere->id, 'brand_id' => $alfa->id]);
        $this->product(['category_id' => $category->id, 'brand_id' => $beta->id, 'is_active' => false]);

        $facet = fn (ProductFilters $filters) => $this->catalog->brandFacet($filters)->mapWithKeys(fn ($brand) => [$brand->name => $brand->total])->all();

        $this->assertSame(['Alfa' => 2, 'Beta' => 1], $facet(new ProductFilters(category: $category)));
        // The brand filter itself is ignored, so brands can be added; the other filters count.
        $this->assertSame(['Alfa' => 2, 'Beta' => 1], $facet(new ProductFilters(category: $category, brandIds: [$beta->id])));
        $this->assertSame(['Alfa' => 1, 'Beta' => 1], $facet(new ProductFilters(category: $category, maxPrice: 500)));
    }

    public function test_a_selected_brand_without_results_stays_in_the_facet_with_zero(): void
    {
        $alfa = Brand::factory()->create(['name' => 'Alfa']);
        $gamma = Brand::factory()->create(['name' => 'Gamma']);
        $category = Category::factory()->create();
        $this->product(['category_id' => $category->id, 'brand_id' => $alfa->id]);

        $facet = $this->catalog->brandFacet(new ProductFilters(category: $category, brandIds: [$gamma->id]));

        $this->assertSame(['Alfa' => 1, 'Gamma' => 0], $facet->mapWithKeys(fn ($brand) => [$brand->name => $brand->total])->all());
    }

    public function test_only_attributes_with_two_or_more_values_are_offered(): void
    {
        $this->product([], ['Color' => 'Rojo', 'Material' => 'PVC']);
        $this->product([], ['Color' => 'Azul', 'Material' => 'PVC']);

        $names = array_column($this->catalog->specFacets(new ProductFilters), 'name');

        $this->assertSame(['Color'], $names);
    }

    public function test_the_attributes_offered_do_not_depend_on_the_other_filters(): void
    {
        $brand = Brand::factory()->create();
        $this->product(['brand_id' => $brand->id], ['Color' => 'Rojo', 'Largo' => '3 m']);
        $this->product([], ['Color' => 'Azul', 'Largo' => '5 m']);

        $filtered = array_column($this->catalog->specFacets(new ProductFilters(brandIds: [$brand->id])), 'name');

        $this->assertEqualsCanonicalizing(['Color', 'Largo'], $filtered);
    }

    public function test_attribute_counts_take_the_other_groups_into_account_but_not_their_own(): void
    {
        $this->product([], ['Sección' => '1,5 mm²', 'Color' => 'Rojo']);
        $this->product([], ['Sección' => '1,5 mm²', 'Color' => 'Azul']);
        $this->product([], ['Sección' => '2,5 mm²', 'Color' => 'Rojo']);

        $facets = collect($this->catalog->specFacets(new ProductFilters(specs: ['Sección' => ['1,5 mm²']])))
            ->mapWithKeys(fn (array $facet) => [$facet['name'] => collect($facet['values'])->pluck('total', 'value')->all()]);

        // Sección ignores its own selection: both sections can still be ticked.
        $this->assertSame(['1,5 mm²' => 2, '2,5 mm²' => 1], $facets['Sección']);
        // Color only counts the products of the selected section.
        $this->assertSame(['Azul' => 1, 'Rojo' => 1], $facets['Color']);
    }

    public function test_a_selected_value_without_results_stays_listed_with_zero(): void
    {
        $this->product([], ['Color' => 'Rojo']);
        $this->product([], ['Color' => 'Azul']);

        $facet = $this->catalog->specFacets(new ProductFilters(specs: ['Color' => ['Verde']]))[0];

        $this->assertContains(['value' => 'Verde', 'total' => 0], $facet['values']);
    }

    public function test_attribute_values_are_sorted_naturally(): void
    {
        foreach (['10 A', '6 A', '16 A', '20 A', '2,5 A'] as $value) {
            $this->product([], ['Amperaje' => $value]);
        }

        $values = array_column($this->catalog->specFacets(new ProductFilters)[0]['values'], 'value');

        $this->assertSame(['2,5 A', '6 A', '10 A', '16 A', '20 A'], $values);
    }

    public function test_the_price_bounds(): void
    {
        $this->assertNull($this->catalog->priceBounds(new ProductFilters));

        $this->product(['list_price' => '1250.50', 'professional_price' => '900.00']);
        $this->product(['list_price' => '99.90', 'professional_price' => null]);
        $this->product(['list_price' => '5000.00', 'is_active' => false]);

        $this->assertSame(['min' => 99, 'max' => 1251], $this->catalog->priceBounds(new ProductFilters));
        // The price filter itself doesn't narrow the bounds.
        $this->assertSame(['min' => 99, 'max' => 1251], $this->catalog->priceBounds(new ProductFilters(minPrice: 500, maxPrice: 600)));
        $this->assertSame(['min' => 99, 'max' => 900], $this->catalog->priceBounds(new ProductFilters(user: User::factory()->professional()->create())));
    }

    // Suggestions

    public function test_suggestions_need_two_characters(): void
    {
        $this->product(['name' => 'Cable']);

        $this->assertCount(0, $this->catalog->suggestions('', null));
        $this->assertCount(0, $this->catalog->suggestions('c', null));
        $this->assertCount(0, $this->catalog->suggestions('  c ', null));
        $this->assertCount(1, $this->catalog->suggestions('ca', null));
    }

    public function test_suggestions_are_limited_active_and_best_first(): void
    {
        foreach (range(1, 9) as $number) {
            $this->product(['name' => "Cable {$number}", 'sku' => "Z-{$number}"]);
        }
        $this->product(['name' => 'Cable oculto', 'is_active' => false]);
        $this->product(['name' => 'Otro', 'sku' => 'CABLE']);

        $suggestions = $this->catalog->suggestions('cable', null);

        $this->assertCount(6, $suggestions);
        $this->assertSame('Otro', $suggestions->first()->name, 'An exact SKU goes first.');
        $this->assertNotContains('Cable oculto', $suggestions->pluck('name')->all());
        $this->assertTrue($suggestions->first()->relationLoaded('brand'));
    }
}
