<x-mail::message>
# ¡Gracias por tu compra, {{ $order->user->firstName() }}!

Recibimos tu pedido **{{ $order->number }}**. Te vamos a avisar cuando lo confirmemos.

<x-mail::table>
| Producto | Cantidad | Subtotal |
|:---------|:--------:|---------:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->product_unit->format($item->quantity) }} | {{ money($item->subtotal) }} |
@endforeach
| **Subtotal** | | **{{ money($order->subtotal) }}** |
| Envío | | {{ $order->shipping_cost === null ? 'A coordinar' : ($order->shipping_cost === '0.00' ? 'Sin cargo' : money($order->shipping_cost)) }} |
| **Total** | | **{{ money($order->total) }}**{{ $order->shipping_cost === null ? ' + envío' : '' }} |
</x-mail::table>

@if ($order->shipping_street)
**Envío a:** {{ $order->shipping_street }} {{ $order->shipping_number }}, {{ $order->shipping_city }}, {{ $order->shipping_province }} ({{ $order->shipping_postal_code }}).
@if ($order->shipping_cost === null)
El costo del envío depende de tu zona: te lo confirmamos por WhatsApp antes de despachar.
@endif
@endif

**Forma de pago:** {{ $order->payment_method->label() }}.

@if ($order->payment_method === App\Enums\PaymentMethod::BankTransfer && $bank !== [])
<x-mail::panel>
**Datos para la transferencia**

@isset($bank['holder'])Titular: {{ $bank['holder'] }}<br>@endisset
@isset($bank['bank'])Banco: {{ $bank['bank'] }}<br>@endisset
@isset($bank['cbu'])CBU: {{ $bank['cbu'] }}<br>@endisset
@isset($bank['alias'])Alias: {{ $bank['alias'] }}<br>@endisset
@isset($bank['cuit'])CUIT: {{ $bank['cuit'] }}@endisset

Cuando transfieras, mandanos el comprobante por WhatsApp con el número de pedido.
</x-mail::panel>
@endif

<x-mail::button :url="$url">
Ver mi pedido
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
