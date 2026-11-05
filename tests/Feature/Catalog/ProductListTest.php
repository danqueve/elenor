<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ProductList;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $specs
     */
    private function product(array $attributes = [], array $specs = []): Product
    {
        return Product::factory()->withSpecs($specs)->create($attributes);
    }

    // The routes

    public function test_the_listing_pages_are_served_by_the_component(): void
    {
        Category::factory()->create(['slug' => 'cables']);

        foreach (['/catalogo', '/categoria/cables', '/ofertas', '/buscar?q=cable'] as $url) {
            $this->get($url)->assertOk()->assertSeeLivewire(ProductList::class);
        }
    }

    public function test_an_unknown_category_is_a_404(): void
    {
        $this->get('/categoria/no-existe')->assertNotFound();
    }

    public function test_the_page_titles_say_what_is_being_shown(): void
    {
        Category::factory()->create(['name' => 'Protección eléctrica', 'slug' => 'proteccion-electrica']);

        $this->get('/catalogo')->assertSee('<title>Catálogo | Elenor</title>', false);
        $this->get('/categoria/proteccion-electrica')->assertSee('<title>Protección eléctrica | Elenor</title>', false);
        $this->get('/ofertas')->assertSee('<title>Ofertas | Elenor</title>', false);
        $this->get('/buscar?q=cable')->assertSee('<title>Resultados para «cable» | Elenor</title>', false);
    }

    public function test_the_breadcrumb_follows_the_category_path(): void
    {
        $root = Category::factory()->create(['name' => 'Cables', 'slug' => 'cables']);
        Category::factory()->childOf($root)->create(['name' => 'Unipolares', 'slug' => 'unipolares']);

        $this->get('/categoria/unipolares')->assertSeeInOrder([
            'aria-label="Ruta de navegación"',
            'Inicio',
            'Catálogo',
            'Cables',
            'Unipolares',
        ], false);
    }

    // What it lists

    public function test_it_lists_active_products_with_their_name_brand_and_price(): void
    {
        $brand = Brand::factory()->create(['name' => 'Prysmian']);
        $this->product(['name' => 'Cable unipolar 2,5 mm²', 'brand_id' => $brand->id, 'list_price' => '1150.00']);
        $this->product(['name' => 'Producto oculto', 'is_active' => false]);

        Livewire::test(ProductList::class)
            ->assertSee('Cable unipolar 2,5 mm²')
            ->assertSee('Prysmian')
            ->assertSee(money('1150.00'))
            ->assertDontSee('Producto oculto')
            ->assertSee('1 producto');
    }

    public function test_a_category_shows_the_products_of_its_subcategories_too(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $this->product(['name' => 'En el hijo', 'category_id' => $child->id]);
        $this->product(['name' => 'En otra categoría']);

        Livewire::test(ProductList::class, ['category' => $root])
            ->assertSee('En el hijo')
            ->assertDontSee('En otra categoría');
    }

    public function test_the_offers_page_lists_only_offers(): void
    {
        $this->product(['name' => 'En oferta', 'list_price' => '1000.00', 'compare_at_price' => '1250.00']);
        $this->product(['name' => 'Normal']);

        $this->get('/ofertas')
            ->assertSee('En oferta')
            ->assertSee('-20%')
            ->assertSee(money('1250.00'))
            ->assertDontSee('Normal');
    }

    public function test_sold_out_products_are_listed_and_marked(): void
    {
        $this->product(['name' => 'Sin existencias', 'stock' => '0.00']);

        Livewire::test(ProductList::class)->assertSee('Sin existencias')->assertSee('Sin stock');
    }

    public function test_guests_see_the_list_price_and_professionals_their_own(): void
    {
        $this->product(['name' => 'Cable', 'list_price' => '1000.00', 'professional_price' => '900.00']);

        Livewire::test(ProductList::class)->assertSee(money('1000.00'))->assertDontSee('Precio profesional');

        // actingAs stays in effect for the rest of the test: guests go first.
        Livewire::actingAs(User::factory()->professional()->create())
            ->test(ProductList::class)
            ->assertSee(money('900.00'))
            ->assertSee('Precio profesional')
            ->assertDontSee(money('1000.00'));
    }

    // Search

    public function test_the_search_term_comes_from_the_url_and_filters_the_list(): void
    {
        $this->product(['name' => 'Cable rojo']);
        $this->product(['name' => 'Termomagnética']);

        Livewire::withQueryParams(['q' => 'rojo'])
            ->test(ProductList::class)
            ->assertSet('q', 'rojo')
            ->assertSee('Resultados para «rojo»')
            ->assertSee('Cable rojo')
            ->assertDontSee('Termomagnética');
    }

    public function test_the_search_can_be_removed(): void
    {
        $this->product(['name' => 'Cable rojo']);
        $this->product(['name' => 'Termomagnética']);

        Livewire::withQueryParams(['q' => 'rojo'])
            ->test(ProductList::class)
            ->call('clearSearch')
            ->assertSet('q', '')
            ->assertSee('Termomagnética');
    }

    public function test_without_results_it_says_so_and_offers_help(): void
    {
        config(['shop.contact.whatsapp' => '5493815551234']);

        Livewire::withQueryParams(['q' => 'nada de esto existe'])
            ->test(ProductList::class)
            ->assertSee('No encontramos productos')
            ->assertSee('Sin resultados')
            ->assertSeeHtml('https://wa.me/5493815551234?text=');
    }

    // Filters

    public function test_selecting_a_brand_narrows_the_list(): void
    {
        $alfa = Brand::factory()->create(['name' => 'Alfa']);
        $beta = Brand::factory()->create(['name' => 'Beta']);
        $this->product(['name' => 'De alfa', 'brand_id' => $alfa->id]);
        $this->product(['name' => 'De beta', 'brand_id' => $beta->id]);

        Livewire::test(ProductList::class)
            ->assertSee('De alfa')->assertSee('De beta')
            ->set('brands', [(string) $alfa->id])
            ->assertSee('De alfa')->assertDontSee('De beta')
            ->assertSee('Marca: Alfa');
    }

    public function test_attribute_filters_combine(): void
    {
        $this->product(['name' => 'A'], ['Sección' => '1,5 mm²', 'Color' => 'Rojo']);
        $this->product(['name' => 'B'], ['Sección' => '1,5 mm²', 'Color' => 'Azul']);
        $this->product(['name' => 'C'], ['Sección' => '2,5 mm²', 'Color' => 'Rojo']);

        Livewire::test(ProductList::class)
            ->set('specs.Sección', ['1,5 mm²'])
            ->assertSee('2 productos')
            ->set('specs.Color', ['Rojo'])
            ->assertSee('1 producto')
            ->assertSee('Sección: 1,5 mm²')
            ->assertSee('Color: Rojo');
    }

    public function test_the_attribute_checkboxes_add_and_remove_values_through_the_server(): void
    {
        $this->product(['name' => 'A'], ['Color' => 'Rojo']);
        $this->product(['name' => 'B'], ['Color' => 'Azul']);
        $this->product(['name' => 'C'], ['Color' => 'Rojo']);

        Livewire::test(ProductList::class)
            // The first tick of a group nobody has used yet (there is no array for the browser to bind).
            ->call('toggleSpec', 'Color', 'Rojo')
            ->assertSet('specs', ['Color' => ['Rojo']])
            ->assertSee('2 productos')
            ->call('toggleSpec', 'Color', 'Azul')
            ->assertSet('specs', ['Color' => ['Rojo', 'Azul']])
            ->assertSee('3 productos')
            ->call('toggleSpec', 'Color', 'Rojo')
            ->assertSet('specs', ['Color' => ['Azul']])
            ->call('toggleSpec', 'Color', 'Azul')
            ->assertSet('specs', []);
    }

    public function test_toggling_an_attribute_goes_back_to_the_first_page(): void
    {
        foreach (range(1, 14) as $number) {
            $this->product(['name' => sprintf('Producto %02d', $number)], ['Color' => 'Rojo']);
        }

        Livewire::test(ProductList::class)
            ->call('nextPage')
            ->assertSee('Producto 13')
            ->call('toggleSpec', 'Color', 'Rojo')
            ->assertSee('Producto 01')
            ->assertDontSee('Producto 13');
    }

    public function test_toggling_ignores_names_and_values_that_make_no_sense(): void
    {
        Livewire::test(ProductList::class)
            ->call('toggleSpec', '', 'Rojo')
            ->call('toggleSpec', str_repeat('x', 61), 'Rojo')
            ->call('toggleSpec', 'Color', '')
            ->call('toggleSpec', 'Color', str_repeat('x', 121))
            ->assertSet('specs', []);
    }

    public function test_the_attribute_checkboxes_are_ticked_from_what_the_server_knows(): void
    {
        $this->product(['name' => 'A'], ['Color' => 'Rojo']);
        $this->product(['name' => 'B'], ['Color' => 'Azul']);

        $html = Livewire::test(ProductList::class)->call('toggleSpec', 'Color', 'Rojo')->html();

        // wire:click and not wire:model: see ProductList::toggleSpec().
        $this->assertStringNotContainsString('wire:model.live="specs', $html);

        preg_match_all('/<input\s+type="checkbox"[^>]*>/s', $html, $tags);
        $byValue = [];
        foreach ($tags[0] as $tag) {
            preg_match('/\svalue="([^"]*)"/', $tag, $value);
            $byValue[$value[1]] = $tag;
        }

        $this->assertMatchesRegularExpression('/\schecked\s/', $byValue['Rojo']);
        $this->assertDoesNotMatchRegularExpression('/\schecked\s/', $byValue['Azul']);
        $this->assertStringContainsString('wire:click="toggleSpec(\'Color\', \'Rojo\')"', $byValue['Rojo']);
        $this->assertStringContainsString('x-bind:checked="($wire.specs[\'Color\'] ?? []).includes(\'Azul\')"', $byValue['Azul']);
    }

    public function test_names_and_values_with_quotes_cannot_break_the_checkbox_markup(): void
    {
        // An attribute is offered as a filter only when the listing has two different values of it.
        $this->product(['name' => 'Caño'], ['Diámetro' => '3/4" (pulgada)']);
        $this->product(['name' => 'Caño chico'], ['Diámetro' => '1/2" (media)']);

        $component = Livewire::test(ProductList::class);

        preg_match_all('/wire:click="(toggleSpec\([^"]*)"/', $component->html(), $calls);
        $this->assertCount(2, $calls[1]);

        // The quote travels as the JavaScript escape " (a backslash, then u0022).
        $escapedQuote = chr(92).'u0022';
        $this->assertStringContainsString($escapedQuote, $calls[1][0]);
        $this->assertStringContainsString($escapedQuote, $calls[1][1]);

        $component->call('toggleSpec', 'Diámetro', '3/4" (pulgada)')
            ->assertSet('specs', ['Diámetro' => ['3/4" (pulgada)']])
            ->assertSee('1 producto');
    }

    public function test_the_price_range_is_typed_in_pesos(): void
    {
        $this->product(['name' => 'Barato', 'list_price' => '500.00']);
        $this->product(['name' => 'Medio', 'list_price' => '5000.00']);
        $this->product(['name' => 'Caro', 'list_price' => '50000.00']);

        Livewire::test(ProductList::class)
            ->set('min', '1.000')
            ->set('max', '10.000')
            ->assertSee('Medio')->assertDontSee('Barato')->assertDontSee('Caro')
            ->assertSee('Precio: '.money(1000, 0).' a '.money(10000, 0));
    }

    public function test_a_reversed_price_range_is_put_in_order(): void
    {
        $this->product(['name' => 'Medio', 'list_price' => '5000.00']);

        Livewire::test(ProductList::class)->set('min', '10000')->set('max', '1000')->assertSee('Medio');
    }

    public function test_every_filter_can_be_removed_one_by_one_or_all_at_once(): void
    {
        $brand = Brand::factory()->create(['name' => 'Alfa']);
        $this->product(['name' => 'Producto Alfa', 'brand_id' => $brand->id, 'list_price' => '500.00'], ['Color' => 'Rojo']);
        $this->product(['name' => 'Producto Beta'], ['Color' => 'Azul']);

        $component = Livewire::test(ProductList::class)
            ->set('brands', [(string) $brand->id])
            ->set('specs.Color', ['Rojo'])
            ->set('min', '100');

        $component->call('removeBrand', $brand->id)->assertSet('brands', []);
        $component->call('removeSpec', 'Color', 'Rojo')->assertSet('specs', []);
        $component->call('clearPrice')->assertSet('min', '')->assertSet('max', '');

        $component->set('brands', [(string) $brand->id])->set('specs.Color', ['Rojo'])->set('max', '900')
            ->call('clearFilters')
            ->assertSet('brands', [])
            ->assertSet('specs', [])
            ->assertSet('max', '')
            ->assertSee('Producto Alfa')->assertSee('Producto Beta');
    }

    public function test_unticking_every_value_of_a_group_leaves_no_empty_group(): void
    {
        $this->product(['name' => 'A'], ['Color' => 'Rojo']);
        $this->product(['name' => 'B'], ['Color' => 'Azul']);

        Livewire::test(ProductList::class)
            ->set('specs.Color', ['Rojo'])
            ->set('specs.Color', [])
            ->assertSet('specs', []);
    }

    public function test_the_filters_are_read_from_the_url(): void
    {
        $brand = Brand::factory()->create();
        $this->product(['name' => 'Barato', 'brand_id' => $brand->id, 'list_price' => '100.00']);
        $this->product(['name' => 'Caro', 'brand_id' => $brand->id, 'list_price' => '900.00']);

        Livewire::withQueryParams(['marca' => [(string) $brand->id], 'orden' => 'mayor-precio', 'min' => '50'])
            ->test(ProductList::class)
            ->assertSet('brands', [(string) $brand->id])
            ->assertSet('sort', 'mayor-precio')
            ->assertSet('min', '50')
            ->assertSeeInOrder(['Caro', 'Barato']);
    }

    // Order and pages

    public function test_the_order_can_be_changed(): void
    {
        $this->product(['name' => 'Banana', 'list_price' => '300.00']);
        $this->product(['name' => 'Anana', 'list_price' => '200.00']);
        $this->product(['name' => 'Cereza', 'list_price' => '100.00']);

        Livewire::test(ProductList::class)
            ->assertSeeInOrder(['Anana', 'Banana', 'Cereza'])
            ->set('sort', 'menor-precio')
            ->assertSeeInOrder(['Cereza', 'Anana', 'Banana'])
            ->set('sort', 'mayor-precio')
            ->assertSeeInOrder(['Banana', 'Anana', 'Cereza']);
    }

    public function test_an_unknown_order_falls_back_to_the_default(): void
    {
        $this->product(['name' => 'Banana']);
        $this->product(['name' => 'Anana']);

        Livewire::test(ProductList::class)->set('sort', 'inventado')->assertSeeInOrder(['Anana', 'Banana']);
    }

    public function test_relevance_is_the_default_order_when_searching(): void
    {
        $this->product(['name' => 'Zzz', 'sku' => 'ABC']);

        Livewire::withQueryParams(['q' => 'abc'])->test(ProductList::class)->assertSee('Más relevantes');
    }

    // Livewire keeps the query params of withQueryParams() for the rest of the test, hence a test of its own.
    public function test_the_name_is_the_default_order_when_not_searching(): void
    {
        $this->product(['name' => 'Zzz', 'sku' => 'ABC']);

        Livewire::test(ProductList::class)->assertDontSee('Más relevantes')->assertSee('Nombre (A-Z)');
    }

    public function test_there_are_twelve_products_per_page(): void
    {
        foreach (range(1, 13) as $number) {
            $this->product(['name' => sprintf('Producto %02d', $number)]);
        }

        Livewire::test(ProductList::class)
            ->assertSee('Producto 12')->assertDontSee('Producto 13')
            ->assertSee('13 productos')
            ->call('nextPage')
            ->assertSee('Producto 13')->assertDontSee('Producto 01');
    }

    public function test_the_page_buttons_print_the_translated_labels_as_they_come(): void
    {
        foreach (range(1, 13) as $number) {
            $this->product(['name' => sprintf('Producto %02d', $number)]);
        }

        // lang/es has them with HTML entities: escaping them would show "&raquo;" on the button.
        Livewire::test(ProductList::class)
            ->assertSeeHtml('Siguiente &raquo;')
            ->assertSeeHtml('&laquo; Anterior')
            ->assertDontSeeHtml('&amp;raquo;')
            ->assertDontSeeHtml('&amp;laquo;');
    }

    public function test_changing_a_filter_goes_back_to_the_first_page(): void
    {
        foreach (range(1, 14) as $number) {
            $this->product(['name' => sprintf('Producto %02d', $number)]);
        }

        Livewire::test(ProductList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('sort', 'mayor-precio')
            ->assertSet('paginators.page', 1);
    }

    public function test_a_page_beyond_the_last_shows_the_last_one(): void
    {
        foreach (range(1, 13) as $number) {
            $this->product(['name' => sprintf('Producto %02d', $number)]);
        }

        Livewire::withQueryParams(['page' => 9])
            ->test(ProductList::class)
            ->assertSee('Producto 13')
            ->assertSet('paginators.page', 2);
    }

    // Safety

    public function test_the_category_and_the_collection_cannot_be_changed_from_the_browser(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(ProductList::class, ['category' => $category])->set('category', $other->id);
    }

    public function test_garbage_in_the_url_does_not_break_the_page(): void
    {
        $this->product(['name' => 'Cable']);

        Livewire::withQueryParams([
            'marca' => ['abc', "5'; DROP TABLE products;--", ['x']],
            'attr' => ['Sección' => 'no es una lista', 'Color' => [['x']], 7 => ['y']],
            'min' => '99999999999999999999999',
            'max' => 'barato',
            'orden' => ['raro'],
            'q' => str_repeat('a', 500),
        ])->test(ProductList::class)->assertOk();

        $this->assertDatabaseCount('products', 1);
    }

    public function test_user_input_is_escaped(): void
    {
        $this->product(['name' => 'Cable']);

        Livewire::withQueryParams(['q' => '<script>alert(1)</script>'])
            ->test(ProductList::class)
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
