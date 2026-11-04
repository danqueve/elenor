<?php

namespace App\View\Components\Layout;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class CategoryBar extends Component
{
    /**
     * Main (top-level) categories shown in the desktop bar.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->roots()->ordered()->get(['name', 'slug']);
    }

    public function render(): View
    {
        return view('components.layout.category-bar', ['categories' => $this->categories()]);
    }
}
