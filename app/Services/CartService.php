<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
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
