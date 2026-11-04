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
     * Run the database seeds. Needs the categories and brands to exist.
     * Safe to run more than once: products are matched by SKU and updated.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'name');

        foreach (require database_path('seeders/data/products.php') as $data) {
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
                    'stock' => $data['stock'],
                    'is_active' => true,
                ],
            );

            foreach ($data['specs'] as $name => $value) {
                $product->specs()->updateOrCreate(['name' => $name], ['value' => $value]);
            }
        }
    }
}
