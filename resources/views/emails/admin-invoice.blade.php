@php
    $brandColor = $template->brand_color ?? '#0F52BA';
    $bg = '#0f172a';
    $line = '#334155';
    $muted = '#94a3b8';
    $text = '#e2e8f0';
    $head = '#ffffff';

    $logoSrc = null;
    $logoValue = $settings?->logo;

    if (filled($logoValue)) {
        if (str_starts_with($logoValue, 'http://') || str_starts_with($logoValue, 'https://')) {
            $logoSrc = $logoValue;
        } else {
            $cleanLogo = ltrim($logoValue, '/');
            $possiblePath = str_starts_with($cleanLogo, 'storage/')
                ? public_path($cleanLogo)
                : public_path('storage/'.$cleanLogo);

            if (! file_exists($possiblePath)) {
                $possiblePath = storage_path('app/public/'.str_replace('storage/', '', $cleanLogo));
            }

            if (file_exists($possiblePath)) {
                $logoSrc = $message->embed($possiblePath);
            }
        }
    }

    $subtotal = $invoice->subtotal();
    $discountAmount = $invoice->discountAmount();
    $grandTotal = $invoice->total();
    $currency = '৳';

    $formatMoney = fn ($amount) => $currency.number_format((float) $amount, 2);
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_no }}</title>
</head>

<body style="margin:0; padding:0; background:{{ $bg }}; font-family:Arial, Helvetica, sans-serif; color:{{ $text }};">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }};">
        <tr>
            <td>
                <div style="padding:36px 24px;">

                    {{-- Logo --}}
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" alt="{{ $settings?->site_name ?: config('app.name') }}"
                            width="120" style="max-width:120px; max-height:48px; height:auto; display:block; margin:0 0 24px;">
                    @endif

                    {{-- Greeting --}}
                    <p style="margin:0 0 18px; font-size:16px; font-weight:700; color:{{ $head }};">
                        Dear {{ $invoice->customer_name }},
                    </p>

                    <p style="margin:0 0 18px; font-size:14px; line-height:1.7; color:{{ $text }};">
                        {{ $invoice->note ?: 'Here is your invoice for the services listed below. Please find the detailed invoice attached as a PDF.' }}
                    </p>

                    <p style="margin:0 0 24px; font-size:12px; color:{{ $muted }};">
                        Invoice #{{ $invoice->invoice_no }} &middot;
                        {{ $invoice->issue_date?->format('M d, Y') ?: $invoice->created_at?->format('M d, Y') }}
                    </p>

                    {{-- Items --}}
                    @foreach ($invoice->items as $item)
                        @php
                            $lineTotal = (float) $item->quantity * (float) $item->unit_price;
                        @endphp
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                            <tr>
                                <td style="padding:14px 0;">
                                    <p style="margin:0; font-size:14px; font-weight:600; color:{{ $head }};">
                                        {{ $item->title }}
                                    </p>
                                    <p style="margin:3px 0 0; font-size:12px; color:{{ $muted }};">
                                        Qty {{ number_format((float) $item->quantity, 1) }}
                                        @if ($item->unit_price)
                                            &times; {{ $formatMoney($item->unit_price) }}
                                        @endif
                                    </p>
                                </td>
                                <td align="right" style="padding:14px 0;">
                                    <p style="margin:0; font-size:14px; font-weight:600; color:{{ $head }};">
                                        {{ $formatMoney($lineTotal) }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    @endforeach

                    {{-- Totals --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid {{ $brandColor }};">
                        @if ($discountAmount > 0)
                            <tr>
                                <td style="padding:12px 0 0; font-size:13px; color:{{ $text }};">Subtotal</td>
                                <td align="right" style="padding:12px 0 0; font-size:13px; color:{{ $head }};">
                                    {{ $formatMoney($subtotal) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-size:13px; color:{{ $text }};">
                                    Discount
                                    @if ($invoice->discount_type === 'percentage')
                                        ({{ number_format((float) $invoice->discount_value, 0) }}%)
                                    @endif
                                </td>
                                <td align="right" style="padding:4px 0; font-size:13px; color:#f87171;">
                                    -{{ $formatMoney($discountAmount) }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="padding:10px 0 0; font-size:16px; font-weight:700; color:{{ $brandColor }};">
                                Total
                            </td>
                            <td align="right" style="padding:10px 0 0; font-size:20px; font-weight:700; color:{{ $brandColor }};">
                                {{ $formatMoney($grandTotal) }}
                            </td>
                        </tr>
                    </table>

                    {{-- Terms --}}
                    <p style="margin:26px 0 0; font-size:12px; line-height:1.7; color:{{ $text }};">
                        {{ $invoice->terms ?: ($template->terms_text ?: 'Payment is due by the due date shown above. Please contact us if you have any questions regarding this invoice.') }}
                    </p>

                    @if ($settings?->location || $settings?->email || $settings?->phone)
                        <p style="margin:18px 0 0; font-size:11px; color:{{ $muted }};">
                            @if ($settings?->location){{ $settings->location }} &middot; @endif
                            @if ($settings?->email){{ $settings->email }} &middot; @endif
                            @if ($settings?->phone){{ $settings->phone }}@endif
                        </p>
                    @endif

                </div>
            </td>
        </tr>
    </table>
</body>

</html>
