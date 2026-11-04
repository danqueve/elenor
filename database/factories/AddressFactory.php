<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Cities of the NOA with their postal codes: city, province, postal code.
     */
    private const PLACES = [
        ['San Miguel de Tucumán', 'Tucumán', '4000'],
        ['Yerba Buena', 'Tucumán', '4107'],
        ['Tafí Viejo', 'Tucumán', '4103'],
        ['Banda del Río Salí', 'Tucumán', '4109'],
        ['Concepción', 'Tucumán', '4146'],
        ['Salta', 'Salta', '4400'],
        ['San Salvador de Jujuy', 'Jujuy', '4600'],
        ['San Fernando del Valle de Catamarca', 'Catamarca', '4700'],
        ['Santiago del Estero', 'Santiago del Estero', '4200'],
        ['La Rioja', 'La Rioja', '5300'],
    ];

    /**
     * A random city of the NOA. Shared with the order factory, which needs
     * the same data for its address snapshot.
     *
     * @return array{string, string, string} City, province and postal code.
     */
    public static function place(): array
    {
        return fake()->randomElement(self::PLACES);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$city, $province, $postalCode] = self::place();

        return [
            'user_id' => User::factory(),
            'street' => fake()->streetName(),
            'number' => (string) fake()->numberBetween(1, 4999),
            'city' => $city,
            'province' => $province,
            'postal_code' => $postalCode,
            'reference' => fake()->optional(0.6)->randomElement([
                'Portón negro, timbre B',
                'Entre calles, casa con rejas blancas',
                'Local sobre la avenida',
                'Dejar con el encargado',
            ]),
        ];
    }
}
