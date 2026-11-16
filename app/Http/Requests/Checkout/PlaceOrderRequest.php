<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Checkout step 3: "Confirmar pedido". Nothing is typed here: the order is
 * built from the cart and the earlier steps, all read on the server. The
 * route is behind "auth" and "phone".
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
