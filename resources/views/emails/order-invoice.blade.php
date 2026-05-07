@php
    $brandColor = $template->brand_color ?: '#0F52BA';

    $customerName = $order->user?->name ?? 'Customer';
    $customerEmail = $order->user?->email ?? '';
    $customerPhone = $order->user?->phone ?? '';
    $customerCompany = $order->user?->company_name ?? '';

    $invoiceNo = $order->order_no ?? 'INV-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

    $plan = $order->pricingPlan;
    $planName = $plan?->title ?? 'Pricing Plan';
    $description = $plan?->description ?? 'Service subscription plan';
    $billingCycle = ucfirst($order->billing_cycle ?? 'monthly');

    $subtotal = (float) ($order->amount ?? 0);
    $discount = 0;
    $total = $subtotal;
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $template->title }}</title>
</head>

<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:30px 12px;">
        <tr>
            <td align="center">
                <table width="720" cellpadding="0" cellspacing="0"
                    style="width:720px; max-width:100%; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e2e8f0;">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:28px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55%" valign="top">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td valign="middle" style="padding-right:14px;">
                                                    <div
                                                        style="width:52px; height:52px; border-radius:10px; background:#eef2ff; border:1px solid #dbe4ff; text-align:center; line-height:52px; font-size:11px; font-weight:bold; color:#94a3b8;">
                                                        Logo
                                                    </div>
                                                </td>

                                                <td valign="middle">
                                                    <h1
                                                        style="margin:0; font-size:24px; line-height:28px; font-weight:800; letter-spacing:-0.03em; color:#0f172a;">
                                                        TechWave
                                                    </h1>
                                                    <p
                                                        style="margin:6px 0 0; font-size:10px; line-height:14px; text-transform:uppercase; letter-spacing:2px; color:#64748b;">
                                                        {{ $template->title ?: 'Service Invoice' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style="margin-top:18px; font-size:12px; line-height:19px; color:#64748b;">
                                            <div>128 Innovation Way</div>
                                            <div>Tech District, Dhaka</div>
                                            <div>contact@techwave.io</div>
                                            <div>+880 1XXX XXXXXX</div>
                                        </div>
                                    </td>

                                    <td width="45%" valign="top" align="right">
                                        <h2
                                            style="margin:0 0 18px; font-size:34px; line-height:38px; font-weight:900; color:{{ $brandColor }};">
                                            INVOICE
                                        </h2>

                                        <table cellpadding="0" cellspacing="0" align="right"
                                            style="font-size:12px; line-height:18px;">
                                            <tr>
                                                <td
                                                    style="padding:3px 16px 3px 0; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">
                                                    Invoice #</td>
                                                <td style="padding:3px 0; font-weight:700; color:#0f172a;">
                                                    {{ $invoiceNo }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="padding:3px 16px 3px 0; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">
                                                    Date Issued</td>
                                                <td style="padding:3px 0; color:#475569;">
                                                    {{ optional($order->created_at)->format('M d, Y') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Bill To --}}
                    <tr>
                        <td style="padding:0 32px 12px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border:1px solid #e2e8f0; border-radius:14px;">
                                <tr>
                                    <td style="padding:18px;">
                                        <p
                                            style="margin:0 0 10px; font-size:10px; line-height:14px; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#94a3b8;">
                                            Bill To
                                        </p>

                                        <div style="font-size:13px; line-height:22px; color:#475569;">
                                            <div><strong style="color:#0f172a;">Name:</strong> {{ $customerName }}</div>

                                            @if ($customerEmail)
                                                <div><strong style="color:#0f172a;">Email:</strong> {{ $customerEmail }}
                                                </div>
                                            @endif

                                            @if ($customerPhone)
                                                <div><strong style="color:#0f172a;">Phone:</strong> {{ $customerPhone }}
                                                </div>
                                            @endif

                                            @if ($customerCompany)
                                                <div style="color:{{ $brandColor }}; font-weight:700;">
                                                    <strong style="color:#0f172a;">Company:</strong>
                                                    {{ $customerCompany }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Items --}}
                    <tr>
                        <td style="padding:14px 32px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="border-collapse:collapse; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                                <thead>
                                    <tr style="background:{{ $brandColor }};">
                                        <th align="left"
                                            style="padding:13px 14px; font-size:11px; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">
                                            Plan</th>
                                        <th align="left"
                                            style="padding:13px 14px; font-size:11px; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">
                                            Description</th>
                                        <th align="center"
                                            style="padding:13px 14px; font-size:11px; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">
                                            Plan Type</th>
                                        <th align="right"
                                            style="padding:13px 14px; font-size:11px; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">
                                            Unit Price</th>
                                        <th align="right"
                                            style="padding:13px 14px; font-size:11px; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">
                                            Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr style="background:#ffffff;">
                                        <td
                                            style="padding:15px 14px; border-top:1px solid #e2e8f0; font-size:12px; font-weight:800; color:#0f172a;">
                                            {{ $planName }}
                                        </td>

                                        <td
                                            style="padding:15px 14px; border-top:1px solid #e2e8f0; font-size:11px; line-height:17px; color:#64748b;">
                                            {{ $description }}
                                        </td>

                                        <td align="center"
                                            style="padding:15px 14px; border-top:1px solid #e2e8f0; font-size:12px; color:#475569;">
                                            {{ $billingCycle }}
                                        </td>

                                        <td align="right"
                                            style="padding:15px 14px; border-top:1px solid #e2e8f0; font-size:12px; color:#475569;">
                                            ৳{{ number_format($subtotal, 2) }}
                                        </td>

                                        <td align="right"
                                            style="padding:15px 14px; border-top:1px solid #e2e8f0; font-size:12px; font-weight:800; color:#0f172a;">
                                            ৳{{ number_format($total, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Summary --}}
                    <tr>
                        <td style="padding:24px 32px 0;" align="right">
                            <table width="320" cellpadding="0" cellspacing="0" style="max-width:100%;">
                                <tr>
                                    <td style="padding:6px 0; font-size:14px; color:#64748b;">Subtotal</td>
                                    <td align="right" style="padding:6px 0; font-size:14px; color:#334155;">
                                        ৳{{ number_format($subtotal, 2) }}</td>
                                </tr>

                                @if ($discount > 0)
                                    <tr>
                                        <td style="padding:6px 0; font-size:14px; color:#64748b;">Discount</td>
                                        <td align="right" style="padding:6px 0; font-size:14px; color:#dc2626;">
                                            -৳{{ number_format($discount, 2) }}</td>
                                    </tr>
                                @endif

                                <tr>
                                    <td colspan="2"
                                        style="padding-top:10px; border-top:2px solid {{ $brandColor }};"></td>
                                </tr>

                                <tr>
                                    <td
                                        style="padding-top:8px; font-size:20px; font-weight:900; text-transform:uppercase; color:{{ $brandColor }};">
                                        Total Amount
                                    </td>
                                    <td align="right"
                                        style="padding-top:8px; font-size:24px; font-weight:900; color:{{ $brandColor }};">
                                        ৳{{ number_format($total, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:34px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td width="50%" valign="top" style="padding-top:24px;">
                                        <h4
                                            style="margin:0 0 8px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#94a3b8;">
                                            Terms & Conditions
                                        </h4>

                                        <p style="margin:0; font-size:12px; line-height:19px; color:#64748b;">
                                            {{ $template->terms_text ?: 'Net terms apply. Please contact support for invoice related queries.' }}
                                        </p>
                                    </td>

                                    <td width="50%" valign="bottom" align="right" style="padding-top:24px;">
                                        <p
                                            style="margin:0 0 6px; font-size:21px; line-height:26px; font-weight:800; color:{{ $brandColor }};">
                                            Thank you for your business!
                                        </p>

                                        <p style="margin:0; font-size:12px; line-height:18px; color:#94a3b8;">
                                            {{ $template->footer_text ?: 'Empowering your digital infrastructure.' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
