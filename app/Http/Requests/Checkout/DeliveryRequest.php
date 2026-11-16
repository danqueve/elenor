<?php

namespace App\Http\Requests\Checkout;

use App\Enums\ShippingMethod;
use App\Services\Checkout\ShippingZones;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout step 1: how the order arrives and where.
 */
class DeliveryRequest extends FormRequest
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
        $clean = fn (string $key) => is_string($this->input($key))
            ? preg_replace('/\s+/', ' ', trim($this->input($key)))
            : $this->input($key);

        $this->merge([
            // With a single option the form doesn't show the choice.
            'shipping_method' => $this->input('shipping_method', ShippingMethod::offered()[0]->value),
            'street' => $clean('street'),
            'number' => $clean('number'),
            'city' => $clean('city'),
            'postal_code' => is_string($this->input('postal_code')) ? mb_strtoupper(str_replace(' ', '', $this->input('postal_code'))) : $this->input('postal_code'),
            'reference' => $clean('reference'),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        $delivery = Rule::requiredIf(fn () => $this->input('shipping_method') === ShippingMethod::Delivery->value);

        return [
            'shipping_method' => ['required', Rule::enum(ShippingMethod::class)->only(ShippingMethod::offered())],
            'street' => [$delivery, 'nullable', 'string', 'max:150'],
            'number' => [$delivery, 'nullable', 'string', 'max:20'],
            'city' => [$delivery, 'nullable', 'string', 'max:120'],
            'province' => [$delivery, 'nullable', 'string', Rule::in(app(ShippingZones::class)->provinces())],
            // 4000, or the newer CPA format: T4000ABC.
            'postal_code' => [$delivery, 'nullable', 'string', 'regex:/^([A-Z]\d{4}[A-Z]{3}|\d{4})$/'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'province.in' => 'Por ahora enviamos solo a Tucumán y el resto del NOA.',
            'postal_code.regex' => 'Ingresá el código postal de 4 números (por ejemplo 4000) o el CPA completo (T4000ABC).',
            'number.required' => 'Ingresá la altura. Si no tiene número, escribí "S/N".',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shipping_method' => 'forma de entrega',
            'street' => 'calle',
            'number' => 'altura',
            'city' => 'localidad',
            'province' => 'provincia',
            'postal_code' => 'código postal',
            'reference' => 'referencia',
        ];
    }
}
