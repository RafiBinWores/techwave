@php
    $brandColor = $template->brand_color ?? '#0F52BA';

    $companyName = $setting?->site_name ?? config('app.name');
    $companyEmail = $setting?->email ?? config('mail.from.address');
    $companyPhone = $setting?->phone ?? '';
    $companyAddress = $setting?->location ?? '';
    $companyWebsite = config('app.url');

    $logoCid = null;

    if (!empty($logoPath)) {
        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            $logoCid = $logoPath;
        } elseif (file_exists($logoPath)) {
            $logoCid = isset($message) && method_exists($message, 'embed')
                ? $message->embed($logoPath)
                : asset('storage/'.ltrim(str_replace('storage/', '', $logoPath), '/'));
        }
    }

    $quotationNo = $booking->booking_no ?? 'QT-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
    $issuedDate = $booking->updated_at?->format('M d, Y') ?? now()->format('M d, Y');

    $customerName = $booking->full_name ?? ($booking->user?->name ?? 'Valued Customer');
    $customerEmail = $booking->email ?? ($booking->user?->email ?? '');
    $customerPhone = $booking->phone ?? ($booking->user?->phone ?? '');
    $customerCompany = $booking->company_name ?? '';

    $isPricingPlan = $booking->booking_type === 'pricing_plan';

    $planName = $isPricingPlan
        ? $booking->pricingPlan?->title ?? ($booking->plan_name ?? 'Pricing Plan')
        : $booking->servicePlan?->name ?? ($booking->plan_name ?? ($booking->service?->card_title ?? 'Service Plan'));

    $description = $isPricingPlan
        ? $booking->pricingPlan?->description ?? 'Business service plan quotation.'
        : $booking->servicePlan?->description ?? ($booking->service?->short_description ?? 'Service quotation.');

    $billingCycle = match ($booking->billing_cycle) {
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'one_time' => 'One-time',
        'custom' => 'Custom',
        default => 'Negotiable',
    };

    $planPrice = (float) ($booking->plan_price ?? 0);
    $requestedPrice = $booking->requested_price !== null ? (float) $booking->requested_price : null;
    $quotedPrice = $booking->quoted_price !== null ? (float) $booking->quoted_price : null;
    $finalPrice = $booking->final_price !== null ? (float) $booking->final_price : null;

    $subtotal = $planPrice;
    $total = $finalPrice ?? ($quotedPrice ?? ($requestedPrice ?? $planPrice));
    $discountAmount =
        $quotedPrice !== null && $planPrice > 0 && $quotedPrice < $planPrice ? $planPrice - $quotedPrice : 0;

    $currency = $booking->currency === 'BDT' || blank($booking->currency) ? '৳' : $booking->currency . ' ';
    $formatMoney = fn($amount) => $currency . number_format((float) $amount, 2);

    $statusLabel = ucfirst(str_replace('_', ' ', $booking->status ?? 'quoted'));
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotationNo }}</title>
</head>

