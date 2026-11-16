<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Turns the customer's cart into an order.
 *
 * Everything happens in one transaction with the products' rows locked
 * (lockForUpdate): two customers buying the last unit at the same time are
 * served one after the other, and the second one finds no stock. Prices are
 * read from the products right there, never from the browser, and copied
 * into the order items.
 */
class OrderPlacer
{
    public function __construct(
        private CartService $carts,
        private ShippingZones $zones,
    ) {}

    /**
     * @throws CheckoutException
     */
    public function place(User $user, CheckoutData $data): Order
    {
        $order = DB::transaction(function () use ($user, $data) {
            $cart = Cart::query()->where('user_id', $user->getKey())->lockForUpdate()->first();
            $items = $cart?->items()->orderBy('product_id')->get() ?? collect();

            if ($items->isEmpty()) {
                throw new CheckoutException('Tu carrito está vacío.');
            }

            // Locked in id order, the same in every checkout, so two orders
            // with the same products can't wait for each other forever.
            $products = Product::query()
                ->whereKey($items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $problem = $product === null ? true : $this->carts->problemWith($product, $item->quantity);

                if ($problem !== null) {
                    throw new CheckoutException('Algunos productos de tu carrito cambiaron (stock o disponibilidad). Revisalos antes de confirmar.');
                }

                $price = $product->priceFor($user);

                $lines[] = [
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'unit_price' => $price,
                    'subtotal' => Decimal::multiply($price, $item->quantity),
                ];
            }

            $subtotal = Decimal::sum(array_column($lines, 'subtotal'));
            $shippingCost = $this->zones->costFor($data->shippingMethod, $data->address['province'] ?? null);

            $order = $user->orders()->create([
                // Temporary: the real number comes from the id, right below.
                'number' => 'tmp-'.Str::random(16),
                'status' => OrderStatus::Received,
                'shipping_method' => $data->shippingMethod,
                'shipping_cost' => $shippingCost,
                'payment_method' => $data->paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => $subtotal,
                'total' => bcadd($subtotal, $shippingCost ?? '0', 2),
                'notes' => $data->notes,
                ...$this->addressSnapshot($data),
            ]);

            $order->update(['number' => sprintf('EL-%06d', $order->id)]);

            foreach ($lines as $line) {
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'product_unit' => $product->unit,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);

                $product->update(['stock' => bcsub($product->stock, $line['quantity'], 2)]);
            }

            $order->statusHistory()->create(['status' => OrderStatus::Received, 'user_id' => null]);

            if ($data->shippingMethod === ShippingMethod::Delivery) {
                // Saved for the next purchase (once: the same address isn't repeated).
                $user->addresses()->firstOrCreate($data->address);
            }

            $cart->items()->delete();

            return $order;
        });

        // Queued, and only once the order is saved: a mail problem never
        // undoes a purchase.
        Mail::to($user)->queue(new OrderPlaced($order));

        return $order;
    }

    /**
     * @return array<string, ?string>
     */
    private function addressSnapshot(CheckoutData $data): array
    {
        $address = $data->address ?? [];

        return [
            'shipping_street' => $address['street'] ?? null,
            'shipping_number' => $address['number'] ?? null,
            'shipping_city' => $address['city'] ?? null,
            'shipping_province' => $address['province'] ?? null,
            'shipping_postal_code' => $address['postal_code'] ?? null,
            'shipping_reference' => $address['reference'] ?? null,
        ];
    }
}
