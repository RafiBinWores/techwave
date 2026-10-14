<?php

namespace App\Support\QrCode;

use DOMDocument;
use InvalidArgumentException;

class LogoOptimizer
{
    public function normalize(string $contents, string $mime): array
    {
        if ($mime === 'image/svg+xml') {
            return ['contents' => $this->sanitizeSvg($contents), 'mime' => $mime];
        }
        $dimensions = @getimagesizefromstring($contents);
        if (! $dimensions || ! in_array($dimensions['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
            throw new InvalidArgumentException('Please upload a valid PNG, JPG, WebP or SVG logo.');
        }
        [$width, $height] = $dimensions;
        if ($width * $height > 16000000) {
            throw new InvalidArgumentException(sprintf('This logo is %d × %d pixels. Please resize it to at most 16 megapixels (for example, 4000 × 4000).', $width, $height));
        }
        $source = @imagecreatefromstring($contents);
        if (! $source) {
            throw new InvalidArgumentException('The logo image could not be read.');
        }
        $scale = min(1, 256 / max($width, $height));
        $outputWidth = max(1, (int) round($width * $scale));
        $outputHeight = max(1, (int) round($height * $scale));
        $output = imagecreatetruecolor($outputWidth, $outputHeight);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
        imagecopyresampled($output, $source, 0, 0, 0, 0, $outputWidth, $outputHeight, $width, $height);
        ob_start();
        imagepng($output, null, 9);
        $optimized = ob_get_clean();

        return ['contents' => $optimized, 'mime' => 'image/png'];
    }

    private function sanitizeSvg(string $contents): string
    {
        if (trim($contents) === '') {
            throw new InvalidArgumentException('Please upload a valid SVG logo.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($contents, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded || $document->doctype || $document->documentElement?->localName !== 'svg') {
            throw new InvalidArgumentException('Please upload a valid SVG logo without a document type.');
        }
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $element) {
            if (in_array(strtolower($element->localName), ['script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video', 'animate', 'set'], true)) {
                $element->parentNode?->removeChild($element);

                continue;
            }
            if ($element->localName === 'style' && (stripos($element->textContent, '@import') !== false || $this->hasExternalUrl($element->textContent))) {
                $element->parentNode?->removeChild($element);

                continue;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->localName);
                $value = trim($attribute->value);
                $externalReference = $name === 'href' && ! str_starts_with($value, '#') && ! preg_match('#^data:image/(png|jpeg|webp);base64,#i', $value);
                if (str_starts_with($name, 'on') || $externalReference || $this->hasExternalUrl($value)) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        return $document->saveXML($document->documentElement);
    }

    private function hasExternalUrl(string $value): bool
    {
        preg_match_all('/url\(([^)]*)\)/i', $value, $matches);
        foreach ($matches[1] as $target) {
            $target = trim(trim($target), "\"'");
            if (! str_starts_with($target, '#')) {
                return true;
            }
        }

        return false;
    }
}
