@php
    use Illuminate\Support\Facades\Storage;

    $brandColor = $template->brand_color ?? '#0F52BA';
    $logoUrl = $settings?->logo ? asset(Storage::url($settings->logo)) : null;

    $subtotal = $proposal->subtotal();
    $discountAmount = $proposal->discountAmount();
    $grandTotal = $proposal->total();

    $currency = '৳';

    $formatMoney = fn ($amount) => $currency . number_format((float) $amount, 2);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $template->title ?? 'Invoice' }}</title>
</head>

<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:32px 0;">
        <tr>
            <td align="center">
                <table width="760" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e5e7eb; box-shadow:0 10px 35px rgba(15,23,42,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:30px 30px 26px; border-bottom:1px solid #e5e7eb;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:55%;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td valign="middle" style="padding-right:14px;">
                                                    <div style="width:52px; height:52px; border-radius:10px; background:#eef2ff; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid #dbe4ff;">
                                                        @if ($logoUrl)
                                                            <img
                                                                src="{{ $logoUrl }}"
                                                                alt="{{ $settings?->site_name }}"
                                                                width="42"
                                                                style="max-width:42px; max-height:42px; display:block;"
                                                            >
                                                        @else
                                                            <div style="width:42px; height:42px; border-radius:8px; background:{{ $brandColor }};"></div>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td valign="middle">
                                                    <h1 style="margin:0; font-size:28px; line-height:1; color:#111827; text-transform:uppercase; letter-spacing:-0.02em;">
                                                        {{ $settings?->site_name ?? config('app.name') }}
                                                    </h1>

                                                    <p style="margin:8px 0 0; font-size:11px; letter-spacing:.18em; text-transform:uppercase; color:#64748b;">
                                                        {{ $template->title ?? 'Invoice' }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style="margin-top:18px; font-size:13px; line-height:1.7; color:#475569;">
                                            @if ($settings?->location)
                                                <div>{{ $settings->location }}</div>
                                            @endif

                                            @if ($settings?->email)
                                                <div>{{ $settings->email }}</div>
                                            @endif

                                            @if ($settings?->phone)
                                                <div>{{ $settings->phone }}</div>
                                            @endif
                                        </div>
                                    </td>

                                    <td valign="top" align="right" style="width:45%;">
                                        <h2 style="margin:0 0 16px; font-size:32px; line-height:1; color:{{ $brandColor }}; font-weight:800;">
                                            PROPOSAL
                                        </h2>

                                        <table cellpadding="0" cellspacing="0" style="margin-left:auto; font-size:13px; color:#475569;">
                                            <tr>
                                                <td style="padding:3px 16px 3px 0; text-transform:uppercase; color:#94a3b8; font-size:11px; letter-spacing:.08em;">
                                                    Proposal #
                                                </td>
                                                <td style="padding:3px 0; font-weight:700; color:#111827; text-align:right;">
                                                    {{ $proposal->proposal_no }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:3px 16px 3px 0; text-transform:uppercase; color:#94a3b8; font-size:11px; letter-spacing:.08em;">
                                                    Date Issued
                                                </td>
                                                <td style="padding:3px 0; text-align:right;">
                                                    {{ $proposal->created_at?->format('M d, Y') }}
                                                </td>
                                            </tr>

                                            @if ($proposal->valid_until)
                                                <tr>
                                                    <td style="padding:3px 16px 3px 0; text-transform:uppercase; color:#94a3b8; font-size:11px; letter-spacing:.08em;">
                                                        Due Date
                                                    </td>
                                                    <td style="padding:3px 0; text-align:right; font-weight:700; color:{{ $brandColor }};">
                                                        {{ $proposal->valid_until->format('M d, Y') }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Customer Info --}}
                    <tr>
                        <td style="padding:24px 30px 6px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:50%; padding-right:10px;">
                                        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#ffffff;">
                                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:.08em;">
                                                Customer Details
                                            </p>

                                            <div style="font-size:14px; line-height:1.8; color:#334155;">
                                                <strong style="font-size:15px; color:#111827;">{{ $proposal->customer_name }}</strong><br>

                                                @if ($proposal->customer_email)
                                                    {{ $proposal->customer_email }}<br>
                                                @endif

                                                @if ($proposal->customer_phone)
                                                    {{ $proposal->customer_phone }}<br>
                                                @endif

                                                @if ($proposal->company_name)
                                                    <span style="display:inline-block; margin-top:6px; font-weight:700; color:{{ $brandColor }};">
                                                        {{ $proposal->company_name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td valign="top" style="width:50%; padding-left:10px;">
                                        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#ffffff;">
                                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:.08em;">
                                                Proposal Details
                                            </p>

                                            <div style="font-size:14px; line-height:1.8; color:#334155;">
                                                <strong>Subject:</strong> {{ $proposal->subject }}<br>
                                                <strong>Status:</strong> {{ ucfirst($proposal->status) }}<br>

                                                @if ($proposal->note)
                                                    <div style="margin-top:10px; padding:10px 12px; background:#f8fafc; border-left:3px solid {{ $brandColor }}; border-radius:8px; font-size:12px; line-height:1.6; color:#475569;">
                                                        <strong>Note:</strong><br>
                                                        {{ $proposal->note }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Table --}}
                    <tr>
                        <td style="padding:20px 30px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background:{{ $brandColor }}; color:#ffffff;">
                                        <th align="left" style="padding:13px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.08em; border-top-left-radius:8px;">
                                            Service
                                        </th>
                                        <th align="left" style="padding:13px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.08em;">
                                            Description
                                        </th>
                                        <th align="center" style="padding:13px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.08em;">
                                            Qty
                                        </th>
                                        <th align="right" style="padding:13px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.08em;">
                                            Unit Price
                                        </th>
                                        <th align="right" style="padding:13px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.08em; border-top-right-radius:8px;">
                                            Total
                                        </th>
                                    </tr>
                                </thead>

                                <tbody style="border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb;">
                                    @foreach ($proposal->items as $index => $item)
                                        @php
                                            $lineTotal = (float) $item->quantity * (float) $item->unit_price;
                                            $rowBg = $index % 2 === 1 ? '#f8fafc' : '#ffffff';
                                        @endphp

                                        <tr style="background:{{ $rowBg }};">
                                            <td valign="top" style="padding:15px 14px; border-top:1px solid #e5e7eb;">
                                                <strong style="font-size:14px; color:#111827;">
                                                    {{ $item->title }}
                                                </strong>
                                            </td>

                                            <td valign="top" style="padding:15px 14px; border-top:1px solid #e5e7eb; font-size:12px; line-height:1.6; color:#64748b; max-width:230px;">
                                                {{ $item->description ?: '—' }}
                                            </td>

                                            <td align="center" valign="top" style="padding:15px 14px; border-top:1px solid #e5e7eb; font-size:13px; color:#334155;">
                                                {{ number_format((float) $item->quantity, 1) }}
                                            </td>

                                            <td align="right" valign="top" style="padding:15px 14px; border-top:1px solid #e5e7eb; font-size:13px; color:#334155;">
                                                {{ $formatMoney($item->unit_price) }}
                                            </td>

                                            <td align="right" valign="top" style="padding:15px 14px; border-top:1px solid #e5e7eb; font-size:13px; font-weight:700; color:#111827;">
                                                {{ $formatMoney($lineTotal) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Totals --}}
                    <tr>
                        <td style="padding:24px 30px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:55%;"></td>

                                    <td valign="top" style="width:45%;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0; font-size:14px; color:#64748b;">Subtotal</td>
                                                <td align="right" style="padding:6px 0; font-size:14px; color:#111827;">
                                                    {{ $formatMoney($subtotal) }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:6px 0; font-size:14px; color:#64748b;">
                                                    Discount
                                                    @if ($proposal->discount_type === 'percentage')
                                                        ({{ number_format((float) $proposal->discount_value, 2) }}%)
                                                    @endif
                                                </td>
                                                <td align="right" style="padding:6px 0; font-size:14px; color:#dc2626;">
                                                    -{{ $formatMoney($discountAmount) }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td colspan="2" style="padding-top:8px;">
                                                    <div style="border-top:2px solid {{ $brandColor }};"></div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:14px 0 0; font-size:18px; font-weight:600; color:{{ $brandColor }}; text-transform:uppercase;">
                                                    Total Amount
                                                </td>
                                                <td align="right" style="padding:14px 0 0; font-size:22px; font-weight:600; color:{{ $brandColor }};">
                                                    {{ $formatMoney($grandTotal) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:34px 30px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e5e7eb; padding-top:22px;">
                                <tr>
                                    <td valign="top" style="width:55%; padding-right:18px;">
                                        <p style="margin:0 0 8px; font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:.08em;">
                                            Terms & Conditions
                                        </p>

                                        <p style="margin:0; font-size:12px; line-height:1.7; color:#64748b;">
                                            {{ $template->terms_text ?: 'Please contact us if you have any questions regarding this invoice or proposal.' }}
                                        </p>
                                    </td>

                                    <td valign="bottom" align="right" style="width:45%; padding-left:18px;">
                                        <p style="margin:0 0 4px; font-size:22px; font-weight:700; color:{{ $brandColor }};">
                                            Thank you for your business!
                                        </p>

                                        <p style="margin:0; font-size:12px; color:#94a3b8;">
                                            {{ $template->footer_text ?: 'Empowering your digital infrastructure.' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0; font-size:11px; color:#94a3b8;">
                    Thank you for considering our proposal. We look forward to the opportunity to work with you and help your business thrive.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>