<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Http\Requests\Checkout\DeliveryRequest;
use App\Http\Requests\Checkout\PaymentRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Services\Cart\CartContents;
use App\Services\CartService;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CheckoutSession;
use App\Services\Checkout\OrderPlacer;
use App\Services\Checkout\ShippingZones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checkout in three steps: delivery, payment and confirmation. Guests can
 * go through the first two; Google is asked for only to confirm the order.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private CheckoutSession $checkout,
        private ShippingZones $zones,
    ) {}

    public function start(): RedirectResponse
    {
        return redirect()->route('checkout.delivery');
    }

    public function delivery(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->cartNotReady($cart = $this->cart($request))) {
            return $redirect;
        }

        // Filled in from this checkout, or else from the customer's last address.
        $values = $this->checkout->stored('delivery');

        if ($values === [] && $request->user() !== null) {
            $values = $request->user()->addresses()->latest('id')->first()?->only([
                'street', 'number', 'city', 'province', 'postal_code', 'reference',
            ]) ?? [];
        }

        return view('checkout.delivery', [
            'cart' => $cart,
            'values' => $values,
            'methods' => ShippingMethod::offered(),
            'provinces' => $this->zones->provinces(),
            'costs' => collect($this->zones->provinces())
                ->mapWithKeys(fn (string $province) => [$province => $this->zones->costFor(ShippingMethod::Delivery, $province)]),
        ]);
    }

    public function storeDelivery(DeliveryRequest $request): RedirectResponse
    {
        $this->checkout->putDelivery($request->validated());

        return redirect()->route('checkout.payment');
    }

    public function payment(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->cartNotReady($cart = $this->cart($request))) {
            return $redirect;
        }

        if ($this->checkout->delivery() === null) {
            return redirect()->route('checkout.delivery');
        }

        return view('checkout.payment', [
            'cart' => $cart,
            'values' => $this->checkout->stored('payment'),
            'methods' => PaymentMethod::offeredFor($this->checkout->shippingMethod()),
        ]);
    }

    public function storePayment(PaymentRequest $request): RedirectResponse
    {
        if ($this->checkout->delivery() === null) {
            return redirect()->route('checkout.delivery');
        }

        $this->checkout->putPayment($request->validated());

        return redirect()->route('checkout.confirm');
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->cartNotReady($cart = $this->cart($request))) {
            return $redirect;
        }

        $data = $this->checkout->data();

        if ($data === null) {
            return redirect()->route($this->checkout->delivery() === null ? 'checkout.delivery' : 'checkout.payment');
        }

        // A visitor signs in from here and comes back to confirm (the cart
        // and these steps are kept).
        if ($request->user() === null) {
            $request->session()->put('url.intended', route('checkout.confirm'));
        }

        $shipping = $this->zones->costFor($data->shippingMethod, $data->address['province'] ?? null);

        return view('checkout.confirm', [
            'cart' => $cart,
            'data' => $data,
            'shippingCost' => $shipping,
            'total' => bcadd($cart->subtotal, $shipping ?? '0', 2),
        ]);
    }

    public function place(PlaceOrderRequest $request, OrderPlacer $placer): RedirectResponse
    {
        $data = $this->checkout->data();

        if ($data === null) {
            return redirect()->route('checkout.delivery');
        }

        try {
            $order = $placer->place($request->user(), $data);
        } catch (CheckoutException $exception) {
            return redirect()->route('cart.index')->with('status', $exception->getMessage());
        }

        $this->checkout->forget();

        return redirect()
            ->route('orders.show', $order)
            ->with('status', "¡Gracias! Recibimos tu pedido {$order->number}. Te mandamos el detalle por email.");
    }

    private function cart(Request $request): CartContents
    {
        return $this->carts->contents($request->user());
    }

    /**
     * An empty cart or one with something that can't be bought goes back to
     * the cart page.
     */
    private function cartNotReady(CartContents $cart): ?RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        if ($cart->hasProblems()) {
            return redirect()->route('cart.index')->with('status', 'Revisá tu carrito: hay productos que cambiaron.');
        }

        return null;
    }
}
