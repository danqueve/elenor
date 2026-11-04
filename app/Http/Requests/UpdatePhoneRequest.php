<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePhoneRequest extends FormRequest
{
    /**
     * The route is behind the "auth" middleware: any signed-in customer
     * can set their own phone.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', ' ', trim((string) $this->input('phone'))),
        ]);
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'bail',
                'required',
                'string',
                'max:30',
                'regex:/^\+?[0-9\s()\-]+$/',
                // Argentine numbers: 10 digits with area code, up to 13 as +54 9 ...
                function (string $attribute, mixed $value, Closure $fail): void {
                    $digits = strlen(preg_replace('/\D/', '', (string) $value));

                    if ($digits < 10 || $digits > 13) {
                        $fail('Ingresá el teléfono con código de área, por ejemplo 381 4123456.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'El teléfono solo puede tener números, espacios, guiones, paréntesis y el signo + al principio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phone' => 'teléfono'];
    }
}
