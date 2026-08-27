@php
    /*
    |--------------------------------------------------------------------------
    | Invoice Information
    |--------------------------------------------------------------------------
    */

    $invoiceId =
        $invoice['invoiceid']
        ?? $invoice['id']
        ?? 'N/A';

    $date =
        $invoice['date']
        ?? '';

    $dueDate =
        $invoice['duedate']
        ?? '';

    $datePaid =
        $invoice['datepaid']
        ?? '';

    if (
        $datePaid === '0000-00-00 00:00:00'
        || $datePaid === '0000-00-00'
    ) {
        $datePaid = '';
    }

    $status = ucfirst(
        (string) (
            $invoice['status']
            ?? 'Unknown'
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Payment Method
    |--------------------------------------------------------------------------
    */

    $paymentMethod = trim(
        (string) (
            $invoice['paymentmethod']
            ?? ''
        )
    );

    $paymentMethodName = trim(
        (string) (
            $invoice['paymentmethodname']
            ?? ''
        )
    );

    $displayPaymentMethod =
        $paymentMethodName;

    if (
        $displayPaymentMethod === ''
        && $paymentMethod !== ''
    ) {
        $displayPaymentMethod = ucwords(
            str_replace(
                ['_', '-'],
                ' ',
                $paymentMethod
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Client Information
    |--------------------------------------------------------------------------
    */

    $firstName =
        $invoice['clientfirstname']
        ?? $invoice['firstname']
        ?? '';

    $lastName =
        $invoice['clientlastname']
        ?? $invoice['lastname']
        ?? '';

    $clientName = trim(
        (string) (
            $invoice['clientname']
            ?? ''
        )
    );

    if ($clientName === '') {
        $clientName = trim(
            $firstName
            .' '.
            $lastName
        );
    }

    $clientCompany =
        $invoice['clientcompanyname']
        ?? $invoice['companyname']
        ?? '';

    $clientEmail =
        $invoice['clientemail']
        ?? $invoice['email']
        ?? '';

    $clientPhone =
        $invoice['clientphonenumber']
        ?? $invoice['phonenumber']
        ?? '';

    $clientAddress1 =
        $invoice['clientaddress1']
        ?? $invoice['address1']
        ?? '';

    $clientAddress2 =
        $invoice['clientaddress2']
        ?? $invoice['address2']
        ?? '';

    $clientCity =
        $invoice['clientcity']
        ?? $invoice['city']
        ?? '';

    $clientState =
        $invoice['clientstate']
        ?? $invoice['state']
        ?? '';

    $clientPostcode =
        $invoice['clientpostcode']
        ?? $invoice['postcode']
        ?? '';

    $clientCountry =
        $invoice['clientcountry']
        ?? $invoice['countryname']
        ?? $invoice['country']
        ?? '';

    /*
    |--------------------------------------------------------------------------
    | Client Address
    |--------------------------------------------------------------------------
    */

    $cityLine = implode(
        ', ',
        array_filter([
            trim((string) $clientCity),
            trim((string) $clientState),
            trim((string) $clientPostcode),
        ])
    );

    $clientAddressParts = array_values(
        array_filter(
            [
                trim(
                    (string) $clientAddress1
                ),

                trim(
                    (string) $clientAddress2
                ),

                trim(
                    (string) $cityLine
                ),

                trim(
                    (string) $clientCountry
                ),
            ],
            fn ($value) =>
                $value !== ''
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Escape Customer Data
    |--------------------------------------------------------------------------
    */

    $clientAddressHtml = implode(
        '<br>',
        array_map(
            fn ($part) => e($part),
            $clientAddressParts
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Amounts
    |--------------------------------------------------------------------------
    */

    $subtotal =
        $invoice['subtotal']
        ?? 0;

    $discount =
        $invoice['discount']
        ?? 0;

    $tax =
        $invoice['tax']
        ?? 0;

    $tax2 =
        $invoice['tax2']
        ?? 0;

    $credit =
        $invoice['credit']
        ?? 0;

    $total =
        $invoice['total']
        ?? 0;

    $balance =
        $invoice['balance']
        ?? 0;

    /*
    |--------------------------------------------------------------------------
    | Invoice Items
    |--------------------------------------------------------------------------
    */

    $items = data_get(
        $invoice,
        'items.item',
        []
    );

    if (! is_array($items)) {
        $items = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Single Invoice Item
    |--------------------------------------------------------------------------
    */

    if (
        isset($items['description'])
        || isset($items['amount'])
    ) {
        $items = [$items];
    }

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    $currencyCode =
        $invoice['currencycode']
        ?? $invoice['currency_code']
        ?? config(
            'app.currency_code',
            'BDT'
        );

    if (is_array($currencyCode)) {
        $currencyCode =
            $currencyCode['code']
            ?? config(
                'app.currency_code',
                'BDT'
            );
    }

    if (
        is_numeric($currencyCode)
        || trim(
            (string) $currencyCode
        ) === ''
    ) {
        $currencyCode = config(
            'app.currency_code',
            'BDT'
        );
    }

    $currencyCode = strtoupper(
        trim(
            (string) $currencyCode
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Amount Formatter
    |--------------------------------------------------------------------------
    */

    $formatAmount = function (
        $amount
    ) use (
        $currencyCode
    ) {
        return number_format(
            (float) $amount,
            2
        )
        .' '.
        $currencyCode;
    };

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    $brandColor =
        '#0F52BA';

    /*
    |--------------------------------------------------------------------------
    | WHMCS Pay To
    |--------------------------------------------------------------------------
    */

    $payToName = trim(
        (string) (
            $payTo['name']
            ?? ''
        )
    );

    $payToAddress = trim(
        (string) (
            $payTo['address']
            ?? ''
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Company Header Information
    |--------------------------------------------------------------------------
    */

    $companyName =
        $payToName !== ''
            ? $payToName
            : data_get(
                $setting,
                'site_name',
                'TechWave'
            );

    $companyAddress =
        $payToAddress !== ''
            ? $payToAddress
            : data_get(
                $setting,
                'location',
                ''
            );

    $companyEmail = data_get(
        $setting,
        'email',
        'info@techwave.asia'
    );

    $companyPhone = data_get(
        $setting,
        'phone',
        ''
    );

    $companyWebsite = data_get(
        $setting,
        'website',
        config('app.url')
    );

$companyWebsiteDisplay = preg_replace(
    '#^https?://#i',
    '',
    trim((string) $companyWebsite)
);

$companyWebsiteDisplay = rtrim(
    $companyWebsiteDisplay,
    '/'
);

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    $logoSrc = null;

    $logoValue = data_get(
        $setting,
        'logo'
    );

    if (! empty($logoValue)) {
        $cleanLogo = ltrim(
            (string) $logoValue,
            '/'
        );

        if (
            str_starts_with(
                $cleanLogo,
                'storage/'
            )
        ) {
            $possibleLogoPath =
                public_path(
                    $cleanLogo
                );
        } else {
            $possibleLogoPath =
                public_path(
                    'storage/'.
                    $cleanLogo
                );
        }

        if (
            ! file_exists(
                $possibleLogoPath
            )
            || ! is_file(
                $possibleLogoPath
            )
        ) {
            $possibleLogoPath =
                storage_path(
                    'app/public/'.
                    str_replace(
                        'storage/',
                        '',
                        $cleanLogo
                    )
                );
        }

        if (
            file_exists(
                $possibleLogoPath
            )
            && is_file(
                $possibleLogoPath
            )
        ) {
            $mimeType =
                mime_content_type(
                    $possibleLogoPath
                );

            if ($mimeType) {
                $contents =
                    file_get_contents(
                        $possibleLogoPath
                    );

                if ($contents !== false) {
                    $logoSrc =
                        'data:'.
                        $mimeType.
                        ';base64,'.
                        base64_encode(
                            $contents
                        );
                }
            }
        }
    }
@endphp

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Invoice #{{ $invoiceId }}
    </title>

    <style>

        @page {
            margin: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .invoice-wrapper {
            width: 100%;
            background: #ffffff;
        }

        .section {
            padding: 22px 24px 18px;
        }

        .currency {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }

        .brand-table {
            border-collapse: collapse;
        }

        .brand-table td {
            padding: 0;
            border: 0;
            vertical-align: middle;
        }

        .logo-box {
            width: 88px;
            height: 56px;
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
            padding: 0;
            border: 0;
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
            color: #94a3b8;
            font-size: 9px;
            font-weight: 700;
        }

        .brand-name {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            line-height: 22px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 8px;
            line-height: 12px;
            text-transform: uppercase;
            letter-spacing: 1.6px;
        }

        .company-info {
            margin-top: 14px;
            color: #64748b;
            font-size: 10px;
            line-height: 16px;
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Meta
        |--------------------------------------------------------------------------
        */

        .invoice-heading {
            margin: 0 0 12px;
            color: {{ $brandColor }};
            font-size: 26px;
            line-height: 30px;
            font-weight: 900;
            text-align: right;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .meta-table td {
            padding: 2px 0;
            border: 0;
            text-align: right;
        }

        .meta-label {
            padding-right: 14px !important;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .meta-value {
            color: #0f172a;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Billing Box
        |--------------------------------------------------------------------------
        */

        .bill-box {
            margin: 0 24px 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .bill-row {
            width: 100%;
            border-collapse: collapse;
        }

        .bill-row td {
            padding: 0 8px;
            border: 0;
            vertical-align: top;
        }

        .bill-to {
            width: 50%;
            padding-right: 22px !important;
        }

        .pay-to {
            width: 50%;
            padding-left: 22px !important;
            text-align: right;
        }

        .label {
            margin: 0 0 7px;
            color: #94a3b8;
            font-size: 9px;
            line-height: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bill-text {
            color: #475569;
            font-size: 11px;
            line-height: 18px;
        }

        .bill-text strong {
            color: #0f172a;
        }

        .company-client-name {
            margin-bottom: 3px;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .client-person {
            margin-bottom: 3px;
            color: #334155;
            font-weight: 600;
        }

        .address-block {
            margin-top: 4px;
        }

        .payment-method {
            margin-top: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        .items-wrap {
            padding: 10px 24px 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        .items th {
            padding: 10px 9px;
            background: {{ $brandColor }};
            color: #ffffff;
            font-size: 9px;
            line-height: 13px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .items td {
            padding: 12px 9px;
            border-top: 1px solid #e2e8f0;
            color: #475569;
            font-size: 10px;
            vertical-align: top;
        }

        .items .center {
            text-align: center;
        }

        .items .right {
            text-align: right;
        }

        .item-desc {
            color: #0f172a;
            font-weight: 800;
        }

        .item-type {
            color: #64748b;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

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
            padding: 5px 0;
            border: 0;
            color: #64748b;
            font-size: 11px;
        }

        .summary-table .amount {
            color: #334155;
            text-align: right;
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
            color: {{ $brandColor }} !important;
            font-size: 14px !important;
            font-weight: 900;
            text-transform: uppercase;
        }

        .total-value {
            padding-top: 7px !important;
            color: {{ $brandColor }} !important;
            font-size: 16px !important;
            font-weight: 900;
            text-align: right;
        }

        .balance-label {
            color: #dc2626 !important;
            font-size: 12px !important;
        }

        .balance-value {
            color: #dc2626 !important;
            font-size: 14px !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */

        .footer {
            padding: 30px 24px 22px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #e2e8f0;
        }

        .footer-table td {
            width: 50%;
            padding-top: 20px;
            border: 0;
            vertical-align: top;
        }

        .terms {
            color: #64748b;
            font-size: 10px;
            line-height: 16px;
        }

        .thanks {
            margin: 0 0 5px;
            color: {{ $brandColor }};
            font-size: 15px;
            line-height: 20px;
            font-weight: 800;
            text-align: right;
        }

        .footer-text {
            color: #94a3b8;
            font-size: 10px;
            line-height: 16px;
            text-align: right;
        }

    </style>

</head>

<body>

<div class="invoice-wrapper">

    {{-- HEADER --}}

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

                                                    <img
                                                        src="{{ $logoSrc }}"
                                                        alt="{{ $companyName }}"
                                                        class="logo-img"
                                                    >

                                                @else

                                                    <span class="logo-placeholder">
                                                        Logo
                                                    </span>

                                                @endif

                                            </td>

                                        </tr>

                                    </table>

                                </div>

                            </td>

                            <td>

                                <h1 class="brand-name">
                                    {{ $companyName }}
                                </h1>

                                <div class="brand-subtitle">
                                    Billing Invoice
                                </div>

                            </td>

                        </tr>

                    </table>

                    <div class="company-info">

                        @if ($companyAddress)

                            {!! nl2br(e($companyAddress)) !!}

                            <br>

                        @endif

                        @if ($companyEmail)

                            {{ $companyEmail }}

                            <br>

                        @endif

                        @if ($companyPhone)

                            {{ $companyPhone }}

                            <br>

                        @endif

                        @if ($companyWebsiteDisplay )

                            {{ $companyWebsiteDisplay  }}

                        @endif

                    </div>

                </td>

                <td style="width: 42%;">

                    <h2 class="invoice-heading">
                        INVOICE
                    </h2>

                    <table class="meta-table">

                        <tr>

                            <td class="meta-label">
                                Invoice #
                            </td>

                            <td class="meta-value">
                                #{{ $invoiceId }}
                            </td>

                        </tr>

                        <tr>

                            <td class="meta-label">
                                Date Issued
                            </td>

                            <td class="meta-value">
                                {{ $date ?: 'N/A' }}
                            </td>

                        </tr>

                        <tr>

                            <td class="meta-label">
                                Due Date
                            </td>

                            <td class="meta-value">
                                {{ $dueDate ?: 'N/A' }}
                            </td>

                        </tr>

                        @if ($datePaid)

                            <tr>

                                <td class="meta-label">
                                    Date Paid
                                </td>

                                <td class="meta-value">
                                    {{ $datePaid }}
                                </td>

                            </tr>

                        @endif

                        <tr>

                            <td class="meta-label">
                                Status
                            </td>

                            <td class="meta-value">
                                {{ $status }}
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </table>

    </div>


    {{-- BILL TO / PAY TO --}}

    <div class="bill-box">

        <table class="bill-row">

            <tr>

                {{-- BILL TO --}}

                <td class="bill-to">

                    <p class="label">
                        Invoice To
                    </p>

                    <div class="bill-text">

                        @if ($clientCompany)

                            <div class="company-client-name">
                                {{ $clientCompany }}
                            </div>

                        @endif

                        @if ($clientName)

                            <div class="client-person">
                                {{ $clientName }}
                            </div>

                        @endif

                        @if ($clientAddressHtml)

                            <div class="address-block">
                                {!! $clientAddressHtml !!}
                            </div>

                        @endif

                        @if ($clientEmail)

                            <div style="margin-top: 5px;">
                                {{ $clientEmail }}
                            </div>

                        @endif

                        @if ($clientPhone)

                            <div>
                                {{ $clientPhone }}
                            </div>

                        @endif

                    </div>

                </td>


                {{-- PAY TO --}}

                <td class="pay-to">

                    <p
                        class="label"
                        style="text-align: right;"
                    >
                        Pay To
                    </p>

                    <div
                        class="bill-text"
                        style="text-align: right;"
                    >

                        @if ($payToName)

                            <div
                                class="company-client-name"
                                style="text-align: right;"
                            >
                                {{ $payToName }}
                            </div>

                        @endif

                        @if ($payToAddress)

                            <div class="address-block">
                                {!! nl2br(e($payToAddress)) !!}
                            </div>

                        @endif

                        @if ($displayPaymentMethod)

                            <div class="payment-method">

                                <strong>
                                    Payment Method:
                                </strong>

                                {{ $displayPaymentMethod }}

                            </div>

                        @endif

                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ITEMS --}}

    @if (count($items))

        <div class="items-wrap">

            <table class="items">

                <thead>

                    <tr>

                        <th>
                            Description
                        </th>

                        <th class="center">
                            Type
                        </th>

                        <th class="right">
                            Amount
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($items as $item)

                        <tr>

                            <td>

                                <span class="item-desc">

                                    {{ data_get(
                                        $item,
                                        'description',
                                        'Item'
                                    ) }}

                                </span>

                            </td>

                            <td class="center">

                                <span class="item-type">

                                    {{ data_get(
                                        $item,
                                        'type',
                                        '-'
                                    ) }}

                                </span>

                            </td>

                            <td class="right">

                                <span class="currency">

                                    {{ $formatAmount(
                                        data_get(
                                            $item,
                                            'amount',
                                            0
                                        )
                                    ) }}

                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif


    {{-- SUMMARY --}}

    <div class="summary-wrap">

        <table class="summary-table">

            <tr>

                <td>
                    Subtotal
                </td>

                <td class="amount">

                    <span class="currency">
                        {{ $formatAmount($subtotal) }}
                    </span>

                </td>

            </tr>


            @if ((float) $discount > 0)

                <tr>

                    <td>
                        Discount
                    </td>

                    <td class="amount discount">

                        -

                        <span class="currency">
                            {{ $formatAmount($discount) }}
                        </span>

                    </td>

                </tr>

            @endif


            @if ((float) $credit > 0)

                <tr>

                    <td>
                        Credit
                    </td>

                    <td class="amount discount">

                        -

                        <span class="currency">
                            {{ $formatAmount($credit) }}
                        </span>

                    </td>

                </tr>

            @endif


            @if ((float) $tax > 0)

                <tr>

                    <td>
                        Tax
                    </td>

                    <td class="amount">

                        <span class="currency">
                            {{ $formatAmount($tax) }}
                        </span>

                    </td>

                </tr>

            @endif


            @if ((float) $tax2 > 0)

                <tr>

                    <td>
                        Tax 2
                    </td>

                    <td class="amount">

                        <span class="currency">
                            {{ $formatAmount($tax2) }}
                        </span>

                    </td>

                </tr>

            @endif


            <tr class="summary-divider">

                <td colspan="2"></td>

            </tr>


            <tr>

                <td class="total-label">
                    Total
                </td>

                <td class="total-value">

                    <span class="currency">
                        {{ $formatAmount($total) }}
                    </span>

                </td>

            </tr>


            @if ((float) $balance > 0)

                <tr>

                    <td class="total-label balance-label">
                        Balance Due
                    </td>

                    <td class="total-value balance-value">

                        <span class="currency">
                            {{ $formatAmount($balance) }}
                        </span>

                    </td>

                </tr>

            @endif

        </table>

    </div>


    {{-- FOOTER --}}

    <div class="footer">

        <table class="footer-table">

            <tr>

                <td>

                    <p class="label">
                        Notes
                    </p>

                    <div class="terms">

                        {{ ! empty($invoice['notes'])
                            ? $invoice['notes']
                            : 'Thank you for your business. Please contact support for any invoice-related queries.'
                        }}

                    </div>

                </td>

                <td>

                    <p class="thanks">
                        Thank you!
                    </p>

                    <div class="footer-text">

                        {{ $companyName }}

                        @if ($companyWebsiteDisplay )

                            &middot;

                            {{ $companyWebsiteDisplay  }}

                        @endif

                    </div>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>

</html>