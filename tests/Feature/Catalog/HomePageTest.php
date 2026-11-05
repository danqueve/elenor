<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ReadsPageHtml;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use ReadsPageHtml;
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function categoryNames(string $html): array
    {
        preg_match_all('/<h3 class="font-bold">(.*?)<\/h3>/s', $html, $matches);

        return array_map(fn (string $name) => html_entity_decode($name, ENT_QUOTES), $matches[1]);
    }

    public function test_the_banner_leads_to_the_catalog(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Materiales eléctricos y herramientas')
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('Ver catálogo');
    }

    public function test_it_shows_the_main_categories_in_order_with_their_product_counts(): void
    {
        $this->seed(CategorySeeder::class);
        $unipolares = Category::query()->where('slug', 'unipolares')->firstOrFail();
        Product::factory()->count(3)->create(['category_id' => $unipolares->id]);
        Product::factory()->inactive()->create(['category_id' => $unipolares->id]);

        $section = $this->section($this->get('/')->assertOk()->getContent(), 'categorias-titulo');

        $this->assertSame(
            ['Cables', 'Protección eléctrica', 'Mecanismos', 'Iluminación LED', 'Canalización', 'Herramientas'],
            $this->categoryNames($section),
        );
        $this->assertSame(1, substr_count($section, '3 productos'), 'The active products below Cables.');
        $this->assertSame(5, substr_count($section, 'Sin productos'));
        $this->assertStringContainsString('href="'.route('categories.show', 'cables').'"', $section);
    }

    public function test_a_category_shows_its_image_when_it_has_one(): void
    {
        Category::factory()->create(['name' => 'Con foto', 'image' => 'categories/con-foto.jpg', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Sin foto', 'image' => null, 'sort_order' => 2]);

        $section = $this->section($this->get('/')->getContent(), 'categorias-titulo');

        $this->assertSame(['Con foto', 'Sin foto'], $this->categoryNames($section));
        $this->assertSame(1, substr_count($section, '<img '), 'The category without a photo gets an icon instead.');
        $this->assertStringContainsString('src="/storage/categories/con-foto.jpg"', $section);
    }

    public function test_the_offers_come_with_the_previous_price_and_the_biggest_discount_first(): void
    {
        Product::factory()->create(['name' => 'Descuento chico', 'list_price' => '900.00', 'compare_at_price' => '1000.00']);
        Product::factory()->create(['name' => 'Descuento grande', 'list_price' => '500.00', 'compare_at_price' => '1000.00']);
        Product::factory()->create(['name' => 'Sin oferta']);
        Product::factory()->create(['name' => 'Previo igual', 'list_price' => '500.00', 'compare_at_price' => '500.00']);
        Product::factory()->create(['name' => 'Previo menor', 'list_price' => '500.00', 'compare_at_price' => '400.00']);
        Product::factory()->create(['name' => 'Oferta oculta', 'list_price' => '500.00', 'compare_at_price' => '2000.00', 'is_active' => false]);

        $section = $this->section($this->get('/')->assertOk()->getContent(), 'ofertas-titulo');

        $this->assertSame(['Descuento grande', 'Descuento chico'], $this->productNames($section));
        preg_match_all('/>(-\d+%)</', $section, $badges);
        $this->assertSame(['-50%', '-10%'], $badges[1]);
        $this->assertStringContainsString(money('1000.00'), $section, 'The previous price, crossed out.');
        $this->assertStringContainsString('href="'.route('offers.index').'"', $section);
    }

    public function test_at_most_eight_offers_and_eight_new_products_are_shown(): void
    {
        Product::factory()->count(10)->onSale()->create();

        $html = $this->get('/')->getContent();

        $this->assertCount(8, $this->productNames($this->section($html, 'ofertas-titulo')));
        $this->assertCount(8, $this->productNames($this->section($html, 'novedades-titulo')));
    }

    public function test_the_newest_products_come_first_and_hidden_ones_do_not_appear(): void
    {
        Product::factory()->create(['name' => 'Más viejo', 'created_at' => now()->subDays(30)]);
        Product::factory()->create(['name' => 'Más nuevo', 'created_at' => now()->subDay()]);
        Product::factory()->create(['name' => 'Intermedio', 'created_at' => now()->subDays(10)]);
        Product::factory()->inactive()->create(['name' => 'Oculto', 'created_at' => now()]);

        $section = $this->section($this->get('/')->getContent(), 'novedades-titulo');

        $this->assertSame(['Más nuevo', 'Intermedio', 'Más viejo'], $this->productNames($section));
        $this->assertStringContainsString('href="'.route('catalog.index', ['orden' => 'novedades']).'"', $section);
    }

    public function test_the_cards_link_to_the_product_pages(): void
    {
        $product = Product::factory()->create(['slug' => 'cable-especial']);

        $this->get('/')->assertSee('href="'.route('products.show', $product).'"', false);
        $this->assertStringEndsWith('/producto/cable-especial', route('products.show', $product));
    }

    public function test_a_card_shows_its_brand_and_says_when_there_is_no_stock(): void
    {
        Product::factory()->create(['name' => 'Agotado', 'stock' => '0.00', 'brand_id' => Brand::factory()->create(['name' => 'Schneider Electric'])->id]);
        Product::factory()->create(['name' => 'Disponible', 'stock' => '5.00']);

        $section = $this->section($this->get('/')->getContent(), 'novedades-titulo');

        $this->assertStringContainsString('Schneider Electric', $section);
        $this->assertSame(1, substr_count($section, 'Sin stock'));
    }

    public function test_guests_see_the_list_price_and_professionals_their_own(): void
    {
        Product::factory()->create(['name' => 'Cable', 'list_price' => '1000.00', 'professional_price' => '900.00']);

        $this->get('/')
            ->assertSee(money('1000.00'))
            ->assertDontSee(money('900.00'))
            ->assertDontSee('Precio profesional');

        $this->actingAs(User::factory()->professional()->create())
            ->get('/')
            ->assertSee(money('900.00'))
            ->assertDontSee(money('1000.00'))
            ->assertSee('Precio profesional');
    }

    public function test_an_empty_store_still_renders_without_the_empty_sections(): void
    {
        $html = $this->get('/')->assertOk()->assertSee('Ver catálogo')->getContent();

        foreach (['categorias-titulo', 'ofertas-titulo', 'novedades-titulo'] as $heading) {
            $this->assertStringNotContainsString('id="'.$heading.'"', $html);
        }
    }
}
