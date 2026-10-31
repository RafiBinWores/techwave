@php
    $brandColor = $template->brand_color ?? '#0F52BA';
    $siteName = $settings->site_name ?: config('app.name');
    $logoSrc = \App\Services\UploadStorage::emailLogo($settings->logo, $message ?? null);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product subscription renewal reminder</title>
</head>
<body style="margin:0; padding:0; background:#0f172a; font-family:Arial, Helvetica, sans-serif; color:#e2e8f0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a; padding:28px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#0b1020; border:1px solid #1e293b; border-radius:20px; overflow:hidden;">
                    <tr>
                        <td style="padding:30px 34px 0;">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $siteName }}" width="120" style="max-width:140px; max-height:52px; height:auto; display:block;">
                            @else
                                <h1 style="margin:0; font-size:22px; font-weight:800; color:#ffffff;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin:24px 0 6px; font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px;">{{ $windowLabel }}</p>
                            <h2 style="margin:0; font-size:24px; color:#ffffff;">Your subscription is due for renewal</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 34px 0;">
                            <p style="margin:0 0 14px; font-size:16px; font-weight:700; color:#ffffff;">Dear {{ $purchase->customer['name'] }},</p>
                            <p style="margin:0; font-size:14px; line-height:1.8;">
                                Your <strong>{{ $purchase->product_name }}</strong> subscription on the <strong>{{ $purchase->plan_name }}</strong> plan is approaching its expiry. Renew your subscription to continue using your software without interruption.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 34px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $brandColor }}55; background:{{ $brandColor }}22; border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0; font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">Subscription expires</p>
                                        <p style="margin:8px 0 0; font-size:20px; font-weight:800; color:#ffffff;">{{ $purchase->expires_at->format('d M Y, h:i A T') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 34px 0;">
                            <p style="margin:0; font-size:14px; line-height:1.8;">Any remaining subscription time will be preserved. Your renewal is activated after SSLCommerz payment verification or admin approval of your bKash payment.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 34px 0;">
                            <a href="{{ $actionUrl }}" style="display:inline-block; background:{{ $brandColor }}; color:#ffffff; padding:13px 32px; border-radius:12px; font-size:14px; font-weight:700; text-decoration:none;">Renew subscription</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px 24px;">
                            <p style="margin:0; padding-top:20px; border-top:1px solid #1e293b; text-align:center; font-size:11px; line-height:1.8; color:#94a3b8;">
                                {{ $siteName }}
                                @if ($settings->email) &middot; {{ $settings->email }} @endif
                                @if ($settings->phone) &middot; {{ $settings->phone }} @endif
                                <br>Thank you for choosing {{ $siteName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
