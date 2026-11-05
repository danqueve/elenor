<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Products on offer: SKU => the price they had before. The current price
     * (in the data file) is the offer price.
     */
    private const OFFERS = [
        'CAB-0006' => '74900.00',
        'CAB-0007' => '112000.00',
        'PRO-0001' => '11500.00',
        'PRO-0005' => '49900.00',
        'MEC-0001' => '6900.00',
        'LED-0001' => '3600.00',
        'LED-0005' => '12500.00',
        'LED-0007' => '23900.00',
        'HER-0003' => '22000.00',
        'HER-0006' => '139000.00',
    ];

    /**
     * Run the database seeds. Needs the categories and brands to exist.
     * Safe to run more than once: products are matched by SKU and updated.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'name');

        foreach (require database_path('seeders/data/products.php') as $index => $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'manufacturer_code' => $data['code'],
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name'].' '.$data['brand']),
                    'description' => $data['description'],
                    'category_id' => $categories[$data['category']],
                    'brand_id' => $data['brand'] === null ? null : $brands[$data['brand']],
                    'unit' => $data['unit'],
                    'list_price' => $data['price'],
                    'professional_price' => $data['pro'],
                    'compare_at_price' => self::OFFERS[$data['sku']] ?? null,
                    'stock' => $data['stock'],
                    'is_active' => true,
                ],
            );

            // Spread the creation dates (the first products are the newest) so
            // the "novedades" section has a believable order.
            if ($product->wasRecentlyCreated) {
                $product->forceFill(['created_at' => now()->subDays($index * 2)])->saveQuietly();
            }

            foreach ($data['specs'] as $name => $value) {
                $product->specs()->updateOrCreate(['name' => $name], ['value' => $value]);
            }
        }
    }
}
