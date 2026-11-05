<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * The categories to link from the catalog: with the number of active products
 * each has, counting everything below it.
 */
class CatalogNavigation
{
    /**
     * The links for the sidebar of the page being shown.
     *
     * - No category: the top-level categories.
     * - A category with subcategories: its subcategories.
     * - A category without them: its siblings, with the current one marked.
     *
     * @return array{title: string, parent: ?Category, items: Collection<int, object{category: Category, total: int}>}
     */
    public function for(?Category $current): array
    {
        $categories = Category::query()->ordered()->get();
        $byParent = $categories->groupBy(fn (Category $category) => $category->parent_id ?? 0);
        $totals = $this->totals($categories, $byParent);

        if ($current === null) {
            return ['title' => 'Categorías', 'parent' => null, 'items' => $this->items($byParent->get(0), $totals)];
        }

        if ($byParent->has($current->id)) {
            return ['title' => $current->name, 'parent' => $current, 'items' => $this->items($byParent->get($current->id), $totals)];
        }

        $parent = $current->parent_id === null ? null : $categories->firstWhere('id', $current->parent_id);

        return [
            'title' => $parent?->name ?? $current->name,
            'parent' => $parent,
            'items' => $parent === null ? new Collection : $this->items($byParent->get($parent->id), $totals),
        ];
    }

    /**
     * The top-level categories with their product totals.
     *
     * @return Collection<int, object{category: Category, total: int}>
     */
    public function roots(): Collection
    {
        return $this->for(null)['items'];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int|string, Collection<int, Category>>  $byParent
     * @return array<int, int> Category id => active products in it and below it.
     */
    private function totals(Collection $categories, Collection $byParent): array
    {
        $own = Product::query()->active()->toBase()
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $totals = [];

        foreach ($byParent->get(0, new Collection) as $root) {
            $this->rollUp($root, $byParent, $own, $totals);
        }

        return $totals;
    }

    /**
     * @param  Collection<int|string, Collection<int, Category>>  $byParent
     * @param  Collection<int, int|string>  $own
     * @param  array<int, int>  $totals
     */
    private function rollUp(Category $category, Collection $byParent, Collection $own, array &$totals): int
    {
        $total = (int) ($own[$category->id] ?? 0);

        foreach ($byParent->get($category->id, new Collection) as $child) {
            $total += $this->rollUp($child, $byParent, $own, $totals);
        }

        return $totals[$category->id] = $total;
    }

    /**
     * @param  Collection<int, Category>|null  $categories
     * @param  array<int, int>  $totals
     * @return Collection<int, object{category: Category, total: int}>
     */
    private function items(?Collection $categories, array $totals): Collection
    {
        return ($categories ?? new Collection)
            ->map(fn (Category $category) => (object) ['category' => $category, 'total' => $totals[$category->id] ?? 0])
            ->values();
    }
}
