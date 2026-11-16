<?php

namespace App\Livewire;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    /**
     * Number of different products in the current cart. Computed (not a
     * public property) so the browser can never set it.
     */
    #[Computed]
    public function count(): int
    {
        return app(CartService::class)->count();
    }

    /**
     * Other components announce changes to the cart: drawing the component
     * again reads the count from the database.
     */
    #[On('cart-updated')]
    public function refresh(): void
    {
        unset($this->count);
    }

    public function render(): View
    {
        return view('livewire.cart-counter');
    }
}
