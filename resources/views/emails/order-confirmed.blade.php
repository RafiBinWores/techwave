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

    $logoSrc = \App\Services\UploadStorage::emailLogo($logoValue, $message ?? null);

    $orderNo = $order->order_no ?? 'ORD-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

    $customerName = $order->full_name ?? ($order->user?->name ?? 'Valued Customer');

    $isPricingPlan = $order->order_type === 'pricing_plan';

    $planName = $isPricingPlan
        ? $order->pricingPlan?->title ?? ($order->plan_name ?? 'your selected plan')
        : $order->servicePlan?->name ?? ($order->service?->card_title ?? ($order->plan_name ?? 'your selected service'));

    $billingCycle = match ($order->billing_cycle) {
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'one_time' => 'One-time',
        'custom' => 'Custom',
        default => 'Negotiable',
    };

    $amount = (float) ($order->final_price ?? $order->amount ?? $order->plan_price ?? 0);

    $currency = $order->currency === 'BDT' || blank($order->currency) ? '৳' : $order->currency . ' ';
    $formatMoney = fn($value) => $currency.number_format((float) $value, 2);

    $confirmedAt = $order->created_at;
    $siteName = $settings?->site_name ?: config('app.name');
    $contactPhone = $settings?->phone;
    $ordersUrl = route('account.services', ['tab' => $isPricingPlan ? 'plans' : 'services']);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
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
                                        <p style="margin:0; font-size:18px; font-weight:800; color:{{ $head }};">Order Confirmed</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0 0 2px; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Order No.</p>
                                        <p style="margin:0; font-size:13px; font-weight:700; color:{{ $text }};">{{ $orderNo }}</p>
                                        <p style="margin:4px 0 0; font-size:11px; color:{{ $muted }};">{{ $confirmedAt?->format('M d, Y') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:28px 34px 0;">
                            <p style="margin:0 0 14px; font-size:17px; font-weight:800; color:{{ $head }};">
                                Dear {{ $customerName }},
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                Great news! Your
                                <strong style="color:{{ $head }};">{{ $planName }}</strong>
                                order has been <strong style="color:#34d399;">confirmed and paid</strong>.
                                Your order is now active.
                            </p>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                Please find your order summary below.
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
                                        <p style="margin:6px 0 0; font-size:13px; font-weight:800; color:#34d399;">Paid</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Order details --}}
                    <tr>
                        <td style="padding:14px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Order Type</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $isPricingPlan ? 'Pricing Plan' : 'Service' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Service / Plan</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $planName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Billing Cycle</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $billingCycle }}</td>
                                </tr>
                                @if ($order->start_date)
                                    <tr>
                                        <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Start Date</td>
                                        <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $order->start_date?->format('M d, Y') }}</td>
                                    </tr>
                                @endif
                                @if ($order->end_date)
                                    <tr>
                                        <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">End Date</td>
                                        <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $order->end_date?->format('M d, Y') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td style="padding:24px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ordersUrl }}"
                                            style="display:inline-block; background:{{ $brandColor }}; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:12px;">
                                            View My Orders
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