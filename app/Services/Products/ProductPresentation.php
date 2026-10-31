<?php

namespace App\Services\Products;

use App\Models\ProductPlan;
use DOMDocument;
use DOMElement;
use DOMNode;

class ProductPresentation
{
    /**
     * Resolve checkout links only for currently available offers.
     */
    public function checkoutUrl(ProductPlan $plan, string $cycle): ?string
    {
        $product = $plan->product;
        if (
            ! $product
            || ! $product->is_published
            || ! $product->pricing_enabled
            || $product->trashed()
            || $plan->trashed()
            || ! $plan->is_active
            || ! in_array($cycle, ['monthly', 'yearly', 'one_time'], true)
            || $plan->effectivePricing()[$cycle] === null
        ) {
            return null;
        }

        return route('client.products.purchase', ['slug' => $product->slug, 'plan' => $plan->id, 'cycle' => $cycle]);
    }

    /**
     * Rebuild saved editor HTML from allowed elements instead of trusting administrator markup.
     */
    public function overview(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }
        if (! str_contains($html, '<')) {
            return '<p>'.nl2br(e($html)).'</p>';
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            $document->loadHTML(
                '<?xml encoding="UTF-8"><div id="product-overview-root">'.$html.'</div>',
                LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );
            $root = $document->getElementById('product-overview-root');
            $output = '';
            foreach ($root?->childNodes ?? [] as $node) {
                $output .= $this->renderNode($node);
            }

            return $output;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function renderNode(DOMNode $node, int $depth = 0): string
    {
        if ($depth > 50) {
            return '';
        }
        if ($node->nodeType === XML_TEXT_NODE) {
            return e($node->nodeValue);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, [
            'script', 'style', 'iframe', 'object', 'embed',
            'svg', 'math', 'template', 'form',
        ], true)) {
            return '';
        }
        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= $this->renderNode($child, $depth + 1);
        }
        if (! in_array($tag, [
            'p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br',
            'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'span',
            'ol', 'ul', 'li', 'blockquote', 'pre', 'code', 'a', 'sub', 'sup',
        ], true)) {
            return $content;
        }
        $attributes = '';
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            if ($href !== '' && ProductPricing::safeUrl($href)) {
                $attributes .= ' href="'.e($href).'" rel="noopener noreferrer"';
            }
        }
        $classes = array_filter(
            preg_split('/\s+/', $node->getAttribute('class')),
            fn (string $class): bool => (bool) preg_match(
                '/^ql-(?:align-(?:center|right|justify)|indent-[1-8]|direction-rtl|font-(?:serif|monospace)|size-(?:small|large|huge)|code-block(?:-container)?)$/',
                $class,
            ),
        );
        if ($classes) {
            $attributes .= ' class="'.e(implode(' ', $classes)).'"';
        }
        if ($tag === 'li' && in_array($node->getAttribute('data-list'), ['bullet', 'ordered'], true)) {
            $attributes .= ' data-list="'.$node->getAttribute('data-list').'"';
        }
        $styles = [];
        foreach (explode(';', $node->getAttribute('style')) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value);
            if (
                in_array($property, ['color', 'background-color'], true)
                && preg_match('/^(?:#[a-f0-9]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9.,%\s]+\)|[a-z]+)$/i', $value)
            ) {
                $styles[] = $property.':'.$value;
            }
        }
        if ($styles) {
            $attributes .= ' style="'.e(implode(';', $styles)).'"';
        }

        return '<'.$tag.$attributes.'>'.($tag === 'br' ? '' : $content.'</'.$tag.'>');
    }
}
