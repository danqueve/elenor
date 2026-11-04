<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Main categories and their subcategories, in display order.
     */
    private const TREE = [
        'Cables' => ['Unipolares', 'Taller y cordones', 'Subterráneos'],
        'Protección eléctrica' => ['Termomagnéticas', 'Disyuntores diferenciales', 'Tableros'],
        'Mecanismos' => ['Tomacorrientes', 'Llaves e interruptores', 'Fichas y alargues'],
        'Iluminación LED' => ['Lámparas LED', 'Paneles y plafones', 'Reflectores'],
        'Canalización' => ['Caños y conductos', 'Cajas y accesorios'],
        'Herramientas' => ['Herramientas manuales', 'Medición', 'Herramientas eléctricas'],
    ];

    /**
     * Run the database seeds. Safe to run more than once: categories are
     * matched by slug and updated.
     */
    public function run(): void
    {
        $position = 0;

        foreach (self::TREE as $name => $children) {
            $parent = $this->save($name, null, ++$position * 10);

            foreach ($children as $index => $child) {
                $this->save($child, $parent, ($index + 1) * 10);
            }
        }
    }

    private function save(string $name, ?Category $parent, int $position): Category
    {
        return Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'parent_id' => $parent?->getKey(), 'sort_order' => $position],
        );
    }
}
