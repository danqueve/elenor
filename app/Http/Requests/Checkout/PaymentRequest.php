<?php

namespace App\Http\Requests\Checkout;

use App\Enums\PaymentMethod;
use App\Services\Checkout\CheckoutSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout step 2: how the customer pays, and an optional note.
 */
class PaymentRequest extends FormRequest
{
    /**
     * Guests can fill in the steps: the login is asked when confirming.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $offered = $this->offered();

        $this->merge([
            // With a single option the form doesn't show the choice.
            'payment_method' => $this->input('payment_method', count($offered) === 1 ? $offered[0]->value : null),
            'notes' => is_string($this->input('notes')) ? trim($this->input('notes')) : $this->input('notes'),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->only($this->offered())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'forma de pago',
            'notes' => 'comentarios',
        ];
    }

    /**
     * @return list<PaymentMethod>
     */
    private function offered(): array
    {
        return PaymentMethod::offeredFor(app(CheckoutSession::class)->shippingMethod());
    }
}
