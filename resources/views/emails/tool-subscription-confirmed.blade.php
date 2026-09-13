@php
    $brandColor = $template->brand_color ?? '#0F52BA';
    $bg = '#0f172a';
    $panel = '#0b1020';
    $line = '#1e293b';
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
            $logoPath = str_starts_with($cleanLogo, 'storage/')
                ? public_path($cleanLogo)
                : public_path('storage/'.$cleanLogo);

            if (! file_exists($logoPath)) {
                $logoPath = storage_path('app/public/'.str_replace('storage/', '', $cleanLogo));
            }

            if (file_exists($logoPath)) {
                $logoSrc = isset($message) && method_exists($message, 'embed')
                    ? $message->embed($logoPath)
                    : asset(str_replace('public/', '', $cleanLogo));
            }
        }
    }

    $currency = '৳';
    $amount = (float) ($invoice?->total() ?? $subscription->amount);
    $subtotal = $invoice ? $invoice->subtotal() : $amount;
    $discount = $invoice ? $invoice->discountAmount() : 0;
    $siteName = $settings?->site_name ?: config('app.name');
    $contactPhone = $settings?->phone;
    $confirmedAt = $subscription->verified_at ?? now();
    $formatMoney = fn ($value) => $currency.number_format((float) $value, 2);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed</title>
</head>

<body style="margin:0; padding:0; background:{{ $bg }}; font-family:Arial, Helvetica, sans-serif; color:{{ $text }};">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }}; padding:28px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:640px; background:{{ $panel }}; border:1px solid {{ $line }}; border-radius:20px; overflow:hidden;">

                    {{-- Letterhead --}}
                    <tr>
                        <td style="padding:30px 34px 0;">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $siteName }}" width="120"
                                    style="max-width:140px; max-height:52px; height:auto; display:block;">
                            @else
                                <h1 style="margin:0; font-size:22px; font-weight:800; color:{{ $head }};">{{ $siteName }}</h1>
                            @endif

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 2px; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Reference</p>
                                        <p style="margin:0; font-size:18px; font-weight:800; color:{{ $head }};">Subscription Confirmed</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0 0 2px; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Date</p>
                                        <p style="margin:0; font-size:13px; font-weight:700; color:{{ $text }};">{{ $confirmedAt->format('M d, Y') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:28px 34px 0;">
                            <p style="margin:0 0 14px; font-size:17px; font-weight:800; color:{{ $head }};">
                                Dear {{ $subscription->user?->name }},
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                Great news! Your payment has been verified and your
                                <strong style="color:{{ $head }};">{{ $subscription->toolCategory?->name }}</strong>
                                subscription is now <strong style="color:#34d399;">confirmed and active</strong>.
                                Your invoice is shown below.
                            </p>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                You can start using the tool right away.
                            </p>
                        </td>
                    </tr>

                    {{-- Total bar --}}
                    <tr>
                        <td style="padding:24px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:{{ $brandColor }}22; border:1px solid {{ $brandColor }}55; border-radius:14px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Amount Paid</p>
                                        <p style="margin:6px 0 0; font-size:26px; font-weight:800; color:{{ $brandColor }};">{{ $formatMoney($amount) }}</p>
                                    </td>
                                    <td align="right" style="padding:16px 20px;">
                                        <p style="margin:0; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Status</p>
                                        <p style="margin:6px 0 0; font-size:13px; font-weight:800; color:#34d399;">Active</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Subscription / invoice details --}}
                    <tr>
                        <td style="padding:14px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Tool</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->toolCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Plan</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->toolPlan?->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Billing Cycle</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ ucfirst($subscription->billing_cycle) }}</td>
                                </tr>
                                @if ($invoice)
                                    <tr>
                                        <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Invoice #</td>
                                        <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $invoice->invoice_no }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Transaction ID</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->transaction_id ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Expires On</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->expires_at?->format('M d, Y') ?: '—' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Invoice items + totals --}}
                    @if ($invoice && $invoice->items->isNotEmpty())
                        <tr>
                            <td style="padding:20px 34px 0;">
                                <p style="margin:0 0 8px; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Invoice Summary</p>
                                @foreach ($invoice->items as $item)
                                    @php $lineTotal = (float) $item->quantity * (float) $item->unit_price; @endphp
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                                        <tr>
                                            <td style="padding:12px 0;">
                                                <p style="margin:0; font-size:14px; font-weight:600; color:{{ $head }};">{{ $item->title }}</p>
                                                @if ($item->description)
                                                    <p style="margin:3px 0 0; font-size:12px; color:{{ $muted }};">{{ $item->description }}</p>
                                                @endif
                                            </td>
                                            <td align="right" style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">{{ $formatMoney($lineTotal) }}</td>
                                        </tr>
                                    </table>
                                @endforeach

                                <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid {{ $brandColor }};">
                                    @if ($discount > 0)
                                        <tr>
                                            <td style="padding:10px 0 0; font-size:13px; color:{{ $text }};">Subtotal</td>
                                            <td align="right" style="padding:10px 0 0; font-size:13px; color:{{ $head }};">{{ $formatMoney($subtotal) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0; font-size:13px; color:{{ $text }};">Discount</td>
                                            <td align="right" style="padding:4px 0; font-size:13px; color:#f87171;">-{{ $formatMoney($discount) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding:10px 0 0; font-size:16px; font-weight:700; color:{{ $brandColor }};">Total</td>
                                        <td align="right" style="padding:10px 0 0; font-size:20px; font-weight:700; color:{{ $brandColor }};">{{ $formatMoney($amount) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td style="padding:24px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('account.tool-subscriptions') }}"
                                            style="display:inline-block; background:{{ $brandColor }}; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:12px;">
                                            View My Subscriptions
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:30px 34px 24px;">
                            <p style="margin:0; padding-top:20px; border-top:1px solid {{ $line }}; text-align:center; font-size:11px; line-height:1.8; color:{{ $muted }};">
                                {{ $siteName }}
                                @if ($settings?->location)
                                    &middot; {{ $settings->location }}
                                @endif
                                @if ($settings?->email)
                                    &middot; {{ $settings->email }}
                                @endif
                                @if ($contactPhone)
                                    &middot; {{ $contactPhone }}
                                @endif
                                <br>
                                Thank you for choosing {{ $siteName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>