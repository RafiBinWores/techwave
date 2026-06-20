<?php

namespace App\Services;

use App\Models\InvoiceTheme;
use Illuminate\Support\Carbon;

class InvoiceThemeRenderer
{
    public const PLACEHOLDERS = [
        'brand_color',
        'logo',
        'invoice_number',
        'issue_date',
        'due_date',
        'due_date_line',
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

        'subtotal',
        'tax_total',
        'discount_type',
        'discount_value',
        'discount_label',
        'discount',
        'discount_row',
        'shipping_charge',
        'shipping_row',
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
    margin: 4px 0 8px;
}

.items {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
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
            '<?xml encoding="UTF-8">' . $document,
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

        $sampleLogo = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="280" height="100" viewBox="0 0 280 100">'
            . '<rect width="280" height="100" rx="14" fill="' . $brandColor . '"/>'
            . '<text x="140" y="61" text-anchor="middle" font-family="Arial" font-size="32" font-weight="700" fill="white">YOUR LOGO</text>'
            . '</svg>',
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
    ): string {
        $currency = (string) ($invoice['currency'] ?? '');
        $shippingCharge = (float) ($invoice['shipping_charge'] ?? 0);
        $discountAmount = (float) ($invoice['discount'] ?? 0);

        $values = [
            'brand_color' => $brandColor,

            'logo' => $logoDataUri
                ? '<img src="' . e($logoDataUri) . '" alt="Logo" style="max-width:140px;max-height:70px;object-fit:contain">'
                : '',

            'invoice_number' => $invoice['invoice_number'] ?? '',
            'issue_date' => $this->formatDate($invoice['issue_date'] ?? null),
            'due_date' => $this->formatDate($invoice['due_date'] ?? null),

            'due_date_line' => filled($invoice['due_date'] ?? null)
                ? 'Due: ' . $this->formatDate($invoice['due_date'] ?? null)
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

            'subtotal' => number_format($subtotal, 2),
            'tax_total' => number_format($taxTotal, 2),
            'shipping_charge' => number_format($shippingCharge, 2),

            'shipping_row' => $shippingCharge > 0
                ? '<p><span>Shipping</span><strong>' . e($currency) . ' ' . number_format($shippingCharge, 2) . '</strong></p>'
                : '',

            'discount_type' => $invoice['discount_type'] ?? 'none',
            'discount_value' => number_format((float) ($invoice['discount_value'] ?? 0), 2),
            'discount_label' => $invoice['discount_label'] ?? '',
            'discount' => number_format($discountAmount, 2),

            'discount_row' => $discountAmount > 0
                ? '<p><span>Discount ' . e($invoice['discount_label'] ?? '') . '</span><strong>-' . e($currency) . ' ' . number_format($discountAmount, 2) . '</strong></p>'
                : '',

            'total' => number_format($total, 2),

            'notes' => $invoice['notes'] ?? '',
            'terms' => $invoice['terms'] ?? '',

            'notes_section' => filled($invoice['notes'] ?? '')
                ? '<div class="invoice-footer-block"><strong>Notes</strong><p>' . nl2br(e($invoice['notes'])) . '</p></div>'
                : '',

            'terms_section' => filled($invoice['terms'] ?? '')
                ? '<div class="invoice-footer-block"><strong>Terms</strong><p>' . nl2br(e($invoice['terms'])) . '</p></div>'
                : '',

            'payment_method' => $invoice['payment_method'] ?? '',
            'sender_number' => $invoice['sender_number'] ?? '',
            'transaction_id' => $invoice['transaction_id'] ?? '',
            'payment_section' => $this->renderPaymentSection($invoice),
        ];

        $rawTokens = [
            'logo',
            'item_rows',
            'due_date_line',
            'invoice_date_block',
            'seller_contact_block',
            'customer_contact_block',
            'shipping_contact_block',
            'shipping_row',
            'discount_row',
            'notes_section',
            'terms_section',
            'payment_section',
        ];

        $renderedHtml = $this->replaceTokens($html, $values, $rawTokens);
        $renderedHtml = $this->removeEmptyHtmlGaps($renderedHtml);

        $renderedCss = $this->replaceTokens($css, $values);

        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:">'
            . '<style>'
            . $renderedCss
            . '</style></head><body>'
            . $renderedHtml
            . '</body></html>';
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

        $rows = '<p><span>Method</span><strong>' . e($label) . '</strong></p>';

        if ($method !== 'cash') {
            $sender = $invoice['sender_number'] ?? '';
            $trxId = $invoice['transaction_id'] ?? '';

            if ($sender) {
                $rows .= '<p><span>Sender</span><strong>' . e($sender) . '</strong></p>';
            }

            if ($trxId) {
                $rows .= '<p><span>TrxID</span><strong>' . e($trxId) . '</strong></p>';
            }
        }

        return '<section class="payment-info"><p class="eyebrow">PAYMENT</p>' . $rows . '</section>';
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
                ? '<div class="item-description">' . nl2br(e($description)) . '</div>'
                : '';

            $rows .= '<tr>'
                . '<td>'
                . '<div class="item-name">' . e((string) ($item['name'] ?? '')) . '</div>'
                . $descriptionHtml
                . '</td>'
                . '<td>' . $qtyFormatted . '</td>'
                . '<td>' . e($currency) . ' ' . number_format((float) ($item['unit_price'] ?? 0), 2) . '</td>'
                . '<td>' . number_format((float) ($item['tax'] ?? 0), 1) . '%</td>'
                . '<td><strong>' . e($currency) . ' ' . number_format($lineTotal, 2) . '</strong></td>'
                . '</tr>';
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

        return '<p class="contact-lines">' . $cleanLines->implode('<br>') . '</p>';
    }

    private function invoiceDateBlock(array $invoice): string
    {
        $lines = [];

        if (filled($invoice['issue_date'] ?? null)) {
            $lines[] = 'Issued: ' . $this->formatDate($invoice['issue_date']);
        }

        if (filled($invoice['due_date'] ?? null)) {
            $lines[] = 'Due: ' . $this->formatDate($invoice['due_date']);
        }

        if (empty($lines)) {
            return '';
        }

        return '<p class="date-lines">' . implode('<br>', array_map('e', $lines)) . '</p>';
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

    private static function innerHtml(\DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}