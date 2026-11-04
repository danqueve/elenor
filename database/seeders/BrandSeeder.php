<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    private const BRANDS = [
        'Cambre',
        'Cimet',
        'DeWalt',
        'Fluke',
        'Genrod',
        'IMSA',
        'Knipex',
        'Macroled',
        'Osram',
        'Philips',
        'Prysmian',
        'Schneider Electric',
        'Siemens',
        'Sica',
        'Stanley',
        'Steck',
        'Tigre',
    ];

    /**
     * Run the database seeds. Safe to run more than once.
     */
    public function run(): void
    {
        foreach (self::BRANDS as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }
}
