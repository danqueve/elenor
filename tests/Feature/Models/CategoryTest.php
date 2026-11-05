<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_roots_are_the_categories_without_a_parent_in_display_order(): void
    {
        $second = Category::factory()->create(['name' => 'Beta', 'sort_order' => 20]);
        $first = Category::factory()->create(['name' => 'Alfa', 'sort_order' => 10]);
        $tied = Category::factory()->create(['name' => 'Aaa', 'sort_order' => 20]);
        Category::factory()->childOf($first)->create();

        $this->assertSame(
            [$first->id, $tied->id, $second->id],
            Category::query()->roots()->ordered()->pluck('id')->all(),
        );
    }

    public function test_children_come_in_display_order(): void
    {
        $parent = Category::factory()->create();
        $last = Category::factory()->childOf($parent)->create(['sort_order' => 20]);
        $first = Category::factory()->childOf($parent)->create(['sort_order' => 10]);

        $this->assertSame(
            [$first->id, $last->id],
            $parent->fresh(['children'])->children->pluck('id')->all(),
        );
    }

    public function test_the_breadcrumb_goes_from_the_main_category_down_to_this_one(): void
    {
        $root = Category::factory()->create(['name' => 'Cables']);
        $child = Category::factory()->childOf($root)->create(['name' => 'Unipolares']);
        $grandchild = Category::factory()->childOf($child)->create(['name' => 'Rollos']);
        Category::factory()->childOf($root)->create(['name' => 'Subterráneos']);

        $this->assertSame(['Cables', 'Unipolares', 'Rollos'], $grandchild->ancestorsAndSelf()->pluck('name')->all());
        $this->assertSame(['Cables', 'Unipolares'], $child->ancestorsAndSelf()->pluck('name')->all());
        $this->assertSame(['Cables'], $root->ancestorsAndSelf()->pluck('name')->all());
    }

    public function test_the_main_category_of_any_category(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $grandchild = Category::factory()->childOf($child)->create();

        $this->assertSame($root->id, $root->rootId());
        $this->assertSame($root->id, $child->rootId());
        $this->assertSame($root->id, $grandchild->rootId());
    }

    public function test_the_tile_icon_is_chosen_by_slug_with_a_default(): void
    {
        $this->assertSame('bolt', Category::factory()->make(['slug' => 'cables'])->icon());
        $this->assertSame('light-bulb', Category::factory()->make(['slug' => 'iluminacion-led'])->icon());
        $this->assertSame('squares-2x2', Category::factory()->make(['slug' => 'algo-nuevo'])->icon());
    }

    public function test_the_image_url_comes_from_the_public_disk(): void
    {
        $this->assertNull(Category::factory()->make(['image' => null])->imageUrl());
        $this->assertSame('/storage/categories/cables.jpg', Category::factory()->make(['image' => 'categories/cables.jpg'])->imageUrl());
    }

    public function test_a_category_is_found_by_its_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'cables']);

        $this->assertSame('cables', $category->getRouteKey());
        $this->assertStringEndsWith('/categoria/cables', route('categories.show', $category));
    }

    public function test_descendant_ids_include_the_category_and_everything_below_it(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $grandchild = Category::factory()->childOf($child)->create();
        $sibling = Category::factory()->childOf($root)->create();
        $other = Category::factory()->create();
        Category::factory()->childOf($other)->create();

        $this->assertEqualsCanonicalizing(
            [$root->id, $child->id, $grandchild->id, $sibling->id],
            $root->descendantIds()->all(),
        );
        $this->assertEqualsCanonicalizing([$child->id, $grandchild->id], $child->descendantIds()->all());
        $this->assertSame([$grandchild->id], $grandchild->descendantIds()->all());
    }
}
