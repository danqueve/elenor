<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ReadsPageHtml;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use ReadsPageHtml;
    use RefreshDatabase;

    /**
     * The rows of the "Ficha técnica": label => value, in the order shown.
     *
     * @return array<string, string>
     */
    private function technicalSheet(string $html): array
    {
        preg_match_all('/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/s', $this->section($html, 'ficha-tecnica'), $rows);

        return array_combine(
            array_map(fn (string $label) => html_entity_decode($label, ENT_QUOTES), $rows[1]),
            array_map(fn (string $value) => html_entity_decode($value, ENT_QUOTES), $rows[2]),
        );
    }

    /**
     * The JSON-LD that search engines read, decoded.
     *
     * @return array<string, mixed>
     */
    private function structuredData(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }

    private function show(Product $product): string
    {
        return $this->get(route('products.show', $product))->assertOk()->getContent();
    }

    public function test_it_shows_the_product_with_its_brand_codes_price_and_stock(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cable unipolar 2,5 mm² celeste',
            'sku' => 'CAB-0002',
            'manufacturer_code' => 'PR-2547',
            'brand_id' => Brand::factory()->create(['name' => 'Prysmian'])->id,
            'list_price' => '12500.50',
            'stock' => '120.00',
            'description' => 'Cable para instalaciones domiciliarias.',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('<title>Cable unipolar 2,5 mm² celeste | '.config('app.name').'</title>', false)
            ->assertSee('Prysmian')
            ->assertSee('CAB-0002')
            ->assertSee('PR-2547')
            ->assertSee(money('12500.50'))
            ->assertSee('Precio por unidad')
            ->assertSee('Disponible: 120 u. en stock')
            ->assertSee('Cable para instalaciones domiciliarias.')
            ->assertDontSee('Precio profesional');
    }

    public function test_the_technical_sheet_has_the_fixed_data_first_and_then_the_attributes(): void
    {
        $product = Product::factory()
            ->withSpecs(['Sección' => '2,5 mm²', 'Color' => 'Celeste'])
            ->create([
                'sku' => 'CAB-0002',
                'manufacturer_code' => 'PR-2547',
                'brand_id' => Brand::factory()->create(['name' => 'Prysmian'])->id,
            ]);

        $this->assertSame([
            'Marca' => 'Prysmian',
            'SKU' => 'CAB-0002',
            'Código de fabricante' => 'PR-2547',
            'Se vende por' => 'unidad',
            'Sección' => '2,5 mm²',
            'Color' => 'Celeste',
        ], $this->technicalSheet($this->show($product)));
    }

    public function test_the_manufacturer_code_is_left_out_when_the_product_has_none(): void
    {
        $product = Product::factory()->create(['manufacturer_code' => null]);

        $html = $this->show($product);

        $this->assertArrayNotHasKey('Código de fabricante', $this->technicalSheet($html));
        $this->assertStringNotContainsString('Código de fabricante', $html);
    }

    public function test_the_breadcrumb_goes_from_the_home_down_to_the_product(): void
    {
        $cables = Category::factory()->create(['name' => 'Cables', 'slug' => 'cables']);
        $unipolares = Category::factory()->childOf($cables)->create(['name' => 'Unipolares', 'slug' => 'unipolares']);
        $product = Product::factory()->create(['name' => 'Cable 2,5', 'category_id' => $unipolares->id]);

        preg_match('/<nav aria-label="Ruta de navegación".*?<\/nav>/s', $this->show($product), $nav);
        preg_match_all('/<(?:a|span)\b[^>]*>([^<]+)<\/(?:a|span)>/', $nav[0], $items);

        $this->assertSame(['Inicio', 'Catálogo', 'Cables', 'Unipolares', 'Cable 2,5'], $items[1]);

        foreach ([route('home'), route('catalog.index'), route('categories.show', 'cables'), route('categories.show', 'unipolares')] as $url) {
            $this->assertStringContainsString('href="'.$url.'"', $nav[0]);
        }

        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Cable 2,5</', $nav[0]);
    }

    public function test_an_offer_shows_the_previous_price_and_the_discount(): void
    {
        $product = Product::factory()->create([
            'list_price' => '9800.00',
            'professional_price' => '8700.00',
            'compare_at_price' => '11500.00',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(money('11500.00'))
            ->assertSee(money('9800.00'))
            ->assertSee('-15%')
            ->assertDontSee(money('8700.00'))
            ->assertDontSee('Precio profesional');
    }

    public function test_approved_professionals_see_their_price_instead_of_the_offer(): void
    {
        $product = Product::factory()->create([
            'list_price' => '9800.00',
            'professional_price' => '8700.00',
            'compare_at_price' => '11500.00',
        ]);

        $this->actingAs(User::factory()->professional()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(money('8700.00'))
            ->assertSee('Precio profesional')
            ->assertDontSee(money('9800.00'))
            ->assertDontSee(money('11500.00'))
            ->assertDontSee('-15%');
    }

    public function test_professionals_waiting_for_approval_see_the_public_price(): void
    {
        $product = Product::factory()->create(['list_price' => '9800.00', 'professional_price' => '8700.00']);

        $this->actingAs(User::factory()->pendingProfessional()->create())
            ->get(route('products.show', $product))
            ->assertSee(money('9800.00'))
            ->assertDontSee(money('8700.00'))
            ->assertDontSee('Precio profesional');
    }

    /**
     * @return array<string, array{ProductUnit, string, string}>
     */
    public static function stockTexts(): array
    {
        return [
            'units' => [ProductUnit::Unit, '120.00', 'Disponible: 120 u. en stock'],
            'meters, with the thousands separator' => [ProductUnit::Meter, '1200.00', 'Disponible: 1.200 m en stock'],
            'meters with decimals' => [ProductUnit::Meter, '12.50', 'Disponible: 12,5 m en stock'],
            'a single roll' => [ProductUnit::Roll, '1.00', 'Disponible: 1 rollo en stock'],
            'several rolls' => [ProductUnit::Roll, '18.00', 'Disponible: 18 rollos en stock'],
        ];
    }

    #[DataProvider('stockTexts')]
    public function test_the_stock_is_shown_in_the_unit_the_product_is_sold_by(ProductUnit $unit, string $stock, string $expected): void
    {
        $product = Product::factory()->soldBy($unit)->create(['stock' => $stock]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee($expected)
            ->assertSee('Precio por '.mb_strtolower($unit->label()))
            ->assertDontSee('Sin stock por el momento');
    }

    public function test_a_product_without_stock_is_still_shown_and_says_so(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Sin stock por el momento')
            ->assertDontSee('en stock');
    }

    public function test_the_whatsapp_button_opens_a_chat_with_the_product_already_written(): void
    {
        config(['shop.contact.whatsapp' => '+54 9 381 555-1234']);
        $product = Product::factory()->create(['name' => 'Cable & cía 2,5', 'sku' => 'CAB-0002']);

        $html = $this->show($product);
        $this->assertStringContainsString('Consultar por WhatsApp', $html);

        // The footer also links to the shop's WhatsApp, but without a message.
        preg_match_all('#href="(https://wa\.me/[^"]*\?text=[^"]*)"#', $html, $links);
        $this->assertCount(1, $links[1]);

        $this->assertStringStartsWith('https://wa.me/5493815551234?text=', $links[1][0]);
        parse_str(parse_url($links[1][0], PHP_URL_QUERY), $query);
        $this->assertSame(
            "¡Hola! Quería consultar por este producto: Cable & cía 2,5 (SKU CAB-0002).\n".route('products.show', $product),
            $query['text'],
        );
    }

    public function test_without_a_whatsapp_number_there_is_no_button(): void
    {
        config(['shop.contact.whatsapp' => null]);

        $this->get(route('products.show', Product::factory()->create()))
            ->assertOk()
            ->assertDontSee('Consultar por WhatsApp');
    }

    public function test_hidden_and_unknown_products_are_not_found(): void
    {
        $hidden = Product::factory()->inactive()->create();

        $this->get(route('products.show', $hidden))->assertNotFound();
        $this->get('/producto/no-existe')->assertNotFound();
    }

    public function test_the_photos_are_shown_in_order_with_thumbnails_when_there_are_several(): void
    {
        $product = Product::factory()->create(['name' => 'Con fotos']);
        ProductImage::factory()->for($product)->create(['path' => 'products/second.jpg', 'sort_order' => 2]);
        ProductImage::factory()->for($product)->create(['path' => 'products/first.jpg', 'sort_order' => 1]);

        $html = $this->show($product);

        $this->assertStringContainsString('<img src="/storage/products/first.jpg" x-bind:src="images[active]" alt="Con fotos"', $html);
        $this->assertStringContainsString('aria-label="Fotos del producto"', $html);
        $this->assertStringContainsString('aria-label="Ver la foto 2"', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.url('/storage/products/first.jpg').'">', $html);
    }

    public function test_a_single_photo_has_no_thumbnails(): void
    {
        $product = Product::factory()->withImages(1)->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Fotos del producto')
            ->assertDontSee('Este producto todavía no tiene fotos');
    }

    public function test_a_product_without_photos_shows_a_placeholder(): void
    {
        $html = $this->show(Product::factory()->create());

        $this->assertStringContainsString('Este producto todavía no tiene fotos', $html);
        $this->assertStringNotContainsString('og:image', $html);
    }

    public function test_search_engines_get_the_public_data_of_the_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cable unipolar',
            'sku' => 'CAB-0002',
            'manufacturer_code' => 'PR-2547',
            'brand_id' => Brand::factory()->create(['name' => 'Prysmian'])->id,
            'list_price' => '12500.50',
            'professional_price' => '11000.00',
            'description' => 'Para instalaciones domiciliarias.',
        ]);

        // Even a professional gets the public price in the markup that gets cached and shared.
        $html = $this->actingAs(User::factory()->professional()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->getContent();

        $url = route('products.show', $product);
        $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Cable unipolar">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.$url.'">', $html);
        $this->assertStringContainsString('<meta name="description" content="Para instalaciones domiciliarias.">', $html);

        $data = $this->structuredData($html);
        $this->assertSame('Product', $data['@type']);
        $this->assertSame('Cable unipolar', $data['name']);
        $this->assertSame('CAB-0002', $data['sku']);
        $this->assertSame('PR-2547', $data['mpn']);
        $this->assertSame('Prysmian', $data['brand']['name']);
        $this->assertSame('ARS', $data['offers']['priceCurrency']);
        $this->assertSame('12500.50', $data['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $data['offers']['availability']);
    }

    public function test_the_structured_data_says_when_there_is_no_stock(): void
    {
        $html = $this->show(Product::factory()->outOfStock()->create());

        $this->assertSame('https://schema.org/OutOfStock', $this->structuredData($html)['offers']['availability']);
    }

    public function test_what_the_store_typed_cannot_break_out_of_the_page(): void
    {
        $name = '</script><script>alert(1)</script>';
        $product = Product::factory()->create(['name' => $name, 'description' => '"><img src=x onerror=alert(2)>']);

        $html = $this->show($product);

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertSame($name, $this->structuredData($html)['name']);
    }

    public function test_related_products_are_the_same_subcategory_with_the_same_brand_first(): void
    {
        $subcategory = Category::factory()->childOf(Category::factory()->create())->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['name' => 'Principal', 'category_id' => $subcategory->id, 'brand_id' => $brand->id]);

        foreach (['Alfa', 'Beta', 'Gamma'] as $name) {
            Product::factory()->create(['name' => $name, 'category_id' => $subcategory->id]);
        }
        foreach (['Yota', 'Zeta'] as $name) {
            Product::factory()->create(['name' => $name, 'category_id' => $subcategory->id, 'brand_id' => $brand->id]);
        }
        Product::factory()->inactive()->create(['name' => 'Oculto', 'category_id' => $subcategory->id, 'brand_id' => $brand->id]);

        $related = $this->section($this->show($product), 'relacionados');

        $this->assertSame(['Yota', 'Zeta', 'Alfa', 'Beta'], $this->productNames($related));
    }

    public function test_when_the_subcategory_is_short_the_main_category_fills_the_rest(): void
    {
        $cables = Category::factory()->create();
        $unipolares = Category::factory()->childOf($cables)->create();
        $subterraneos = Category::factory()->childOf($cables)->create();
        $manuales = Category::factory()->childOf(Category::factory()->create())->create();

        $product = Product::factory()->create(['name' => 'Principal', 'category_id' => $unipolares->id]);
        Product::factory()->create(['name' => 'Hermano', 'category_id' => $unipolares->id]);
        foreach (['Primo 1', 'Primo 2', 'Primo 3', 'Primo 4'] as $name) {
            Product::factory()->create(['name' => $name, 'category_id' => $subterraneos->id]);
        }
        Product::factory()->create(['name' => 'De otro rubro', 'category_id' => $manuales->id]);

        $related = $this->section($this->show($product), 'relacionados');

        $this->assertSame(['Hermano', 'Primo 1', 'Primo 2', 'Primo 3'], $this->productNames($related));
    }

    public function test_the_section_is_left_out_when_there_is_nothing_to_suggest(): void
    {
        $product = Product::factory()->create();
        Product::factory()->inactive()->create(['category_id' => $product->category_id]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Productos relacionados');
    }
}
