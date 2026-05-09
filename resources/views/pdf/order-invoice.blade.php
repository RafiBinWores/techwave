@php
    $brandColor = $template->brand_color ?: '#0F52BA';

    $plan = $order->pricingPlan;

    $invoiceNo = $order->order_no ?? 'N/A';
    $issuedDate = $order->created_at?->format('M d, Y') ?? now()->format('M d, Y');

    $customerName = $order->user?->name ?? 'Customer';
    $customerEmail = $order->user?->email ?? 'N/A';
    $customerPhone = $order->user?->phone ?? 'N/A';
    $customerCompany = $order->user?->company_name ?? 'N/A';

    $planName = $plan?->title ?? 'Pricing Plan';
    $description = $plan?->description ?? 'N/A';
    $billingCycle = ucfirst($order->billing_cycle ?? 'N/A');

    $subtotal = (float) ($order->amount ?? 0);
    $discount = 0;
    $total = $subtotal - $discount;

    $companyName = $setting->site_name ?? 'TechWave';
    $companyEmail = $setting->email ?? 'contact@techwave.io';
    $companyPhone = $setting->phone ?? '+880 1XXX XXXXXX';
    $companyAddress = $setting->location ?? 'N/A';
    $companyWebsite = $setting->website ?? ($setting->url ?? config('app.url'));

    /*
    |--------------------------------------------------------------------------
    | Logo Base64 for DomPDF
    |--------------------------------------------------------------------------
    */

    $logoSrc = null;
    $logoValue = $setting->logo ?? null;

    if (!empty($logoValue)) {
        $cleanLogo = ltrim($logoValue, '/');

        if (str_starts_with($cleanLogo, 'storage/')) {
            $possibleLogoPath = public_path($cleanLogo);
        } else {
            $possibleLogoPath = public_path('storage/' . $cleanLogo);
        }

        if (file_exists($possibleLogoPath)) {
            $mimeType = mime_content_type($possibleLogoPath);
            $logoSrc = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($possibleLogoPath));
        }
    }
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $invoiceNo }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style>
        @page {
            margin: 24px;
        }

        body {
            margin: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Inter', DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .taka {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        }

        .invoice-wrapper {
            overflow: hidden;
            background: #ffffff;
        }

        .section {
            padding: 22px 24px 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 0;
            vertical-align: top;
            padding: 0;
        }

        .brand-table {
            border-collapse: collapse;
        }

        .brand-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .logo-box {
            width: 88px;
            height: 56px;
            /* border-radius: 10px; */
            /* background: #eff6ff; */
            border: 0;
            overflow: hidden;
        }

        .logo-center-table {
            width: 88px;
            height: 56px;
            border-collapse: collapse;
        }

        .logo-center-table td {
            width: 88px;
            height: 56px;
            border: 0;
            padding: 0;
            text-align: center;
            vertical-align: middle;
        }

        .logo-img {
            width: 80px;
            max-width: 80px;
            max-height: 48px;
            vertical-align: middle;
        }

        .logo-placeholder {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
        }

        .brand-name {
            margin: 0;
            font-size: 18px;
            line-height: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.2px;
            color: #0f172a;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 8px;
            line-height: 12px;
            text-transform: uppercase;
            letter-spacing: 1.6px;
            color: #64748b;
        }

        .company-info {
            margin-top: 14px;
            font-size: 10px;
            line-height: 16px;
            color: #64748b;
        }

        .invoice-heading {
            margin: 0 0 12px;
            font-size: 26px;
            line-height: 30px;
            font-weight: 900;
            color: {{ $brandColor }};
            text-align: right;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .meta-table td {
            border: 0;
            padding: 2px 0;
            text-align: right;
        }

        .meta-label {
            padding-right: 14px !important;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .meta-value {
            font-weight: 700;
            color: #0f172a;
        }

        .bill-box {
            margin: 0 24px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .label {
            margin: 0 0 7px;
            font-size: 9px;
            line-height: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        .bill-text {
            font-size: 11px;
            line-height: 18px;
            color: #475569;
        }

        .bill-text strong {
            color: #0f172a;
        }

        .items-wrap {
            padding: 10px 24px 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        .items th {
            background: {{ $brandColor }};
            color: #ffffff;
            font-size: 9px;
            line-height: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 10px 9px;
            text-align: left;
        }

        .items th.center,
        .items td.center {
            text-align: center;
        }

        .items th.right,
        .items td.right {
            text-align: right;
        }

        .items td {
            border-top: 1px solid #e2e8f0;
            padding: 12px 9px;
            vertical-align: top;
            font-size: 10px;
            color: #475569;
        }

        .items .plan-name {
            font-weight: 800;
            color: #0f172a;
        }

        .items .description {
            font-size: 9px;
            line-height: 15px;
            color: #64748b;
        }

        .summary-wrap {
            padding: 22px 24px 0;
            text-align: right;
        }

        .summary-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 0;
            padding: 5px 0;
            font-size: 11px;
            color: #64748b;
        }

        .summary-table .amount {
            text-align: right;
            color: #334155;
        }

        .summary-table .discount {
            color: #dc2626;
        }

        .summary-divider td {
            padding-top: 8px;
            border-top: 2px solid {{ $brandColor }};
        }

        .total-label {
            padding-top: 7px !important;
            font-size: 14px !important;
            font-weight: 900;
            text-transform: uppercase;
            color: {{ $brandColor }} !important;
        }

        .total-value {
            padding-top: 7px !important;
            text-align: right;
            font-size: 16px !important;
            font-weight: 900;
            color: {{ $brandColor }} !important;
        }

        .footer {
            padding: 30px 24px 22px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #e2e8f0;
        }

        .footer-table td {
            border: 0;
            width: 50%;
            vertical-align: top;
            padding-top: 20px;
        }

        .terms {
            font-size: 10px;
            line-height: 16px;
            color: #64748b;
        }

        .thanks {
            margin: 0 0 5px;
            font-size: 15px;
            line-height: 20px;
            font-weight: 800;
            color: {{ $brandColor }};
            text-align: right;
        }

        .footer-text {
            font-size: 10px;
            line-height: 16px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="invoice-wrapper">
        {{-- Header --}}
        <div class="section">
            <table class="header-table">
                <tr>
                    <td style="width: 58%;">
                        <table class="brand-table">
                            <tr>
                                <td style="width: 100px; padding-right: 8px;">
                                    <div class="logo-box">
                                        <table class="logo-center-table">
                                            <tr>
                                                <td>
                                                    @if ($logoSrc)
                                                        <img src="{{ $logoSrc }}" alt="{{ $companyName }}"
                                                            class="logo-img">
                                                    @else
                                                        <span class="logo-placeholder">Logo</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>

                                <td>
                                    <h1 class="brand-name">{{ $companyName }}</h1>
                                    <div class="brand-subtitle">
                                        {{ $template->title ?: 'Service Invoice' }}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="company-info">
                            {{ $companyAddress }}<br>
                            {{ $companyEmail }}<br>
                            {{ $companyPhone }}<br>
                            {{ $companyWebsite }}
                        </div>
                    </td>

                    <td style="width: 42%;">
                        <h2 class="invoice-heading">INVOICE</h2>

                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Invoice #</td>
                                <td class="meta-value">{{ $invoiceNo }}</td>
                            </tr>

                            <tr>
                                <td class="meta-label">Date Issued</td>
                                <td class="meta-value">{{ $issuedDate }}</td>
                            </tr>

                            <tr>
                                <td class="meta-label">Status</td>
                                <td class="meta-value">{{ ucfirst($order->payment_status) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Bill To --}}
        <div class="bill-box">
            <p class="label">Bill To</p>

            <div class="bill-text">
                <div><strong>Name:</strong> {{ $customerName }}</div>
                <div><strong>Email:</strong> {{ $customerEmail }}</div>
                <div><strong>Phone:</strong> {{ $customerPhone }}</div>
                <div style="color: {{ $brandColor }}; font-weight: 700;">
                    <strong>Company :</strong> {{ $customerCompany }}
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Description</th>
                        <th class="center">Plan Type</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            <span class="plan-name">{{ $planName }}</span>
                        </td>

                        <td>
                            <span class="description">{{ $description }}</span>
                        </td>

                        <td class="center">
                            {{ $billingCycle }}
                        </td>

                        <td class="right">
                            <span class="taka">BDT </span>{{ number_format($subtotal, 2) }}
                        </td>

                        <td class="right">
                            <strong style="color: #0f172a;">
                                <span class="taka">BDT </span>{{ number_format($total, 2) }}
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="amount">
                        <span class="taka">BDT </span>{{ number_format($subtotal, 2) }}
                    </td>
                </tr>

                <tr>
                    <td>Discount</td>
                    <td class="amount discount">
                        -<span class="taka">BDT </span>{{ number_format($discount, 2) }}
                    </td>
                </tr>

                <tr class="summary-divider">
                    <td colspan="2"></td>
                </tr>

                <tr>
                    <td class="total-label">Total Amount</td>
                    <td class="total-value">
                        <span class="taka">BDT </span>{{ number_format($total, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        <p class="label">Terms & Conditions</p>

                        <div class="terms">
                            {{ $template->terms_text ?: 'Net terms apply. Please contact support for invoice related queries.' }}
                        </div>
                    </td>

                    <td>
                        <p class="thanks">Thank you for your business!</p>

                        <div class="footer-text">
                            {{ $template->footer_text ?: 'Empowering your digital infrastructure.' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
