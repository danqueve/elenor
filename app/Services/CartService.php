<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartContents;
use App\Services\Cart\CartException;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartProblem;
use App\Support\Decimal;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * The cart of whoever is browsing: the signed-in customer's (by user id) or
 * the visitor's (by session id). Carts store products and quantities only;
 * prices are calculated here every time the cart is read, for that customer.
 */
class CartService
{
    /**
     * The current cart, or null if this visitor has none yet.
     */
    public function current(): ?Cart
    {
        $user = Auth::user();

        return $user !== null
            ? Cart::query()->where('user_id', $user->getKey())->first()
            : Cart::query()->where('session_id', Session::getId())->first();
    }

    /**
     * How many different products the cart has (the header counter).
     */
    public function count(): int
    {
        return $this->current()?->items()->count() ?? 0;
    }

    /**
     * Add a quantity of a product (on top of what the cart already has).
     *
     * @return string The product's new quantity in the cart ("3.00").
     *
     * @throws CartException
     */
    public function add(Product $product, mixed $quantity): string
    {
        $quantity = $this->validQuantity($product, $quantity);

        return DB::transaction(function () use ($product, $quantity) {
            $cart = $this->currentOrCreate();
            $item = $cart->items()->where('product_id', $product->getKey())->lockForUpdate()->first();

            $total = bcadd($item->quantity ?? '0', $quantity, 2);
            $this->ensureAvailable($product, $total, $item?->quantity);

            $item === null
                ? $cart->items()->create(['product_id' => $product->getKey(), 'quantity' => $total])
                : $item->update(['quantity' => $total]);

            return $total;
        });
    }

    /**
     * Replace the quantity of a product that is already in the cart.
     *
     * @throws CartException
     */
    public function update(int $productId, mixed $quantity): string
    {
        return DB::transaction(function () use ($productId, $quantity) {
            $item = $this->currentItem($productId);

            if ($item === null) {
                throw new CartException('Ese producto ya no está en tu carrito.');
            }

            $product = $item->product;
            $quantity = $this->validQuantity($product, $quantity);
            $this->ensureAvailable($product, $quantity);

            $item->update(['quantity' => $quantity]);

            return $quantity;
        });
    }

    public function remove(int $productId): void
    {
        $this->current()?->items()->where('product_id', $productId)->delete();
    }

    /**
     * The cart's lines with the prices this customer pays and anything that
     * prevents buying them. Pass the cart to read one other than the current.
     */
    public function contents(?User $user, ?Cart $cart = null): CartContents
    {
        $cart ??= $this->current();

        if ($cart === null) {
            return new CartContents([]);
        }

        $items = $cart->items()
            ->with(['product' => fn ($query) => $query->with('brand')->withFirstImage()])
            ->orderBy('id')
            ->get();

        return new CartContents($items->map(function (CartItem $item) use ($user) {
            $price = $item->product->priceFor($user);

            return new CartLine(
                product: $item->product,
                quantity: $item->quantity,
                unitPrice: $price,
                subtotal: Decimal::multiply($price, $item->quantity),
                problem: $this->problemWith($item->product, $item->quantity),
            );
        })->all());
    }

    /**
     * Why a quantity of a product can't be bought right now, or null if it can.
     * The checkout asks again under a row lock before taking the stock.
     */
    public function problemWith(Product $product, string $quantity): ?CartProblem
    {
        return match (true) {
            ! $product->is_active => CartProblem::Unavailable,
            ! $product->unit->accepts($quantity) => CartProblem::InvalidQuantity,
            ! $product->isInStock() => CartProblem::OutOfStock,
            bccomp($quantity, $product->stock, 2) > 0 => CartProblem::NotEnoughStock,
            default => null,
        };
    }

    private function currentOrCreate(): Cart
    {
        $user = Auth::user();

        // createOrFirst: two requests at once can't create two carts (the
        // columns are unique); the second one gets the first one's cart.
        return $user !== null
            ? Cart::query()->createOrFirst(['user_id' => $user->getKey()])
            : Cart::query()->createOrFirst(['session_id' => Session::getId()]);
    }

    private function currentItem(int $productId): ?CartItem
    {
        $cart = $this->current();

        return $cart?->items()->where('product_id', $productId)->with('product')->lockForUpdate()->first();
    }

    /**
     * @throws CartException
     */
    private function validQuantity(Product $product, mixed $input): string
    {
        $quantity = Quantity::parse($input);

        if ($quantity === null || bccomp($quantity, '0', 2) <= 0) {
            throw new CartException('Ingresá una cantidad válida.');
        }

        if (! $product->unit->accepts($quantity)) {
            throw new CartException($product->unit->stepHint());
        }

        return $quantity;
    }

    /**
     * @param  string|null  $inCart  What the cart already had, to word the message.
     *
     * @throws CartException
     */
    private function ensureAvailable(Product $product, string $quantity, ?string $inCart = null): void
    {
        if (! $product->is_active) {
            throw new CartException('Este producto no está disponible.');
        }

        if (! $product->isInStock()) {
            throw new CartException('Este producto está sin stock por el momento.');
        }

        if (bccomp($quantity, $product->stock, 2) > 0) {
            $available = $product->unit->format($product->stock);

            throw new CartException($inCart === null
                ? "No tenemos stock para esa cantidad: hay {$available}."
                : "No tenemos stock para esa cantidad: hay {$available} y ya tenés {$product->unit->format($inCart)} en el carrito.");
        }
    }

    /**
     * Fold the cart a visitor built before signing in into their own cart.
     *
     * Guest carts are keyed by session id, and logging in regenerates the
     * session, so this must be called with the id read before the login.
     * Quantities of a product that is in both carts are added up. Prices are
     * not stored in carts: they are always calculated when the cart is read.
     */
    public function mergeGuestCart(string $sessionId, User $user): void
    {
        DB::transaction(function () use ($sessionId, $user) {
            $guestCart = Cart::query()->where('session_id', $sessionId)->lockForUpdate()->first();

            // Nothing to merge, or a parallel request already did it.
            if ($guestCart === null) {
                return;
            }

            $guestItems = $guestCart->items()->get();

            if ($guestItems->isEmpty()) {
                $guestCart->delete();

                return;
            }

            $userCart = Cart::query()->where('user_id', $user->getKey())->lockForUpdate()->first();

            if ($userCart === null) {
                // The customer had no cart yet: the guest cart simply becomes theirs.
                $guestCart->update(['user_id' => $user->getKey(), 'session_id' => null]);

                return;
            }

            foreach ($guestItems as $item) {
                $existing = $userCart->items()->where('product_id', $item->product_id)->lockForUpdate()->first();

                if ($existing === null) {
                    $userCart->items()->create(['product_id' => $item->product_id, 'quantity' => $item->quantity]);
                } else {
                    $existing->update(['quantity' => bcadd($existing->quantity, $item->quantity, 2)]);
                }
            }

            $guestCart->delete();
        });
    }
}
