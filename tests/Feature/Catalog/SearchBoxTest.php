<?php

namespace Tests\Feature\Catalog;

use App\Livewire\SearchBox;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchBoxTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_has_the_search_box_as_a_normal_get_form(): void
    {
        $this->get('/')
            ->assertSeeLivewire(SearchBox::class)
            ->assertSee('role="search"', false)
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('role="combobox"', false);
    }

    public function test_it_keeps_the_term_of_the_search_page(): void
    {
        $this->get('/buscar?q=cable 2,5')->assertSee('value="cable 2,5"', false);

        Livewire::withQueryParams(['q' => 'cable'])->test(SearchBox::class)->assertSet('query', 'cable');
    }

    public function test_it_suggests_products_by_name_with_their_brand_sku_and_price(): void
    {
        $brand = Brand::factory()->create(['name' => 'Prysmian']);
        $product = Product::factory()->create([
            'name' => 'Cable unipolar 2,5 mm² celeste',
            'sku' => 'CAB-0002',
            'brand_id' => $brand->id,
            'list_price' => '1150.00',
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'unipolar')
            ->assertSee('Cable unipolar 2,5 mm² celeste')
            ->assertSee('Prysmian')
            ->assertSee('SKU CAB-0002')
            ->assertSee(money('1150.00'))
            ->assertSeeHtml('href="'.route('products.show', $product).'"')
            ->assertSee('Ver todos los resultados para «unipolar»');
    }

    public function test_it_finds_by_sku_manufacturer_code_and_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Siemens']);
        Product::factory()->create(['name' => 'Disyuntor', 'sku' => 'PRO-0006', 'manufacturer_code' => 'DD2P40', 'brand_id' => $brand->id]);

        foreach (['PRO-0006', 'DD2P40', 'siemens'] as $term) {
            Livewire::test(SearchBox::class)->set('query', $term)->assertSee('Disyuntor');
        }
    }

    public function test_it_waits_for_two_characters(): void
    {
        Product::factory()->create(['name' => 'Cable']);

        Livewire::test(SearchBox::class)
            ->set('query', 'c')
            ->assertDontSee('Cable')
            ->assertDontSee('Ver todos los resultados')
            ->set('query', 'ca')
            ->assertSee('Cable');
    }

    public function test_it_says_when_nothing_matches(): void
    {
        Livewire::test(SearchBox::class)
            ->set('query', 'nada')
            ->assertSee('No encontramos productos para «nada»')
            ->assertDontSee('Ver todos los resultados');
    }

    public function test_it_shows_at_most_six_active_products(): void
    {
        foreach (range(1, 8) as $number) {
            Product::factory()->create(['name' => "Cable {$number}"]);
        }
        Product::factory()->create(['name' => 'Cable oculto', 'is_active' => false]);

        $component = Livewire::test(SearchBox::class)->set('query', 'cable');

        $this->assertCount(6, $component->instance()->suggestions);
        $component->assertDontSee('Cable oculto');
    }

    public function test_professionals_see_their_own_price_in_the_suggestions(): void
    {
        Product::factory()->create(['name' => 'Cable', 'list_price' => '1000.00', 'professional_price' => '900.00']);

        Livewire::actingAs(User::factory()->professional()->create())
            ->test(SearchBox::class)
            ->set('query', 'cable')
            ->assertSee(money('900.00'))
            ->assertDontSee(money('1000.00'));
    }

    public function test_what_is_typed_is_escaped(): void
    {
        Livewire::test(SearchBox::class)
            ->set('query', '<img src=x onerror=alert(1)>')
            ->assertDontSeeHtml('<img src=x onerror=alert(1)>')
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_the_link_to_all_the_results_goes_to_the_search_page(): void
    {
        Product::factory()->create(['name' => 'Cable 2,5']);

        Livewire::test(SearchBox::class)
            ->set('query', 'cable 2,5')
            ->assertSeeHtml('href="'.route('search', ['q' => 'cable 2,5']).'"');
    }
}
