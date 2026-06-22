<?php

namespace App\Services;

use App\Models\InvoiceTheme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class InvoiceThemeRenderer
{
    public const PLACEHOLDERS = [
        'brand_color',
        'logo',
        'invoice_number',
        'issue_date',
        'due_date',
        'due_date_line',
        'due_meta_card',
        'invoice_date_block',

        'seller_name',
        'seller_company_name',
        'seller_email',
        'seller_phone',
        'seller_address',
        'seller_website',
        'seller_contact_block',

        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_contact_block',

        'customer_shipping_name',
        'customer_shipping_email',
        'customer_shipping_phone',
        'customer_shipping_address',
        'shipping_contact_block',

        'currency',
        'item_rows',
        'item_rows_compact',

        'subtotal',
        'tax_total',
        'discount_type',
        'discount_value',
        'discount_label',
        'discount',
        'discount_row',
        'discount_table_row',
        'shipping_charge',
        'shipping_row',
        'shipping_table_row',
        'total',

        'notes',
        'terms',
        'notes_section',
        'terms_section',

        'payment_method',
        'sender_number',
        'transaction_id',
        'payment_section',
    ];

    public static function starterHtml(): string
    {
        return <<<'HTML'
<header class="invoice-header">
    <div>
        {{logo}}
        <p class="eyebrow">FROM</p>
        <h1>{{seller_name}}</h1>
        {{seller_contact_block}}
    </div>

    <div class="invoice-meta">
        <h2>INVOICE</h2>
        <strong>{{invoice_number}}</strong>
        {{invoice_date_block}}
    </div>
</header>

<section class="bill-to">
    <p class="eyebrow">BILL TO</p>
    <h3>{{customer_name}}</h3>
    {{customer_contact_block}}
</section>

<table class="items">
    <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Tax</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        {{item_rows}}
    </tbody>
</table>

<section class="totals">
    <p><span>Subtotal</span><strong>{{currency}} {{subtotal}}</strong></p>
    <p><span>Tax</span><strong>{{currency}} {{tax_total}}</strong></p>
    {{shipping_row}}
    {{discount_row}}
    <p class="grand-total"><span>Total</span><strong>{{currency}} {{total}}</strong></p>
</section>

<footer>
    {{notes_section}}
    {{terms_section}}
</footer>

{{payment_section}}
HTML;
    }

    public static function starterCss(): string
    {
        return <<<'CSS'
@page {
    margin: 28px;
}

body {
    margin: 0;
    color: #172033;
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.5;
}

.invoice-header {
    display: table;
    width: 100%;
    padding-bottom: 22px;
    border-bottom: 5px solid {{brand_color}};
}

.invoice-header > div {
    display: table-cell;
    width: 50%;
    vertical-align: top;
}

.invoice-header h1 {
    margin: 3px 0 8px;
    font-size: 22px;
}

.invoice-meta {
    text-align: right;
}

.invoice-meta h2 {
    margin: 0;
    color: {{brand_color}};
    font-size: 30px;
}

.eyebrow {
    margin: 0 0 5px;
    color: {{brand_color}};
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.contact-lines,
.date-lines {
    margin: 0;
    color: #334155;
    line-height: 1.65;
}

.bill-to {
    margin-top: 22px;
    padding: 14px;
    background: #f8fafc;
    border: 1px solid #dbe3ee;
}

.bill-to h3 {
    margin: 4px 0 0;
}

.items {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

.company-website {
    color: #999ca1 !important;
    text-align: right;
}

.items th {
    padding: 10px 8px;
    color: #fff;
    background: {{brand_color}};
    text-align: left;
}

.items td {
    padding: 10px 8px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
}

.items th:not(:first-child),
.items td:not(:first-child) {
    text-align: right;
}

.item-name {
    font-weight: bold;
}

.item-description {
    margin-top: 3px;
    color: #64748b;
    font-size: 9px;
    line-height: 1.45;
}

.totals {
    width: 280px;
    margin: 22px 0 0 auto;
}

.totals p {
    display: table;
    width: 100%;
    margin: 0;
    padding: 5px 0;
}

.totals span,
.totals strong {
    display: table-cell;
    width: 50%;
}

.totals strong {
    text-align: right;
}

.grand-total {
    border-top: 2px solid {{brand_color}};
    color: {{brand_color}};
    font-size: 15px;
}

footer {
    display: table;
    width: 100%;
    margin-top: 30px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    color: #64748b;
}

.invoice-footer-block {
    display: table-cell;
    width: 50%;
    padding-right: 20px;
    vertical-align: top;
}

.invoice-footer-block strong {
    color: {{brand_color}};
}

.invoice-footer-block p {
    margin: 5px 0 0;
}

.payment-info {
    margin-top: 16px;
    padding: 14px;
    background: #f8fafc;
    border: 1px solid #dbe3ee;
}

.payment-info p {
    display: table;
    width: 100%;
    margin: 2px 0;
}

.payment-info span {
    display: table-cell;
    width: 30%;
    font-size: 9px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: {{brand_color}};
}

.payment-info strong {
    display: table-cell;
    width: 70%;
    text-align: right;
}
CSS;
    }

    public static function redAccentHtml(): string
    {
        return <<<'HTML'
<main class="invoice">
    <div class="top-frame"></div>
    <div class="left-frame"></div>
    <div class="side-accent"></div>
    <div class="bottom-accent"></div>

    <table class="invoice-header">
        <tr>
            <td class="brand">
                {{logo}}
                <strong>{{seller_company_name}}</strong>
                <small>{{seller_website}}</small>
            </td>
            <td class="invoice-title"><span>+</span><strong>INVOICE</strong></td>
        </tr>
    </table>

    <table class="invoice-meta">
        <tr>
            <td class="bill-to">
                <p class="eyebrow">INVOICE TO:</p>
                <h2>{{customer_name}}</h2>
                {{customer_contact_block}}
            </td>
            <td class="meta-card dark">
                <span>INVOICE NO:</span>
                <strong>{{invoice_number}}</strong>
            </td>
            <td class="meta-card dark">
                <span>DATE:</span>
                <strong>{{issue_date}}</strong>
            </td>
            <td class="meta-card red">
                <span>TOTAL DUE:</span>
                <strong>{{currency}} {{total}}</strong>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>ITEM DESCRIPTION</th>
                <th>QUANTITY</th>
                <th>UNIT PRICE</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            {{item_rows_compact}}
        </tbody>
    </table>

    <table class="payment-summary">
        <tr>
            <td class="payment-column">
                {{payment_section}}
                {{terms_section}}
            </td>
            <td class="totals-column">
                <table class="totals">
                    <tr><td>SUB TOTAL</td><td>{{currency}} {{subtotal}}</td></tr>
                    <tr><td>TAX</td><td>{{currency}} {{tax_total}}</td></tr>
                    {{shipping_table_row}}
                    {{discount_table_row}}
                    <tr class="grand-total"><td>TOTAL DUE</td><td>{{currency}} {{total}}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <section class="signature">
        <strong>{{seller_name}}</strong>
        <span>Authorized Signature</span>
    </section>

    <table class="invoice-footer">
        <tr>
            <td>
                <h3>{{seller_company_name}}</h3>
                <p>{{seller_address}}</p>
            </td>
            <td>
                <p>{{seller_phone}}<br>{{seller_email}}</p>
            </td>
            <td class="website">{{seller_website}}</td>
        </tr>
    </table>

    {{notes_section}}
</main>
HTML;
    }

    public static function redAccentCss(): string
    {
        return <<<'CSS'
@page {
    size: A4 portrait;
    margin: 0;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    color: #33353a;
    background: #ffffff;
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10px;
}

.invoice {
    padding: 39px 48px 40px 62px;
    background: #ffffff;
}

.top-frame {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    height: 14px;
    background: #24262d;
}

.left-frame {
    position: fixed;
    top: 14px;
    left: 0;
    width: 12px;
    height: 66px;
    background: #24262d;
}

.side-accent {
    position: fixed;
    top: 80px;
    right: 0;
    width: 12px;
    bottom: 0;
    background: #f43b3c;
}

.bottom-accent {
    position: fixed;
    right: 0;
    bottom: 0;
    width: 205px;
    height: 12px;
    background: #f43b3c;
}

.invoice-header,
.invoice-meta,
.payment-summary,
.invoice-footer {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.invoice-header td {
    height: 70px;
    vertical-align: middle;
}

.brand {
    width: 60%;
    color: #111111;
}

.brand img {
    max-width: 125px !important;
    max-height: 48px !important;
    margin-right: 10px;
    vertical-align: middle;
}

.brand strong,
.brand small {
    display: block;
}

.brand strong {
    font-size: 20px;
}

.brand small {
    margin-top: 4px;
    color: #6c6e73;
    font-size: 8px;
    letter-spacing: 2px;
}

.invoice-title {
    width: 40%;
    color: #ffffff;
    text-align: right;
}

.invoice-title span,
.invoice-title strong {
    display: inline-block;
    padding: 9px 12px;
    font-size: 20px;
}

.invoice-title span {
    background: #24262d;
}

.invoice-title strong {
    background: #f43b3c;
}

.invoice-meta {
    margin: 32px 0 24px;
}

.invoice-meta td {
    vertical-align: bottom;
}

.bill-to {
    width: 32%;
    padding-right: 16px;
}

.eyebrow {
    margin: 0 0 8px;
    color: #f43b3c;
    font-size: 9px;
    font-weight: bold;
}

.bill-to h2 {
    margin: 0 0 5px;
    font-size: 12px;
}

.contact-lines {
    margin: 0;
    color: #7d8085;
    font-size: 9px;
    line-height: 1.45;
}

.meta-card {
    width: 22.66%;
    height: 55px;
    padding: 11px 10px;
    color: #ffffff;
    border-left: 7px solid #ffffff;
}

.meta-card.dark {
    background: #24262d;
}

.meta-card.red {
    background: #f43b3c;
}

.meta-card span,
.meta-card strong {
    display: block;
}

.meta-card span {
    margin-bottom: 6px;
    font-size: 7px;
}

.meta-card strong {
    font-size: 9px;
}

.items {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.items th {
    padding: 8px 9px;
    color: #ffffff;
    background: #24262d;
    border-right: 5px solid #ffffff;
    font-size: 8px;
    text-align: left;
}

.items th:first-child {
    width: 55%;
}

.items th:not(:first-child) {
    width: 15%;
    text-align: right;
}

.items th:last-child {
    border-right: 0;
}

.items td {
    height: 48px;
    padding: 9px 10px;
    vertical-align: middle;
}

.items td:not(:first-child) {
    text-align: right;
}

.items tbody tr:nth-child(even) td {
    background: #f0f1f2;
}

.item-name {
    font-weight: bold;
}

.item-description {
    margin-top: 3px;
    color: #999ca1;
    font-size: 8px;
}

.item-placeholder td {
    color: transparent;
}

.payment-summary {
    margin-top: 12px;
}

.payment-summary td {
    vertical-align: top;
}

.payment-column {
    width: 55%;
    padding-right: 46px;
}

.totals-column {
    width: 45%;
}

.payment-info {
    padding: 13px 15px;
    color: #ffffff;
    background: #24262d;
}

.payment-info .eyebrow {
    color: #ffffff;
}

.payment-info p {
    display: table;
    width: 100%;
    margin: 3px 0;
}

.payment-info span,
.payment-info strong {
    display: table-cell;
}

.payment-info span {
    width: 36%;
    color: #f43b3c;
}

.payment-info strong {
    width: 64%;
    text-align: right;
}

.invoice-footer-block {
    margin-top: 16px;
    color: #999ca1;
}

.invoice-footer-block strong {
    color: #33353a;
}

.invoice-footer-block p {
    margin: 5px 0 0;
    line-height: 1.45;
}

.totals {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 3px;
}

.totals td {
    padding: 8px 11px;
    background: #f0f1f2;
}

.totals td:last-child {
    text-align: right;
    font-weight: bold;
}

.totals .grand-total td {
    color: #ffffff;
    background: #f43b3c;
}

.signature {
    position: fixed;
    right: 70px;
    bottom: 165px;
    width: 160px;
    margin: 0;
    text-align: center;
}

.signature strong,
.signature span {
    display: block;
}

.signature span {
    margin-top: 4px;
    color: #999ca1;
    font-size: 8px;
}

.invoice-footer {
    position: fixed;
    right: 48px;
    bottom: 42px;
    left: 62px;
    width: auto;
    margin: 0;
}

.invoice-footer td {
    width: 33.33%;
    color: #777a7f;
    vertical-align: bottom;
}

.invoice-footer h3,
.invoice-footer p {
    margin: 0;
}

.invoice-footer h3 {
    color: #f43b3c;
    font-size: 11px;
}

.invoice-footer p {
    margin-top: 5px;
    line-height: 1.5;
}

.website {
    color: #f43b3c !important;
    text-align: right;
}

.invoice > .invoice-footer-block {
    position: fixed;
    bottom: 105px;
    left: 62px;
    width: 300px;
    margin: 0;
    font-size: 8px;
}
CSS;
    }

    public static function backgroundOverlayCss(): string
    {
        return <<<'CSS'
@page {
    size: A4 portrait;
    margin: 0;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    color: #33353a;
    background: transparent;
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9pt;
}

.invoice-sheet {
    padding: 46px 72px 40px;
}

.header,
.invoice-meta,
.summary-section,
.seller-footer {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.header td {
    height: 62px;
    vertical-align: middle;
}

.brand {
    width: 60%;
}

.brand img {
    display: block;
    max-width: 118px !important;
    max-height: 54px !important;
}

.brand-name {
    display: none;
}

.invoice-heading {
    width: 40%;
    text-align: right;
    white-space: nowrap;
}

.invoice-heading span {
    display: inline-block;
    color: #ffffff;
    vertical-align: middle;
}

.invoice-heading .plus {
    padding: 6px 8px;
    background: #24262d;
    font-size: 14pt;
}

.invoice-heading .title {
    padding: 7px 11px;
    background: #f43b3c;
    font-size: 13pt;
}

.invoice-meta {
    margin: 32px 0 22px;
}

.invoice-meta td {
    vertical-align: bottom;
}

.customer-block {
    width: 31%;
    padding: 0 14px 2px 8px;
}

.eyebrow {
    margin: 0 0 7px;
    color: #f43b3c;
    font-size: 7.5pt;
    font-weight: bold;
}

.customer-block h2 {
    margin: 0 0 4px;
    color: #24262d;
    font-size: 9pt;
}

.contact-lines {
    margin: 0;
    color: #777a7f;
    font-size: 7.5pt;
    line-height: 1.45;
}

.meta-card {
    width: 23%;
    height: 43px;
    padding: 9px 8px;
    color: #ffffff;
    border-left: 8px solid transparent;
    background-clip: padding-box;
}

.meta-card.dark {
    background-color: #24262d;
}

.meta-card.red {
    background-color: #f43b3c;
}

.meta-card span,
.meta-card strong {
    display: block;
}

.meta-card span {
    margin-bottom: 6px;
    font-size: 6.5pt;
}

.meta-card strong {
    font-size: 7.5pt;
}

.items {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.items th {
    height: 18px;
    padding: 0 7px;
    color: #ffffff;
    background: #24262d;
    border-right: 4px solid #ffffff;
    font-size: 7pt;
    text-align: left;
    vertical-align: middle;
}

.items th:first-child {
    width: 55%;
}

.items th:not(:first-child) {
    width: 15%;
    text-align: right;
}

.items th:last-child {
    border-right: 0;
}

.items td {
    height: 42px;
    padding: 7px 8px;
    vertical-align: middle;
}

.items td:not(:first-child) {
    text-align: right;
}

.items tbody tr:nth-child(even) td {
    background: #f0f1f2;
}

.item-name {
    font-size: 8pt;
    font-weight: bold;
}

.item-description {
    margin-top: 3px;
    color: #999ca1;
    font-size: 6.5pt;
}

.item-placeholder td {
    color: transparent;
}

.summary-section {
    margin-top: 5px;
}

.summary-section td {
    vertical-align: top;
}

.payment-column {
    width: 55%;
    padding-right: 43px;
}

.totals-column {
    width: 45%;
}

.payment-info {
    min-height: 62px;
    padding: 9px 11px;
    color: #ffffff;
    background: #24262d;
}

.payment-info .eyebrow {
    margin-bottom: 5px;
    color: #ffffff;
}

.payment-info p {
    display: table;
    width: 100%;
    margin: 2px 0;
}

.payment-info span,
.payment-info strong {
    display: table-cell;
    font-size: 7pt;
}

.payment-info span {
    width: 34%;
    color: #f43b3c;
}

.payment-info strong {
    width: 66%;
    color: #ffffff;
    text-align: right;
}

.terms-wrapper {
    margin: 13px 0 0 8px;
}

.terms-wrapper .invoice-footer-block {
    margin: 0;
    color: #999ca1;
}

.terms-wrapper .invoice-footer-block strong {
    color: #33353a;
    font-size: 8pt;
}

.terms-wrapper .invoice-footer-block p {
    margin: 4px 0 0;
    font-size: 7pt;
    line-height: 1.4;
}

.totals {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 2px;
}

.totals td {
    height: 19px;
    padding: 4px 8px;
    background: #f0f1f2;
    font-size: 7.5pt;
}

.totals td:last-child {
    text-align: right;
    font-weight: bold;
}

.totals .grand-total td {
    color: #ffffff;
    background: #f43b3c;
}

.signature {
    position: fixed;
    right: 78px;
    bottom: 132px;
    width: 130px;
    text-align: center;
}

.signature strong,
.signature span {
    display: block;
}

.signature strong {
    font-size: 8.5pt;
}

.signature span {
    margin-top: 3px;
    color: #999ca1;
    font-size: 7pt;
}

.seller-footer {
    position: fixed;
    bottom: 82px;
    left: 72px;
    width: 649px;
    border-collapse: collapse;
    table-layout: fixed;
}

.seller-footer td {
    color: #777a7f;
    font-size: 7pt;
    vertical-align: bottom;
}

.company-address {
    width: 34%;
}

.seller-footer h3,
.seller-footer p {
    margin: 0;
}

.seller-footer h3 {
    margin-bottom: 4px;
    color: #f43b3c;
    font-size: 8.5pt;
}

.seller-footer p {
    line-height: 1.3;
}

.company-contact {
    width: 33%;
    padding-left: 18px;
}

.company-website {
    width: 33%;
    padding: 0;
    color: #999ca1 !important;
    text-align: right !important;
    white-space: nowrap;
}

.notes-wrapper {
    display: none;
}
CSS;
    }

    public static function hasUnsafeHtml(string $html): bool
    {
        return (bool) preg_match(
            '/<\s*(script|iframe|object|embed|form|input|button|link|meta|base|svg|img)\b|on[a-z]+\s*=|javascript\s*:|@import|url\s*\(|<\?(php|=)|{!!|@php|@include/i',
            $html,
        );
    }

    public static function hasUnsafeCss(string $css): bool
    {
        return (bool) preg_match('/@import|url\s*\(|expression\s*\(|javascript\s*:|behavior\s*:/i', $css);
    }

    public static function importHtmlDocument(string $document): array
    {
        if (preg_match('/<\s*script\b|on[a-z]+\s*=|javascript\s*:|<\?(php|=)|{!!|@php|@include/i', $document)) {
            throw new \InvalidArgumentException('Scripts, event handlers, PHP, and Blade directives are not allowed.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$document,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $styles = [];

        foreach (iterator_to_array($dom->getElementsByTagName('style')) as $style) {
            $styles[] = $style->textContent;
            $style->parentNode?->removeChild($style);
        }

        foreach (['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'base', 'svg', 'img'] as $tag) {
            if ($dom->getElementsByTagName($tag)->length > 0) {
                throw new \InvalidArgumentException("The uploaded theme contains a disallowed <{$tag}> element.");
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $html = $body ? self::innerHtml($body) : $dom->saveHTML();
        $css = implode("\n\n", $styles);

        if (self::hasUnsafeHtml($html) || self::hasUnsafeCss($css)) {
            throw new \InvalidArgumentException('The uploaded theme contains unsafe HTML or CSS.');
        }

        return [trim($html), trim($css)];
    }

    public function render(
        InvoiceTheme $theme,
        array $invoice,
        iterable $items,
        float $subtotal,
        float $taxTotal,
        float $total,
        ?string $logoDataUri = null,
    ): string {
        if (
            self::hasUnsafeHtml((string) $theme->html_template)
            || self::hasUnsafeCss((string) $theme->css_styles)
        ) {
            throw new \InvalidArgumentException('The invoice theme contains unsafe template code.');
        }

        return $this->renderDocument(
            $theme->html_template ?: self::starterHtml(),
            $theme->css_styles ?: self::starterCss(),
            $theme->brand_color ?: '#2563eb',
            $invoice,
            $items,
            $subtotal,
            $taxTotal,
            $total,
            $logoDataUri,
            $this->backgroundDataUri($theme),
        );
    }

    public function preview(string $html, string $css, string $brandColor): string
    {
        if (self::hasUnsafeHtml($html) || self::hasUnsafeCss($css)) {
            return '<!DOCTYPE html><html><body style="font-family:Arial;padding:24px;color:#b91c1c">Preview blocked because the template contains unsafe code.</body></html>';
        }

        $invoice = [
            'invoice_number' => 'INV-2026-0042',
            'issue_date' => '2026-06-15',
            'due_date' => '2026-06-29',

            'seller_name' => 'TechWave Studio',
            'seller_email' => 'hello@example.com',
            'seller_phone' => '+880 1700-000000',
            'seller_address' => 'Dhaka, Bangladesh',
            'seller_company_name' => 'TechWave Studio',
            'seller_website' => 'https://example.com',

            'customer_name' => 'Sample Client',
            'customer_email' => 'client@example.com',
            'customer_phone' => '+880 1800-000000',
            'customer_address' => 'Banani, Dhaka',

            'customer_shipping_name' => 'Sample Client',
            'customer_shipping_email' => 'client@example.com',
            'customer_shipping_phone' => '+880 1800-000000',
            'customer_shipping_address' => 'Banani, Dhaka',

            'currency' => 'BDT',
            'discount' => 250,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_label' => '10.00%',
            'notes' => 'Thank you for your business.',
            'terms' => 'Payment is due within 14 days.',
            'payment_method' => 'bkash',
            'sender_number' => '01XXXXXXXXX',
            'transaction_id' => 'TrxID123ABC',
            'shipping_charge' => 200,
        ];

        $items = [
            [
                'name' => 'Website design',
                'description' => 'Homepage, service page, contact page and responsive layout.',
                'type' => 'service',
                'unit' => 'pcs',
                'quantity' => 1,
                'unit_price' => 25000,
                'tax' => 5,
                'line_total' => 25000,
                'item_tax' => 1250,
            ],
            [
                'name' => 'Hosting',
                'description' => 'One year standard hosting package.',
                'type' => 'goods',
                'unit' => 'pcs',
                'quantity' => 1,
                'unit_price' => 5000,
                'tax' => 0,
                'line_total' => 5000,
                'item_tax' => 0,
            ],
        ];

        $sampleLogo = 'data:image/svg+xml;base64,'.base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="280" height="100" viewBox="0 0 280 100">'
            .'<rect width="280" height="100" rx="14" fill="'.$brandColor.'"/>'
            .'<text x="140" y="61" text-anchor="middle" font-family="Arial" font-size="32" font-weight="700" fill="white">YOUR LOGO</text>'
            .'</svg>',
        );

        return $this->renderDocument($html, $css, $brandColor, $invoice, $items, 30000, 1250, 30950, $sampleLogo);
    }

    private function renderDocument(
        string $html,
        string $css,
        string $brandColor,
        array $invoice,
        iterable $items,
        float $subtotal,
        float $taxTotal,
        float $total,
        ?string $logoDataUri,
        ?string $backgroundDataUri = null,
    ): string {
        $currency = (string) ($invoice['currency'] ?? '');
        $shippingCharge = (float) ($invoice['shipping_charge'] ?? 0);
        $discountAmount = (float) ($invoice['discount'] ?? 0);

        $values = [
            'brand_color' => $brandColor,

            'logo' => $logoDataUri
                ? '<img src="'.e($logoDataUri).'" alt="Logo" style="max-width:140px;max-height:70px;object-fit:contain">'
                : '',

            'invoice_number' => $invoice['invoice_number'] ?? '',
            'issue_date' => $this->formatDate($invoice['issue_date'] ?? null),
            'due_date' => $this->formatDate($invoice['due_date'] ?? null),

            'due_date_line' => filled($invoice['due_date'] ?? null)
                ? 'Due: '.$this->formatDate($invoice['due_date'] ?? null)
                : '',
            'due_meta_card' => filled($invoice['due_date'] ?? null)
                ? '<td class="meta-card red"><span>DUE:</span><strong>'.e($currency).' '.number_format($total, 2).'</strong></td>'
                : '',

            'invoice_date_block' => $this->invoiceDateBlock($invoice),

            'seller_name' => $invoice['seller_name'] ?? '',
            'seller_company_name' => $invoice['seller_company_name'] ?? '',
            'seller_email' => $invoice['seller_email'] ?? '',
            'seller_phone' => $invoice['seller_phone'] ?? '',
            'seller_address' => $invoice['seller_address'] ?? '',
            'seller_website' => $invoice['seller_website'] ?? '',
            'seller_contact_block' => $this->contactBlock([
                $invoice['seller_email'] ?? '',
                $invoice['seller_phone'] ?? '',
                $invoice['seller_website'] ?? '',
                $invoice['seller_address'] ?? '',
            ]),

            'customer_name' => $invoice['customer_name'] ?? '',
            'customer_email' => $invoice['customer_email'] ?? '',
            'customer_phone' => $invoice['customer_phone'] ?? '',
            'customer_address' => $invoice['customer_address'] ?? '',
            'customer_contact_block' => $this->contactBlock([
                $invoice['customer_email'] ?? '',
                $invoice['customer_phone'] ?? '',
                $invoice['customer_address'] ?? '',
            ]),

            'customer_shipping_name' => $invoice['customer_shipping_name'] ?? '',
            'customer_shipping_email' => $invoice['customer_shipping_email'] ?? '',
            'customer_shipping_phone' => $invoice['customer_shipping_phone'] ?? '',
            'customer_shipping_address' => $invoice['customer_shipping_address'] ?? '',
            'shipping_contact_block' => $this->contactBlock([
                $invoice['customer_shipping_name'] ?? '',
                $invoice['customer_shipping_email'] ?? '',
                $invoice['customer_shipping_phone'] ?? '',
                $invoice['customer_shipping_address'] ?? '',
            ]),

            'currency' => $currency,
            'item_rows' => $this->renderItemRows($items, $currency),
            'item_rows_compact' => $this->renderCompactItemRows($items, $currency),

            'subtotal' => number_format($subtotal, 2),
            'tax_total' => number_format($taxTotal, 2),
            'shipping_charge' => number_format($shippingCharge, 2),

            'shipping_row' => $shippingCharge > 0
                ? '<p><span>Shipping</span><strong>'.e($currency).' '.number_format($shippingCharge, 2).'</strong></p>'
                : '',
            'shipping_table_row' => $shippingCharge > 0
                ? '<tr><td>SHIPPING</td><td>'.e($currency).' '.number_format($shippingCharge, 2).'</td></tr>'
                : '',

            'discount_type' => $invoice['discount_type'] ?? 'none',
            'discount_value' => number_format((float) ($invoice['discount_value'] ?? 0), 2),
            'discount_label' => $invoice['discount_label'] ?? '',
            'discount' => number_format($discountAmount, 2),

            'discount_row' => $discountAmount > 0
                ? '<p><span>Discount '.e($invoice['discount_label'] ?? '').'</span><strong>-'.e($currency).' '.number_format($discountAmount, 2).'</strong></p>'
                : '',
            'discount_table_row' => $discountAmount > 0
                ? '<tr><td>DISCOUNT '.e($invoice['discount_label'] ?? '').'</td><td>-'.e($currency).' '.number_format($discountAmount, 2).'</td></tr>'
                : '',

            'total' => number_format($total, 2),

            'notes' => $invoice['notes'] ?? '',
            'terms' => $invoice['terms'] ?? '',

            'notes_section' => filled($invoice['notes'] ?? '')
                ? '<div class="invoice-footer-block"><strong>Notes</strong><p>'.nl2br(e($invoice['notes'])).'</p></div>'
                : '',

            'terms_section' => filled($invoice['terms'] ?? '')
                ? '<div class="invoice-footer-block"><strong>Terms</strong><p>'.nl2br(e($invoice['terms'])).'</p></div>'
                : '',

            'payment_method' => $invoice['payment_method'] ?? '',
            'sender_number' => $invoice['sender_number'] ?? '',
            'transaction_id' => $invoice['transaction_id'] ?? '',
            'payment_section' => $this->renderPaymentSection($invoice),
        ];

        $rawTokens = [
            'logo',
            'item_rows',
            'item_rows_compact',
            'due_date_line',
            'due_meta_card',
            'invoice_date_block',
            'seller_contact_block',
            'customer_contact_block',
            'shipping_contact_block',
            'shipping_row',
            'shipping_table_row',
            'discount_row',
            'discount_table_row',
            'notes_section',
            'terms_section',
            'payment_section',
        ];

        $renderedHtml = $this->replaceTokens($html, $values, $rawTokens);
        $renderedHtml = $this->removeEmptyHtmlGaps($renderedHtml);

        $renderedCss = $this->replaceTokens($css, $values);

        $backgroundMarkup = $backgroundDataUri
            ? '<img class="invoice-theme-background" src="'.e($backgroundDataUri).'" alt="">'
            : '';

        $backgroundCss = $backgroundDataUri
            ? '.invoice-theme-background{position:fixed;top:0;right:0;bottom:0;left:0;width:100%;height:100%;z-index:0;}'
                .'.invoice-theme-content{position:relative;z-index:1;}'
            : '';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            .'<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:">'
            .'<style>'
            .$renderedCss
            .$backgroundCss
            .'</style></head><body>'
            .$backgroundMarkup
            .'<div class="invoice-theme-content">'
            .$renderedHtml
            .'</div></body></html>';
    }

    private function replaceTokens(string $content, array $values, array $rawTokens = []): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function (array $match) use ($values, $rawTokens) {
            $key = strtolower($match[1]);

            if (! array_key_exists($key, $values)) {
                return '';
            }

            if (in_array($key, $rawTokens, true)) {
                return (string) $values[$key];
            }

            return nl2br(e((string) $values[$key]));
        }, $content) ?? $content;
    }

    private function renderPaymentSection(array $invoice): string
    {
        $method = (string) ($invoice['payment_method'] ?? '');

        if (! $method) {
            return '';
        }

        $label = match ($method) {
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'bank' => 'Bank',
            'cash' => 'Cash',
            default => ucfirst($method),
        };

        $rows = '<p><span>Method</span><strong>'.e($label).'</strong></p>';

        if ($method !== 'cash') {
            $sender = $invoice['sender_number'] ?? '';
            $trxId = $invoice['transaction_id'] ?? '';

            if ($sender) {
                $rows .= '<p><span>Sender</span><strong>'.e($sender).'</strong></p>';
            }

            if ($trxId) {
                $rows .= '<p><span>TrxID</span><strong>'.e($trxId).'</strong></p>';
            }
        }

        return '<section class="payment-info"><p class="eyebrow">PAYMENT</p>'.$rows.'</section>';
    }

    private function renderItemRows(iterable $items, string $currency): string
    {
        $rows = '';

        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)));
            $qty = (float) ($item['quantity'] ?? 0);
            $qtyFormatted = $qty == (int) $qty
                ? (string) (int) $qty
                : rtrim(rtrim(number_format($qty, 2), '0'), '.');

            $description = trim((string) ($item['description'] ?? ''));

            $descriptionHtml = $description !== ''
                ? '<div class="item-description">'.nl2br(e($description)).'</div>'
                : '';

            $rows .= '<tr>'
                .'<td>'
                .'<div class="item-name">'.e((string) ($item['name'] ?? '')).'</div>'
                .$descriptionHtml
                .'</td>'
                .'<td>'.$qtyFormatted.'</td>'
                .'<td>'.e($currency).' '.number_format((float) ($item['unit_price'] ?? 0), 2).'</td>'
                .'<td>'.number_format((float) ($item['tax'] ?? 0), 1).'%</td>'
                .'<td><strong>'.e($currency).' '.number_format($lineTotal, 2).'</strong></td>'
                .'</tr>';
        }

        return $rows;
    }

    private function renderCompactItemRows(iterable $items, string $currency): string
    {
        $rows = '';
        $renderedItems = 0;

        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)));
            $quantity = (float) ($item['quantity'] ?? 0);
            $formattedQuantity = $quantity == (int) $quantity
                ? (string) (int) $quantity
                : rtrim(rtrim(number_format($quantity, 2), '0'), '.');

            $description = trim((string) ($item['description'] ?? ''));
            $descriptionHtml = $description !== ''
                ? '<div class="item-description">'.nl2br(e($description)).'</div>'
                : '';

            $rows .= '<tr>'
                .'<td><div class="item-name">'.e((string) ($item['name'] ?? '')).'</div>'.$descriptionHtml.'</td>'
                .'<td>'.$formattedQuantity.'</td>'
                .'<td>'.number_format((float) ($item['unit_price'] ?? 0), 2).'</td>'
                .'<td><strong>'.number_format($lineTotal, 2).'</strong></td>'
                .'</tr>';

            $renderedItems++;
        }

        while ($renderedItems < 5) {
            $rows .= '<tr class="item-placeholder">'
                .'<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>'
                .'</tr>';
            $renderedItems++;
        }

        return $rows;
    }

    private function contactBlock(array $lines): string
    {
        $cleanLines = collect($lines)
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(fn ($line) => nl2br(e($line)))
            ->values();

        if ($cleanLines->isEmpty()) {
            return '';
        }

        return '<p class="contact-lines">'.$cleanLines->implode('<br>').'</p>';
    }

    private function invoiceDateBlock(array $invoice): string
    {
        $lines = [];

        if (filled($invoice['issue_date'] ?? null)) {
            $lines[] = 'Issued: '.$this->formatDate($invoice['issue_date']);
        }

        if (filled($invoice['due_date'] ?? null)) {
            $lines[] = 'Due: '.$this->formatDate($invoice['due_date']);
        }

        if (empty($lines)) {
            return '';
        }

        return '<p class="date-lines">'.implode('<br>', array_map('e', $lines)).'</p>';
    }

    private function removeEmptyHtmlGaps(string $html): string
    {
        $html = preg_replace('/(<p\b[^>]*>)\s*(<br\s*\/?>\s*)+/i', '$1', $html) ?? $html;
        $html = preg_replace('/(<br\s*\/?>\s*)+(\s*<\/p>)/i', '$2', $html) ?? $html;
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $html) ?? $html;

        $html = preg_replace('/<p\b[^>]*>\s*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace('/<div\b[^>]*>\s*<\/div>/i', '', $html) ?? $html;
        $html = preg_replace('/<section\b[^>]*>\s*<\/section>/i', '', $html) ?? $html;

        return $html;
    }

    private function formatDate(?string $date): string
    {
        return filled($date) ? Carbon::parse($date)->format('M d, Y') : '';
    }

    private function backgroundDataUri(InvoiceTheme $theme): ?string
    {
        $path = $theme->pdf_background_image;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    private static function innerHtml(\DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
