<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private const NAMES = [
        'Cable unipolar',
        'Termomagnética bipolar',
        'Disyuntor diferencial',
        'Tomacorriente doble',
        'Llave de un punto',
        'Lámpara LED',
        'Reflector LED',
        'Caño corrugado',
        'Pinza universal',
    ];

    /**
     * Money and quantities are decimal strings with two decimals, like the
     * database stores them.
     */
    private static function decimal(int|float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sku = strtoupper(fake()->unique()->bothify('???-####'));
        $name = fake()->randomElement(self::NAMES).' modelo '.fake()->numerify('###');
        $price = fake()->numberBetween(1500, 250000);

        return [
            'sku' => $sku,
            'manufacturer_code' => strtoupper(fake()->bothify('??-####-?')),
            'name' => $name,
            'slug' => Str::slug($name.' '.$sku),
            'description' => fake()->randomElement([
                'Producto de calidad para instalaciones domiciliarias.',
                'Ideal para obras y trabajos de electricidad en general.',
                'Uso profesional y doméstico.',
            ]),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit' => ProductUnit::Unit,
            'list_price' => self::decimal($price),
            'professional_price' => self::decimal($price * 0.88),
            'stock' => self::decimal(fake()->numberBetween(1, 500)),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => '0.00']);
    }

    public function withoutProfessionalPrice(): static
    {
        return $this->state(['professional_price' => null]);
    }

    public function soldBy(ProductUnit $unit): static
    {
        return $this->state(['unit' => $unit]);
    }

    /**
     * Create the technical sheet: ['Sección' => '2,5 mm²', 'Color' => 'Rojo'].
     *
     * @param  array<string, string>  $specs
     */
    public function withSpecs(array $specs): static
    {
        return $this->afterCreating(function (Product $product) use ($specs) {
            foreach ($specs as $name => $value) {
                $product->specs()->create(['name' => $name, 'value' => $value]);
            }
        });
    }

    public function withImages(int $count = 2): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            ProductImage::factory()->count($count)->for($product)->sequence(
                fn ($sequence) => ['sort_order' => $sequence->index],
            )->create();
        });
    }
}
