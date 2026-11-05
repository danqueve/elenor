<?php

namespace Tests\Unit;

use App\Enums\CatalogSort;
use App\Services\Catalog\ProductFilters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductFiltersTest extends TestCase
{
    /**
     * @return array<string, array{mixed, list<int>}>
     */
    public static function brandInputs(): array
    {
        return [
            'strings from checkboxes' => [['3', '7'], [3, 7]],
            'duplicates are dropped' => [['3', 3, '3'], [3]],
            'junk is ignored' => [['abc', '5; DROP TABLE', '', null, [1], '-2', '0', '12'], [12]],
            'not a list' => ['3', []],
            'null' => [null, []],
        ];
    }

    /**
     * @param  list<int>  $expected
     */
    #[DataProvider('brandInputs')]
    public function test_it_cleans_brand_ids(mixed $input, array $expected): void
    {
        $this->assertSame($expected, ProductFilters::cleanBrandIds($input));
    }

    public function test_it_limits_the_number_of_brands(): void
    {
        $this->assertCount(50, ProductFilters::cleanBrandIds(range(1, 500)));
    }

    public function test_it_cleans_attribute_selections(): void
    {
        $clean = ProductFilters::cleanSpecs([
            'Sección' => ['2,5 mm²', '', '2,5 mm²', ['nested'], 4],
            'Color' => [],
            'Largo' => 'not a list',
            0 => ['numeric key is not an attribute name'],
            str_repeat('x', 61) => ['too long a name'],
            'Amperaje' => ['16 A', str_repeat('y', 121)],
        ]);

        $this->assertSame(['Sección' => ['2,5 mm²'], 'Amperaje' => ['16 A']], $clean);
    }

    public function test_it_limits_how_many_attribute_groups_and_values_are_read(): void
    {
        $groups = [];

        foreach (range(1, 40) as $i) {
            $groups["Atributo {$i}"] = ['a'];
        }

        $this->assertCount(12, ProductFilters::cleanSpecs($groups));
        $this->assertCount(30, ProductFilters::cleanSpecs(['Color' => array_map(fn ($i) => "valor {$i}", range(1, 200))])['Color']);
        $this->assertSame([], ProductFilters::cleanSpecs('x'));
    }

    /**
     * @return array<string, array{mixed, ?int}>
     */
    public static function prices(): array
    {
        return [
            'plain' => ['1500', 1500],
            'with thousands dots' => ['1.500', 1500],
            'with a currency sign and spaces' => ['$ 12.345', 12345],
            'the decimals are ignored' => ['1.500,75', 1500],
            'an integer' => [900, 900],
            'empty' => ['', null],
            'letters' => ['barato', null],
            'an array' => [['1'], null],
            'null' => [null, null],
            'absurdly large is capped' => ['99999999999999999999', 99_999_999],
        ];
    }

    #[DataProvider('prices')]
    public function test_it_cleans_prices_typed_by_people(mixed $input, ?int $expected): void
    {
        $this->assertSame($expected, ProductFilters::cleanPrice($input));
    }

    public function test_the_default_order_depends_on_whether_there_is_a_search(): void
    {
        $this->assertSame(CatalogSort::Name, (new ProductFilters)->effectiveSort());
        $this->assertSame(CatalogSort::Relevance, (new ProductFilters(search: 'cable'))->effectiveSort());
    }

    public function test_relevance_is_ignored_without_a_search(): void
    {
        $this->assertSame(CatalogSort::Name, (new ProductFilters(sort: CatalogSort::Relevance))->effectiveSort());
        $this->assertSame(CatalogSort::PriceAsc, (new ProductFilters(sort: CatalogSort::PriceAsc))->effectiveSort());
        $this->assertSame(CatalogSort::PriceDesc, (new ProductFilters(search: 'cable', sort: CatalogSort::PriceDesc))->effectiveSort());
    }

    public function test_it_counts_the_active_filters(): void
    {
        $this->assertSame(0, (new ProductFilters(search: 'cable'))->activeFilterCount());

        $filters = new ProductFilters(
            brandIds: [1, 2],
            specs: ['Color' => ['Rojo', 'Azul'], 'Sección' => ['2,5 mm²']],
            minPrice: 100,
            maxPrice: 900,
        );

        // 2 brands + 3 attribute values + the price range (counted once).
        $this->assertSame(6, $filters->activeFilterCount());
    }
}
