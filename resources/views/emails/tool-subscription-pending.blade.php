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

    $currency = '৳';
    $amount = (float) $subscription->amount;
    $submittedAt = $subscription->created_at ?? $subscription->starts_at;
    $siteName = $settings?->site_name ?: config('app.name');
    $contactPhone = $settings?->phone;
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Received</title>
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
                                        <p style="margin:0; font-size:18px; font-weight:800; color:{{ $head }};">Subscription Request</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0 0 2px; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Date</p>
                                        <p style="margin:0; font-size:13px; font-weight:700; color:{{ $text }};">{{ $submittedAt?->format('M d, Y') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting + status --}}
                    <tr>
                        <td style="padding:28px 34px 0;">
                            <p style="margin:0 0 14px; font-size:17px; font-weight:800; color:{{ $head }};">
                                Dear {{ $subscription->user?->name }},
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                Thank you for subscribing to <strong style="color:{{ $head }};">{{ $subscription->toolCategory?->name }}</strong>.
                                We have received your payment request. Our team will verify your payment and confirm
                                your purchase <strong style="color:{{ $head }};">as soon as possible</strong>.
                            </p>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.75; color:{{ $text }};">
                                Please find your payment summary below.
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
                                        <p style="margin:0; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Amount Payable</p>
                                        <p style="margin:6px 0 0; font-size:26px; font-weight:800; color:{{ $brandColor }};">{{ $currency }}{{ number_format($amount, 2) }}</p>
                                    </td>
                                    <td align="right" style="padding:16px 20px;">
                                        <p style="margin:0; font-size:11px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:1.5px;">Status</p>
                                        <p style="margin:6px 0 0; font-size:13px; font-weight:800; color:#fbbf24;">Pending Verification</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Booking / payment details --}}
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
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">bKash Number</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->sender_bkash ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; font-size:14px; font-weight:600; color:{{ $head }};">Transaction ID</td>
                                    <td align="right" style="padding:12px 0; font-size:14px; color:{{ $text }};">{{ $subscription->transaction_id ?: '—' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Verification notice --}}
                    <tr>
                        <td style="padding:22px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#1c1917; border:1px solid #57534e; border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0; font-size:14px; line-height:1.75; color:#fafaf9;">
                                            If your subscription is taking longer than expected to activate, or if you
                                            have any questions,
                                            @if ($contactPhone)
                                                please call us at
                                                <strong style="white-space:nowrap; color:#ffffff;">{{ $contactPhone }}</strong>
                                            @else
                                                please contact our support team
                                            @endif
                                            and we will be happy to assist you.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

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
                                This is a confirmation that we received your subscription payment request.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>