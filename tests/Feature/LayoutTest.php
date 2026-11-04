<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LayoutTest extends TestCase
{
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
            'account' => ['/cuenta'],
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

    public function test_the_category_bar_lists_the_main_categories(): void
    {
        $this->get('/')->assertSeeInOrder([
            'Cables',
            'Protección eléctrica',
            'Mecanismos',
            'Iluminación LED',
            'Canalización',
            'Herramientas',
        ]);
    }

    public function test_the_category_bar_highlights_the_selected_category(): void
    {
        $this->get('/catalogo?categoria=cables')
            ->assertSee('href="'.route('catalog.index', ['categoria' => 'cables']).'"', false)
            ->assertSee('aria-current="page"', false);
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
