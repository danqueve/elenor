<?php

namespace App\View\Components\Layout;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * The helpers are private on purpose: Blade exposes the public methods of a
 * component to its view, and they would replace the data passed by render().
 */
class CategoryBar extends Component
{
    public function render(): View
    {
        return view('components.layout.category-bar', [
            'categories' => $this->categories(),
            'activeRootId' => $this->activeRootId(),
        ]);
    }

    /**
     * Main (top-level) categories shown in the desktop bar.
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()->roots()->ordered()->get(['id', 'name', 'slug']);
    }

    /**
     * The main category of the page being shown (the one the page belongs to,
     * whether it is the category itself, a subcategory or one of its products),
     * to mark it in the bar.
     */
    private function activeRootId(): ?int
    {
        $route = request()->route();
        $category = $route?->parameter('category');
        $product = $route?->parameter('product');

        return match (true) {
            $category instanceof Category => $category->rootId(),
            $product instanceof Product => Category::query()->find($product->category_id)?->rootId(),
            default => null,
        };
    }
}
