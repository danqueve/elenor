<?php

namespace App\View\Components\Layout;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class CategoryBar extends Component
{
    /**
     * Main categories shown in the desktop bar.
     *
     * Fixed list until the categories table exists (stage 2); after that it
     * is read from the database.
     *
     * @return list<array{name: string, slug: string}>
     */
    public function categories(): array
    {
        return array_map(
            fn (string $name) => ['name' => $name, 'slug' => Str::slug($name)],
            ['Cables', 'Protección eléctrica', 'Mecanismos', 'Iluminación LED', 'Canalización', 'Herramientas'],
        );
    }

    public function render(): View
    {
        return view('components.layout.category-bar', ['categories' => $this->categories()]);
    }
}
