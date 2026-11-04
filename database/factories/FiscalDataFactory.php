<?php

namespace Database\Factories;

use App\Enums\IvaCondition;
use App\Models\FiscalData;
use App\Models\User;
use App\Support\Cuit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalData>
 */
class FiscalDataFactory extends Factory
{
    /**
     * A CUIT with a valid check digit, as 11 digits.
     */
    public static function cuit(): string
    {
        do {
            $firstTen = fake()->randomElement(['20', '27', '30', '33']).fake()->numerify('########');
            $checkDigit = Cuit::checkDigit($firstTen);
        } while ($checkDigit === null);

        return $firstTen.$checkDigit;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'cuit' => self::cuit(),
            'iva_condition' => fake()->randomElement(IvaCondition::cases()),
        ];
    }
}
