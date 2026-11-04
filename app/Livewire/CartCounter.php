<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CartCounter extends Component
{
    /**
     * Number of items in the current cart. Computed (not a public property)
     * so the browser can never set it. Stays at zero until the cart exists
     * (stage 5).
     */
    #[Computed]
    public function count(): int
    {
        return 0;
    }

    public function render(): View
    {
        return view('livewire.cart-counter');
    }
}
