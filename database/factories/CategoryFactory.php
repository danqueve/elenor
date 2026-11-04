<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Categoría '.fake()->unique()->numerify('####');

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'image' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * A subcategory of the given category.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->getKey()]);
    }
}
