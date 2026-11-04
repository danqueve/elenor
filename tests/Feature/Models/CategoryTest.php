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
