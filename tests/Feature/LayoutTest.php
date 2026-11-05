<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    // The category bar reads the categories from the database.
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/'],
            'catalog' => ['/catalogo'],
            'search' => ['/buscar?q=cable'],
            'quotes' => ['/presupuestos'],
            'favorites' => ['/favoritos'],
            'cart' => ['/carrito'],
            'orders' => ['/pedidos'],
            'login' => ['/ingresar'],
        ];
    }

    #[DataProvider('pages')]
    public function test_every_page_renders_inside_the_main_layout(string $url): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee('lang="es"', false)
            ->assertSee('Saltar al contenido')
            ->assertSee('role="search"', false);
    }

    public function test_the_account_is_only_for_signed_in_customers(): void
    {
        $this->get('/cuenta')->assertRedirect(route('login'));
    }

    public function test_the_header_has_login_favorites_and_cart(): void
    {
        $this->get('/')
            ->assertSee('Ingresar')
            ->assertSee('Favoritos')
            ->assertSee('Carrito')
            ->assertSee('sin productos');
    }

    public function test_the_search_box_keeps_the_search_term(): void
    {
        $this->get('/buscar?q=cable 2,5')
            ->assertSee('value="cable 2,5"', false);
    }

    public function test_the_category_bar_lists_the_main_categories_in_order(): void
    {
        $this->seed(CategorySeeder::class);

        $this->get('/')
            ->assertSeeInOrder([
                'Cables',
                'Protección eléctrica',
                'Mecanismos',
                'Iluminación LED',
                'Canalización',
                'Herramientas',
            ])
            ->assertDontSee('Unipolares');
    }

    public function test_the_category_bar_follows_the_categories_in_the_database(): void
    {
        Category::factory()->create(['name' => 'Ferretería eléctrica', 'slug' => 'ferreteria-electrica', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'Cables de cobre', 'slug' => 'cables-de-cobre', 'sort_order' => 1]);

        $this->get('/')->assertSeeInOrder(['Cables de cobre', 'Ferretería eléctrica']);
    }

    /**
     * The HTML of the desktop category bar only (other parts of a page can
     * mark things as current too).
     */
    private function categoryBar(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();

        preg_match('#<nav aria-label="Categorías" class="hidden.*?</nav>#s', $html, $bar);

        return $bar[0] ?? '';
    }

    public function test_the_category_bar_links_to_the_category_pages(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertStringContainsString('href="'.route('categories.show', 'cables').'"', $this->categoryBar('/'));
    }

    public function test_the_category_bar_marks_the_current_main_category(): void
    {
        $this->seed(CategorySeeder::class);
        $cables = 'href="'.route('categories.show', 'cables').'"';

        $this->assertMatchesRegularExpression('#'.preg_quote($cables, '#').'\s+aria-current="page"#', $this->categoryBar('/categoria/cables'));
        $this->assertSame(1, substr_count($this->categoryBar('/categoria/cables'), 'aria-current="page"'));
    }

    public function test_a_subcategory_marks_its_main_category_in_the_bar(): void
    {
        $this->seed(CategorySeeder::class);
        $cables = 'href="'.route('categories.show', 'cables').'"';

        $this->assertMatchesRegularExpression('#'.preg_quote($cables, '#').'\s+aria-current="page"#', $this->categoryBar('/categoria/unipolares'));
    }

    public function test_a_product_page_marks_the_main_category_of_the_product(): void
    {
        $this->seed(CategorySeeder::class);
        $product = Product::factory()->create(['category_id' => Category::query()->where('slug', 'tomacorrientes')->value('id')]);
        $mecanismos = 'href="'.route('categories.show', 'mecanismos').'"';

        $this->assertMatchesRegularExpression('#'.preg_quote($mecanismos, '#').'\s+aria-current="page"#', $this->categoryBar('/producto/'.$product->slug));
    }

    public function test_nothing_is_marked_in_the_bar_outside_a_category(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertStringNotContainsString('aria-current', $this->categoryBar('/catalogo'));
        $this->assertStringNotContainsString('aria-current', $this->categoryBar('/'));
    }

    public function test_the_mobile_menu_has_the_five_destinations_in_order(): void
    {
        $this->get('/')->assertSeeInOrder([
            'aria-label="Navegación principal"',
            'Inicio',
            'Catálogo',
            'Presupuestos',
            'Pedidos',
            'Cuenta',
        ], false);
    }

    public function test_the_mobile_menu_marks_the_current_section(): void
    {
        $this->get('/pedidos')->assertSeeInOrder([
            'href="'.route('orders.index').'"',
            'aria-current="page"',
        ], false);
    }

    public function test_the_footer_shows_the_contact_details(): void
    {
        config([
            'shop.contact.address' => 'Calle Falsa 123, San Miguel de Tucumán',
            'shop.contact.phone' => '+54 381 555-1234',
            'shop.contact.whatsapp' => '5493815551234',
            'shop.contact.email' => 'ventas@elenor.test',
        ]);

        $this->get('/')
            ->assertSee('Calle Falsa 123, San Miguel de Tucumán')
            ->assertSee('href="tel:+543815551234"', false)
            ->assertSee('href="https://wa.me/5493815551234"', false)
            ->assertSee('href="mailto:ventas@elenor.test"', false);
    }

    public function test_the_footer_skips_contact_details_that_are_not_set(): void
    {
        config([
            'shop.contact.address' => null,
            'shop.contact.phone' => null,
            'shop.contact.whatsapp' => null,
            'shop.contact.email' => null,
            'shop.contact.hours' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('wa.me')
            ->assertDontSee('mailto:', false)
            ->assertDontSee('tel:', false);
    }
}
