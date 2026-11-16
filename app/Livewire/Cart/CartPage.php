<?php

namespace App\Livewire\Cart;

use App\Enums\ShippingMethod;
use App\Services\Cart\CartContents;
use App\Services\Cart\CartException;
use App\Services\CartService;
use App\Services\Checkout\ShippingZones;
use App\Support\Quantity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The cart page (/carrito). The browser only sends product ids and
 * quantities; lines, prices and totals are read from the server each time.
 */
#[Title('Carrito')]
class CartPage extends Component
{
    /**
     * The quantities as typed in the inputs, by product id. Only a draft:
     * changing it does nothing until updatedQuantities() saves it through
     * the cart service, which validates it.
     *
     * @var array<int|string, string>
     */
    public array $quantities = [];

    public function mount(): void
    {
        $this->syncQuantities();
    }

    #[Computed]
    public function cart(): CartContents
    {
        return app(CartService::class)->contents(auth()->user());
    }

    /**
     * Whether some delivery zone has no fixed cost (it is agreed afterwards).
     */
    #[Computed]
    public function shippingHasFixedCosts(): bool
    {
        $zones = app(ShippingZones::class);

        foreach ($zones->provinces() as $province) {
            if ($zones->costFor(ShippingMethod::Delivery, $province) === null) {
                return false;
            }
        }

        return $zones->provinces() !== [];
    }

    /**
     * A quantity input changed (wire:model.live.change; in Livewire 4 plain
     * .change only updates the browser state, without a request): save it.
     */
    public function updatedQuantities(mixed $value, string $productId): void
    {
        $this->save((int) $productId, $value);
    }

    public function increment(int $productId): void
    {
        $this->move($productId, 1);
    }

    public function decrement(int $productId): void
    {
        $this->move($productId, -1);
    }

    public function remove(int $productId, CartService $cart): void
    {
        $cart->remove($productId);

        $this->changed();
    }

    public function render(): View
    {
        return view('livewire.cart.cart-page');
    }

    private function move(int $productId, int $direction): void
    {
        $line = collect($this->cart->lines)->first(fn ($line) => $line->product->id === $productId);

        if ($line === null) {
            return;
        }

        $step = $line->product->unit->step();
        $next = $direction > 0 ? bcadd($line->quantity, $step, 2) : bcsub($line->quantity, $step, 2);

        if (bccomp($next, '0', 2) <= 0) {
            return;
        }

        $this->save($productId, $next);
    }

    private function save(int $productId, mixed $quantity): void
    {
        $this->resetErrorBag();

        try {
            app(CartService::class)->update($productId, $quantity);
        } catch (CartException $exception) {
            $this->addError("quantities.{$productId}", $exception->getMessage());
        }

        $this->changed();
    }

    /**
     * Read the cart again and tell the header counter.
     */
    private function changed(): void
    {
        unset($this->cart);
        $this->syncQuantities(keepErrors: true);
        $this->dispatch('cart-updated');
    }

    /**
     * Put the saved quantities back in the inputs. A line with an error keeps
     * what the customer typed, so they can see and fix it.
     */
    private function syncQuantities(bool $keepErrors = false): void
    {
        $quantities = [];

        foreach ($this->cart->lines as $line) {
            $id = $line->product->id;
            $quantities[$id] = $keepErrors && $this->getErrorBag()->has("quantities.{$id}")
                ? (string) ($this->quantities[$id] ?? '')
                : Quantity::format($line->quantity);
        }

        $this->quantities = $quantities;
    }
}
