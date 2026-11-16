<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Services\Cart\CartException;
use App\Services\CartService;
use App\Support\Quantity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Quantity and "Agregar al carrito" on the product page. The browser sends
 * only the quantity: the product id is locked and the price is never here.
 */
class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public string $quantity = '1';

    /** What the cart has of this product after adding, to confirm it. */
    #[Locked]
    public ?string $added = null;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->quantity = Quantity::format($product->unit->step());
    }

    #[Computed]
    public function product(): Product
    {
        return Product::query()->findOrFail($this->productId);
    }

    public function increment(): void
    {
        $this->step(1);
    }

    public function decrement(): void
    {
        $this->step(-1);
    }

    public function add(CartService $cart): void
    {
        $this->resetErrorBag();
        $this->added = null;

        try {
            $this->added = $cart->add($this->product, $this->quantity);
        } catch (CartException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.cart.add-to-cart');
    }

    /**
     * Move the quantity one step (a unit, a roll or half a metre), never
     * below the minimum.
     */
    private function step(int $direction): void
    {
        $step = $this->product->unit->step();
        $current = Quantity::parse($this->quantity) ?? $step;
        $next = $direction > 0 ? bcadd($current, $step, 2) : bcsub($current, $step, 2);

        if (bccomp($next, $step, 2) < 0) {
            $next = $step;
        }

        $this->quantity = Quantity::format($next);
    }
}
