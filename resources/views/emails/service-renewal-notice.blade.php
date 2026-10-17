<x-mail::message>
# {{ $heading }}

Hello {{ $order->user?->name ?: ($order->full_name ?: 'Customer') }},

{{ $messageText }}

**Order:** {{ $order->order_no }}

**Billing cycle:** {{ ucfirst($order->billing_cycle) }}

@if ($kind === 'confirmed')
**Service period:** {{ $order->start_date->format('d M Y') }} – {{ $order->end_date->format('d M Y') }}

**Amount paid:** {{ $order->currency }} {{ number_format((float) $order->amount, 2) }}
@else
**Expiry date:** {{ $order->end_date->format('d M Y') }}

Renewal becomes active after payment is confirmed. Early renewal preserves your remaining paid time.
@endif

<x-mail::button :url="$actionUrl">
{{ $kind === 'confirmed' ? 'View renewal' : 'Renew service' }}
</x-mail::button>

Thanks,<br>
{{ $siteName ?? config('app.name') }}
</x-mail::message>