<body style="margin:0; padding:0; background:#f4f6f8; font-family:'Segoe UI', Arial, Helvetica, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:28px 0;">
        <tr>
            <td align="center">
                <table width="720" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 12px 40px rgba(15,23,42,0.10);">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:22px 24px 18px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:58%;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td valign="middle" style="width:88px; padding-right:10px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0"
                                                        border="0"
                                                        style="width:88px; height:56px; border-collapse:collapse;">
                                                        <tr>
                                                            <td align="center" valign="middle"
                                                                style="width:88px; height:56px; padding:0; overflow:hidden;">
                                                                @if ($logoCid)
                                                                    <img src="{{ $logoCid }}"
                                                                        alt="{{ $companyName }}" width="80"
                                                                        height="48"
                                                                        style="width:80px; max-width:80px; max-height:48px; object-fit:contain; display:block; border:0; outline:none; text-decoration:none;">
                                                                @else
                                                                    <span
                                                                        style="font-size:9px; font-weight:700; color:#94a3b8;">
                                                                        Logo
                                                                    </span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>

                                                <td valign="middle">
                                                    <h1
                                                        style="margin:0; font-size:18px; line-height:22px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:-0.2px;">
                                                        {{ $companyName }}
                                                    </h1>

                                                    <p
                                                        style="margin:3px 0 0; font-size:8px; line-height:12px; text-transform:uppercase; letter-spacing:1.6px; color:#64748b;">
                                                        {{ $template->title ?? 'Service Quotation' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style="margin-top:14px; font-size:10px; line-height:16px; color:#64748b;">
                                            @if ($companyAddress)
                                                {{ $companyAddress }}<br>
                                            @endif

                                            @if ($companyEmail)
                                                {{ $companyEmail }}<br>
                                            @endif

                                            @if ($companyPhone)
                                                {{ $companyPhone }}<br>
                                            @endif

                                            @if ($companyWebsite)
                                                {{ parse_url($companyWebsite, PHP_URL_HOST) ?? $companyWebsite }}
                                            @endif
                                        </div>
                                    </td>

                                    <td valign="top" align="right" style="width:42%;">
                                        <h2
                                            style="margin:0 0 12px; font-size:26px; line-height:30px; font-weight:900; color:{{ $brandColor }}; text-transform:uppercase;">
                                            Quotation
                                        </h2>

                                        <table cellpadding="0" cellspacing="0" align="right"
                                            style="font-size:10px; border-collapse:collapse;">
                                            <tr>
                                                <td
                                                    style="padding:2px 14px 2px 0; text-align:right; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">
                                                    Quote #
                                                </td>
                                                <td
                                                    style="padding:2px 0; text-align:right; font-weight:700; color:#0f172a;">
                                                    {{ $quotationNo }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    style="padding:2px 14px 2px 0; text-align:right; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">
                                                    Date
                                                </td>
                                                <td
                                                    style="padding:2px 0; text-align:right; font-weight:700; color:#0f172a;">
                                                    {{ $issuedDate }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    style="padding:2px 14px 2px 0; text-align:right; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">
                                                    Status
                                                </td>
                                                <td
                                                    style="padding:2px 0; text-align:right; font-weight:700; color:{{ $brandColor }};">
                                                    {{ $statusLabel }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Bill To & Pay To --}}
                    <tr>
                        <td style="padding:0 24px 12px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border:1px solid #e2e8f0; border-radius:12px;">
                                <tr>
                                    <td valign="top" style="width:50%; padding:14px 16px 14px 14px;">
                                        <p
                                            style="margin:0 0 7px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#94a3b8;">
                                            Bill To
                                        </p>

                                        <div style="font-size:11px; line-height:18px; color:#475569;">
                                            <strong style="color:#0f172a;">{{ $customerName }}</strong>

                                            @if ($customerEmail)
                                                <div>{{ $customerEmail }}</div>
                                            @endif

                                            @if ($customerPhone)
                                                <div>{{ $customerPhone }}</div>
                                            @endif

                                            @if ($customerCompany)
                                                <div style="color:{{ $brandColor }}; font-weight:700;">
                                                    <strong style="color:#0f172a;">Company:</strong> {{ $customerCompany }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td valign="top"
                                        style="width:50%; padding:14px 14px 14px 16px; border-left:1px solid #e2e8f0;">
                                        <p
                                            style="margin:0 0 7px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#94a3b8;">
                                            Pay To
                                        </p>

                                        <div style="font-size:11px; line-height:18px; color:#475569;">
                                            <strong style="color:#0f172a;">{{ $companyName ?: 'Our Company' }}</strong>

                                            @if ($companyAddress)
                                                <div>{{ $companyAddress }}</div>
                                            @endif

                                            @if ($companyPhone)
                                                <div>{{ $companyPhone }}</div>
                                            @endif

                                            @if ($companyEmail)
                                                <div>{{ $companyEmail }}</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Quotation Items --}}
                    <tr>
                        <td style="padding:10px 24px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid #e2e8f0;">
                                <tr style="background:{{ $brandColor }}; color:#ffffff;">
                                    <th align="left"
                                        style="padding:10px 9px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.6px;">
                                        Service / Plan
                                    </th>
                                    <th align="right"
                                        style="padding:10px 9px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.6px;">
                                        Unit Price
                                    </th>
                                    <th align="right"
                                        style="padding:10px 9px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:0.6px;">
                                        Total
                                    </th>
                                </tr>

                                <tr>
                                    <td style="padding:12px 9px; border-top:1px solid #e2e8f0; font-size:10px; color:#475569;">
                                        <span style="font-weight:800; color:#0f172a;">{{ $planName }}</span>

                                        <div
                                            style="margin-top:3px; font-size:9px; line-height:15px; color:#64748b;">
                                            {{ $description }}
                                        </div>
                                    </td>

                                    <td align="right"
                                        style="padding:12px 9px; border-top:1px solid #e2e8f0; font-size:10px; color:#475569;">
                                        {{ $planPrice > 0 ? $formatMoney($planPrice) : 'Negotiable' }}
                                    </td>

                                    <td align="right"
                                        style="padding:12px 9px; border-top:1px solid #e2e8f0; font-size:10px; color:#0f172a; font-weight:800;">
                                        {{ $total > 0 ? $formatMoney($total) : 'Negotiable' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Summary --}}
                    <tr>
                        <td style="padding:22px 24px 0; text-align:right;">
                            <table width="280" align="right" cellpadding="0" cellspacing="0"
                                style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:5px 0; font-size:11px; color:#64748b; text-align:left;">
                                        Subtotal
                                    </td>
                                    <td align="right" style="padding:5px 0; font-size:11px; color:#334155;">
                                        {{ $planPrice > 0 ? $formatMoney($subtotal) : 'Negotiable' }}
                                    </td>
                                </tr>

                                @if ($requestedPrice !== null)
                                    <tr>
                                        <td style="padding:5px 0; font-size:11px; color:#64748b; text-align:left;">
                                            Requested Price
                                        </td>
                                        <td align="right" style="padding:5px 0; font-size:11px; color:#d97706;">
                                            {{ $formatMoney($requestedPrice) }}
                                        </td>
                                    </tr>
                                @endif

                                @if ($discountAmount > 0)
                                    <tr>
                                        <td style="padding:5px 0; font-size:11px; color:#64748b; text-align:left;">
                                            Discount
                                        </td>
                                        <td align="right" style="padding:5px 0; font-size:11px; color:#dc2626;">
                                            -{{ $formatMoney($discountAmount) }}
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td colspan="2" style="padding-top:8px;">
                                        <div style="border-top:2px solid {{ $brandColor }};"></div>
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="padding-top:7px; font-size:14px; font-weight:900; text-transform:uppercase; color:{{ $brandColor }}; text-align:left;">
                                        Total Amount
                                    </td>
                                    <td
                                        style="padding-top:7px; text-align:right; font-size:16px; font-weight:900; color:{{ $brandColor }};">
                                        {{ $total > 0 ? $formatMoney($total) : 'Negotiable' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:30px 24px 22px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td valign="top" style="padding-top:20px; width:50%; padding-right:18px;">
                                        <p
                                            style="margin:0 0 7px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#94a3b8;">
                                            Terms & Conditions
                                        </p>

                                        <div style="font-size:10px; line-height:16px; color:#64748b;">
                                            {{ $template->terms_text ?: 'Please review this quotation. If everything looks good, accept it from your account to confirm.' }}
                                        </div>

                                        @if ($booking->admin_note)
                                            <p
                                                style="margin:14px 0 7px; font-size:9px; line-height:13px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#94a3b8;">
                                                Quote Note
                                            </p>

                                            <div style="font-size:10px; line-height:16px; color:#475569;">
                                                {{ $booking->admin_note }}
                                            </div>
                                        @endif
                                    </td>

                                    <td valign="bottom" align="right" style="padding-top:20px; width:50%; padding-left:18px;">
                                        <p
                                            style="margin:0 0 5px; font-size:15px; line-height:20px; font-weight:800; color:{{ $brandColor }};">
                                            Thank you for your business!
                                        </p>

                                        <div style="font-size:10px; line-height:16px; color:#94a3b8;">
                                            {{ $template->footer_text ?: 'Empowering your digital infrastructure.' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                @if (Route::has('account.services'))
                    <p style="margin:24px 0 0; text-align:center;">
                        <a href="{{ route('account.services', ['tab' => $booking->booking_type === 'pricing_plan' ? 'plans' : 'services']) }}"
                            style="display:inline-block; background:{{ $brandColor }}; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:10px; font-size:12px; font-weight:700;">
                            Review &amp; Respond in My Account
                        </a>
                    </p>
                @endif

                <p style="margin:16px 0 0; font-size:11px; color:#94a3b8;">
                    This quotation was generated from {{ $companyName }}. You can accept, negotiate the price, or
                    decline it from your account.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>