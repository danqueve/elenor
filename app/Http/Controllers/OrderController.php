<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartException;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Mis pedidos": the customer's orders, their detail and "Repetir pedido".
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order, 'view');

        $order->load(['items.product', 'statusHistory']);

        return view('orders.show', [
            'order' => $order,
            'timeline' => $this->timeline($order),
            'bank' => array_filter(config('shop.bank', [])),
        ]);
    }

    /**
     * Put the order's products back in the cart, in the same quantities,
     * at today's prices. What can't be added (no longer sold, out of stock)
     * is listed; a quantity that isn't fully in stock is added up to the stock.
     */
    public function reorder(Request $request, Order $order, CartService $cart): RedirectResponse
    {
        $this->authorizeOwner($request, $order, 'reorder');

        $order->load('items.product');

        $skipped = [];
        $added = 0;

        // What the cart already has of each product: it counts against the stock.
        $inCart = collect($cart->contents(null)->lines)
            ->mapWithKeys(fn ($line) => [$line->product->id => $line->quantity]);

        foreach ($order->items as $item) {
            $product = $item->product;
            $quantity = $product === null ? null : $this->availableQuantity($product, $item->quantity, $inCart->get($product->id, '0'));

            if ($quantity === null) {
                $skipped[] = $item->product_name;

                continue;
            }

            try {
                $cart->add($product, $quantity);
                $added++;
            } catch (CartException) {
                $skipped[] = $item->product_name;
            }
        }

        $message = match (true) {
            $added === 0 => 'No pudimos agregar ningún producto: ya no están disponibles o no tienen stock.',
            $skipped === [] => 'Agregamos los productos del pedido a tu carrito, con los precios de hoy.',
            default => 'Agregamos lo que había disponible, con los precios de hoy. No pudimos agregar: '.implode(', ', $skipped).'.',
        };

        return redirect()->route('cart.index')->with('status', $message);
    }

    /**
     * Orders are numbered in sequence: someone else's order is a 404, not a
     * 403, so the numbers of other customers' orders can't be probed.
     */
    private function authorizeOwner(Request $request, Order $order, string $ability): void
    {
        abort_if(Gate::forUser($request->user())->denies($ability, $order), 404);
    }

    /**
     * How much of the ordered quantity can be added now (not more than the
     * stock left after what the cart already has), or null if nothing can.
     */
    private function availableQuantity(Product $product, string $ordered, string $inCart): ?string
    {
        if (! $product->is_active || ! $product->isInStock()) {
            return null;
        }

        $left = bcsub($product->stock, $inCart, 2);
        $quantity = bccomp($ordered, $left, 2) > 0 ? $left : $ordered;

        // Round down to what the unit can be sold in (whole units, half metres).
        $step = $product->unit->step();
        $quantity = bcsub($quantity, bcmod($quantity, $step, 2), 2);

        return bccomp($quantity, '0', 2) > 0 ? $quantity : null;
    }

    /**
     * The usual path of an order with the date each step was reached. A
     * cancelled order shows what happened up to the cancellation.
     *
     * @return list<array{status: OrderStatus, at: ?Carbon, done: bool, current: bool}>
     */
    private function timeline(Order $order): array
    {
        $reached = $order->statusHistory->keyBy(fn ($entry) => $entry->status->value);

        $path = $order->status === OrderStatus::Cancelled
            ? [...$order->statusHistory->pluck('status')->unique()->values()->all()]
            : [
                OrderStatus::Received,
                OrderStatus::Confirmed,
                OrderStatus::Preparing,
                $order->shipping_method === ShippingMethod::Pickup ? OrderStatus::ReadyForPickup : OrderStatus::Shipped,
                OrderStatus::Delivered,
            ];

        return array_map(fn (OrderStatus $status) => [
            'status' => $status,
            'at' => $reached->get($status->value)?->created_at,
            'done' => $reached->has($status->value),
            'current' => $status === $order->status,
        ], $path);
    }
}
