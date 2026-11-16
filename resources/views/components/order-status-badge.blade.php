@props(['status'])

<span {{ $attributes->class([
    'inline-block rounded-md px-2 py-1 text-xs font-bold',
    'bg-brand-electric text-brand-deep' => $status === App\Enums\OrderStatus::Delivered,
    'bg-red-100 text-red-800' => $status === App\Enums\OrderStatus::Cancelled,
    'bg-brand-deep text-brand-neutral' => ! in_array($status, [App\Enums\OrderStatus::Delivered, App\Enums\OrderStatus::Cancelled], true),
]) }}>{{ $status->label() }}</span>
