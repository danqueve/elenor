<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    private const VALUES = [
        'Sección' => ['1,5 mm²', '2,5 mm²', '4 mm²', '6 mm²'],
        'Amperaje' => ['10 A', '16 A', '20 A', '32 A'],
        'Color' => ['Rojo', 'Celeste', 'Negro', 'Blanco'],
        'Largo' => ['3 m', '5 m', '25 m', '100 m'],
    ];

    /**
     * A random name/value pair. A product can't repeat a name, so to give one
     * product several attributes pass the names explicitly (or use the
     * product factory's withSpecs()).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(array_keys(self::VALUES));

        return [
            'product_id' => Product::factory(),
            'name' => $name,
            'value' => fake()->randomElement(self::VALUES[$name]),
        ];
    }
}
