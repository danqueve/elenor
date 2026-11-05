<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The header search with suggestions. It is a normal GET form (it works
 * without JavaScript); Livewire only adds the dropdown while typing.
 */
class SearchBox extends Component
{
    public string $query = '';

    public function mount(): void
    {
        // Keep the term on the results page.
        $this->query = mb_substr((string) request('q', ''), 0, 100);
    }

    /**
     * The best matches by name, brand, SKU or manufacturer code.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        return app(ProductCatalog::class)->suggestions($this->query, auth()->user());
    }

    public function render(): View
    {
        return view('livewire.search-box', ['user' => auth()->user()]);
    }
}
