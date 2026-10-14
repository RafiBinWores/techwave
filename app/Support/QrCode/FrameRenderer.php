<?php

namespace App\Support\QrCode;

class FrameRenderer
{
    public const PRESETS = [
        'none' => 'No frame',
        'scan-bottom' => 'Bottom banner',
        'scan-top' => 'Top banner',
        'rounded' => 'Rounded',
        'card' => 'Card',
        'outline' => 'Outline',
        'corners' => 'Corners',
        'badge' => 'Badge',
        'bottom-pill' => 'Bottom pill',
        'bottom-label' => 'Bottom label',
        'top-pill' => 'Top pill',
        'top-label' => 'Top label',
        'ribbon-bottom' => 'Bottom ribbon',
        'ribbon-top' => 'Top ribbon',
        'hanger' => 'Hanging tag',
        'clipboard' => 'Clipboard',
        'clapperboard' => 'Clapperboard',
        'circle' => 'Circle',
        'circle-double' => 'Double circle',
        'ticket' => 'Ticket',
        'shield' => 'Shield',
        'speech' => 'Speech bubble',
        'minimal-bottom' => 'Plain bottom',
        'minimal-top' => 'Plain top',
        'phone' => 'Phone',
        'scan-icon' => 'Scan badge',
        'diamond' => 'Diamond',
        'stamp' => 'Stamp',
    ];

    public const FONTS = ['sans' => 'Sans serif', 'serif' => 'Serif', 'mono' => 'Monospace'];

    public static function layouts(): array
    {
        return array_diff_key(self::PRESETS, array_flip([
            'card', 'corners', 'stamp', 'minimal-top', 'minimal-bottom', 'scan-icon', 'diamond',
        ]));
    }

    public function render(
        string $qrSvg,
        string $preset,
        string $text = 'SCAN ME',
        string $color = '#111827',
        ?string $textColor = null,
        string $background = '#ffffff',
        string $font = 'sans',
    ): string {
        if ($preset === 'none' || ! array_key_exists($preset, self::PRESETS)) {
            return $qrSvg;
        }

        $textColor ??= in_array($preset, ['minimal-top', 'minimal-bottom'], true) ? $this->color($color, '#111827') : '#ffffff';

        if (! in_array($preset, ['scan-bottom', 'scan-top', 'rounded', 'card', 'outline', 'corners', 'badge'], true)) {
            return $this->renderGalleryFrame($qrSvg, $preset, $text, $color, $textColor, $background, $font);
        }

        preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/', $qrSvg, $viewBox);
        preg_match('/width="([\d.]+)"/', $qrSvg, $width);
        preg_match('/height="([\d.]+)"/', $qrSvg, $height);
        $qrW = (float) ($viewBox[1] ?? $width[1] ?? 420);
        $qrH = (float) ($viewBox[2] ?? $height[1] ?? $qrW);
        $padding = max(12, round($qrW * 0.08));
        $text = mb_substr(trim($text), 0, 48);
        $captionHeight = $text === '' ? 0 : max(34, round($qrW * 0.18));
        $newW = $qrW + $padding * 2;
        $newH = $qrH + $padding * 2 + $captionHeight;
        $qrX = $padding;
        $qrY = $padding + ($preset === 'scan-top' ? $captionHeight : 0);
        $color = $this->color($color, '#111827');
        $textColor = $this->color($textColor, '#ffffff');
        $background = $this->color($background, '#ffffff');
        $filled = in_array($preset, ['scan-top', 'scan-bottom', 'rounded'], true);
        $radius = $preset === 'rounded' ? round($padding) : ($filled ? 6 : 20);
        $canvas = $filled ? $color : $background;
        $parts = [sprintf('<rect width="%s" height="%s" rx="%s" fill="%s"/>', $newW, $newH, $radius, $canvas)];

        if (in_array($preset, ['card', 'outline'], true)) {
            $parts[] = sprintf('<rect x="3" y="3" width="%s" height="%s" rx="18" fill="none" stroke="%s" stroke-width="%s"/>', $newW - 6, $newH - 6, $color, $preset === 'outline' ? 4 : 2);
        }

        if ($preset === 'corners') {
            $edge = $newW - 5;
            $bottom = $qrH + $padding * 2 - 5;
            $arm = round($newW * 0.14);
            $parts[] = sprintf('<path d="M5 %s V5 H%s M%s 5 H%s V%s M%s %s V%s H%s M%s %s H5 V%s" fill="none" stroke="%s" stroke-width="6" stroke-linecap="round"/>', $arm, $arm, $edge - $arm, $edge, $arm, $edge, $bottom - $arm, $bottom, $edge - $arm, $arm, $bottom, $bottom - $arm, $color);
        }

        // Preserve the complete QR image, including its quiet zone, inside every frame.
        $parts[] = sprintf('<rect x="%s" y="%s" width="%s" height="%s" rx="8" fill="%s"/>', $qrX - 3, $qrY - 3, $qrW + 6, $qrH + 6, $background);
        $parts[] = sprintf('<image x="%s" y="%s" width="%s" height="%s" href="data:image/svg+xml;base64,%s" preserveAspectRatio="xMidYMid meet"/>', $qrX, $qrY, $qrW, $qrH, base64_encode($qrSvg));

        if ($text !== '') {
            $captionY = $preset === 'scan-top' ? $padding : $qrY + $qrH;
            $fontSize = min(32, max(11, $qrW * 0.075), ($newW - $padding * 2) / max(1, mb_strlen($text) * 0.7));
            $family = match ($font) {
                'serif' => 'Georgia, serif',
                'mono' => 'Courier New, monospace',
                default => 'Arial, sans-serif',
            };
            $captionColor = $textColor;

            if (! $filled) {
                $parts[] = sprintf('<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="%s"/>', $padding, $captionY + 5, $qrW, $captionHeight - 5, $preset === 'badge' ? ($captionHeight - 5) / 2 : 6, $color);
            }

            $parts[] = sprintf('<text x="%s" y="%s" text-anchor="middle" fill="%s" font-size="%s" font-family="%s" font-weight="700">%s</text>', $newW / 2, $captionY + $captionHeight / 2 + $fontSize * 0.35, $captionColor, $fontSize, $family, htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s">%s</svg>', $newW, $newH, $newW, $newH, implode('', $parts));
    }

    private function renderGalleryFrame(string $qrSvg, string $preset, string $text, string $color, string $textColor, string $background, string $font): string
    {
        preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/', $qrSvg, $box);
        preg_match('/width="([\d.]+)"/', $qrSvg, $width);
        preg_match('/height="([\d.]+)"/', $qrSvg, $height);
        $qrW = (float) ($box[1] ?? $width[1] ?? 420);
        $qrH = (float) ($box[2] ?? $height[1] ?? $qrW);
        $padding = max(12, round($qrW * 0.06));
        $gap = max(6, round($qrW * 0.02));
        $stroke = max(2, round($qrW * 0.012));
        $text = mb_substr(trim($text), 0, 48);
        $captionH = $text === '' ? 0 : max(30, round($qrW * 0.15));
        $bottomCaption = in_array($preset, ['bottom-pill', 'bottom-label', 'ribbon-bottom', 'ticket'], true);
        $captionGap = $bottomCaption
            ? $padding / 2 + $stroke / 2 + max(8, round($qrW * 0.03)) + ($preset === 'ribbon-bottom' ? 2 : 0)
            : $gap;
        $captionSpace = $captionH > 0 ? $captionH + $captionGap : 0;
        $topCaption = in_array($preset, ['top-pill', 'top-label', 'ribbon-top', 'minimal-top'], true);
        $headerH = in_array($preset, ['hanger', 'clipboard', 'clapperboard', 'phone'], true) ? max(20, round($qrW * 0.13)) : 0;
        $color = $this->color($color, '#111827');
        $textColor = $this->color($textColor, '#ffffff');
        $background = $this->color($background, '#ffffff');
        $qrX = $padding;
        $qrY = $padding + $headerH + ($topCaption ? $captionSpace : 0);
        $canvasW = $qrW + $padding * 2;
        $canvasH = $qrH + $padding * 2 + $headerH + $captionSpace;
        $tailH = in_array($preset, ['shield', 'speech'], true) ? max(16, round($qrW * 0.1)) : 0;
        $canvasH += $tailH;
        $circle = in_array($preset, ['circle', 'circle-double'], true);
        if ($circle) {
            // The ring encloses the entire square QR, rather than clipping its corners.
            $diameter = ceil(hypot($qrW + $padding * 2, $qrH + $padding * 2)) + $stroke * 2;
            $canvasW = $diameter;
            $canvasH = $diameter + $captionSpace;
            $qrX = ($diameter - $qrW) / 2;
            $qrY = ($diameter - $qrH) / 2;
        } elseif ($preset === 'diamond') {
            $canvasW = $qrW + $qrH + $padding * 2;
            $canvasH = $canvasW + $captionSpace;
            $qrX = ($canvasW - $qrW) / 2;
            $qrY = ($canvasW - $qrH) / 2;
        }
        $parts = [$this->rect(0, 0, $canvasW, $canvasH, 0, $background)];
        $left = $qrX - $padding / 2;
        $top = $qrY - $padding / 2;
        $right = $qrX + $qrW + $padding / 2;
        $bottom = $qrY + $qrH + $padding / 2;
        $bodyW = $right - $left;
        $bodyH = $bottom - $top;
        $bodyBottom = $canvasH - $padding / 2 - $tailH;

        if ($circle) {
            $cx = $canvasW / 2;
            $radius = $cx - $stroke;
            $parts[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s"/>', $cx, $cx, $radius, $color, $stroke);
            if ($preset === 'circle') {
                $step = max(4, round($qrW / 29));
                for ($y = $stroke * 3; $y < $diameter - $stroke * 3; $y += $step) {
                    for ($x = $stroke * 3; $x < $diameter - $stroke * 3; $x += $step) {
                        $insideQr = $x + $step > $qrX - 4 && $x < $qrX + $qrW + 4 && $y + $step > $qrY - 4 && $y < $qrY + $qrH + 4;
                        if (! $insideQr && hypot($x + $step / 2 - $cx, $y + $step / 2 - $cx) < $radius - $stroke * 3 && ((int) ($x / $step) * 7 + (int) ($y / $step) * 11) % 5 < 2) {
                            $parts[] = $this->rect($x, $y, $step * 0.7, $step * 0.7, $step * 0.1, $color);
                        }
                    }
                }
            }
            if ($preset === 'circle-double') {
                $parts[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s"/>', $cx, $cx, $radius - $stroke * 2, $color, $stroke / 2);
            }
        } elseif ($preset === 'diamond') {
            $parts[] = $this->polygon([[$canvasW / 2, $stroke], [$canvasW - $stroke, $canvasW / 2], [$canvasW / 2, $canvasW - $stroke], [$stroke, $canvasW / 2]], 'none', $color, $stroke);
        } elseif ($preset === 'hanger') {
            $loopW = $qrW * 0.3;
            $parts[] = $this->rect(($canvasW - $loopW) / 2, $stroke, $loopW, $headerH + $padding, $headerH * 0.35, $background, $color, $stroke * 1.5);
            $parts[] = $this->rect($left, $top, $bodyW, $bodyBottom - $top, 8, $color);
        } elseif ($preset === 'clipboard') {
            $parts[] = $this->rect($left, $top, $bodyW, $bodyBottom - $top, 8, $color);
            $parts[] = $this->rect($canvasW * 0.34, $padding / 2, $canvasW * 0.32, $headerH + $padding / 2, 6, $color);
            $parts[] = $this->rect($canvasW * 0.43, $padding / 2 + $stroke * 2, $canvasW * 0.14, $headerH * 0.2, 3, $background);
        } elseif ($preset === 'clapperboard') {
            $parts[] = $this->rect($left, $top, $bodyW, $bodyBottom - $top, 4, $color);
            $slateTop = $padding / 2;
            $slateBottom = $top - $gap / 2;
            $slant = $headerH * 0.17;
            $points = sprintf('%s,%s %s,%s %s,%s %s,%s', $left, $slateTop + $slant, $right, $slateTop, $right, $slateBottom - $slant, $left, $slateBottom);
            $parts[] = '<defs><clipPath id="frame-clapper"><polygon points="'.$points.'"/></clipPath></defs>';
            $parts[] = '<polygon points="'.$points.'" fill="'.$color.'"/>';
            $parts[] = '<g clip-path="url(#frame-clapper)">';
            $stripeW = $bodyW / 8;
            for ($i = -1; $i < 9; $i++) {
                $x = $left + $i * $stripeW;
                $parts[] = $this->polygon([[$x, $slateTop], [$x + $stripeW * 0.48, $slateTop], [$x - $headerH * 0.35 + $stripeW * 0.48, $slateBottom], [$x - $headerH * 0.35, $slateBottom]], $background);
            }
            $parts[] = '</g>';
        } elseif ($preset === 'phone') {
            $parts[] = $this->rect($left, $padding / 2, $bodyW, $canvasH - $padding, max(14, $qrW * 0.06), $color);
            $parts[] = $this->rect($canvasW * 0.39, $padding / 2 + $headerH * 0.32, $canvasW * 0.22, max(2, $stroke), $stroke, $background);
        } elseif ($preset === 'shield') {
            $parts[] = $this->polygon([[$left, $top], [$right, $top], [$right, $bodyBottom], [$canvasW / 2, $canvasH - $stroke], [$left, $bodyBottom]], $background, $color, $stroke);
        } elseif ($preset === 'speech') {
            $parts[] = $this->polygon([[$left, $top], [$right, $top], [$right, $bodyBottom], [$canvasW * 0.43, $bodyBottom], [$canvasW * 0.3, $canvasH - $stroke], [$canvasW * 0.3, $bodyBottom], [$left, $bodyBottom]], $background, $color, $stroke);
        } elseif (! in_array($preset, ['minimal-bottom', 'minimal-top', 'scan-icon'], true)) {
            $radius = in_array($preset, ['bottom-pill', 'top-pill', 'ticket'], true) ? 8 : 0;
            $border = $this->rect($left, $top, $bodyW, $bodyH, $radius, 'none', $color, $stroke);
            if ($preset === 'stamp') {
                $border = str_replace('/>', ' stroke-dasharray="'.$stroke * 1.5.' '.$stroke.'"/>', $border);
            }
            $parts[] = $border;
            if ($preset === 'ticket') {
                $notchR = $padding * 0.3;
                foreach ([$left, $right] as $notchX) {
                    $parts[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="%s"/>', $notchX, $top + $bodyH / 2, $notchR, $background);
                }
            }
        }

        $parts[] = $this->rect($qrX - 2, $qrY - 2, $qrW + 4, $qrH + 4, 0, $background);
        $parts[] = sprintf('<image x="%s" y="%s" width="%s" height="%s" href="data:image/svg+xml;base64,%s" preserveAspectRatio="xMidYMid meet"/>', $qrX, $qrY, $qrW, $qrH, base64_encode($qrSvg));

        if ($text !== '') {
            $captionY = $topCaption ? $padding / 2 : $qrY + $qrH + $captionGap;
            if ($circle || $preset === 'diamond') {
                $captionY = $canvasW + $gap / 2;
            }
            $matchBorder = in_array($preset, [
                'bottom-pill', 'top-pill', 'bottom-label', 'top-label', 'ribbon-bottom', 'ribbon-top', 'ticket',
            ], true);
            $captionW = $matchBorder ? $bodyW + $stroke : $qrW;
            $captionX = ($canvasW - $captionW) / 2;
            $pill = in_array($preset, ['bottom-pill', 'top-pill', 'scan-icon', 'circle', 'circle-double'], true);
            $minimal = in_array($preset, ['minimal-bottom', 'minimal-top'], true);
            $ribbon = in_array($preset, ['ribbon-bottom', 'ribbon-top'], true);
            if ($ribbon) {
                $wing = $padding * 0.8;
                $parts[] = $this->polygon([[$captionX, $captionY - 2], [$captionX + $captionW, $captionY - 2], [$captionX + $captionW - $wing * 0.45, $captionY + $captionH / 2], [$captionX + $captionW, $captionY + $captionH + 2], [$captionX, $captionY + $captionH + 2], [$captionX + $wing * 0.45, $captionY + $captionH / 2]], $color);
            } elseif (! $minimal) {
                $parts[] = $this->rect($captionX, $captionY, $captionW, $captionH, $pill ? $captionH / 2 : 3, $color);
            }
            $textX = $canvasW / 2;
            $textWidth = $captionW - $padding;
            if ($preset === 'scan-icon') {
                $iconSize = $captionH * 0.72;
                $iconX = $captionX + $captionH * 0.2;
                $iconY = $captionY + ($captionH - $iconSize) / 2;
                $parts[] = $this->rect($iconX, $iconY, $iconSize, $iconSize, $iconSize * 0.18, $textColor);
                foreach ([[0.18, 0.18], [0.58, 0.18], [0.18, 0.58], [0.58, 0.58]] as [$ix, $iy]) {
                    $parts[] = $this->rect($iconX + $iconSize * $ix, $iconY + $iconSize * $iy, $iconSize * 0.24, $iconSize * 0.24, 0, $color);
                }
                $textX += $captionH * 0.4;
                $textWidth -= $captionH;
            }
            $fontSize = min(32, max(11, $qrW * 0.075), $textWidth / max(1, mb_strlen($text) * 0.7));
            $family = match ($font) {
                'serif' => 'Georgia, serif',
                'mono' => 'Courier New, monospace',
                default => 'Arial, sans-serif',
            };
            $outline = '';
            $parts[] = sprintf('<text x="%s" y="%s" text-anchor="middle" fill="%s" font-size="%s" font-family="%s" font-weight="700"%s>%s</text>', $textX, $captionY + $captionH / 2 + $fontSize * 0.35, $textColor, $fontSize, $family, $outline, htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s">%s</svg>', $canvasW, $canvasH, $canvasW, $canvasH, implode('', $parts));
    }

    private function rect(float $x, float $y, float $width, float $height, float $radius, string $fill, string $stroke = 'none', float $strokeWidth = 0): string
    {
        return sprintf('<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="%s" stroke="%s" stroke-width="%s"/>', $x, $y, $width, $height, $radius, $fill, $stroke, $strokeWidth);
    }

    private function polygon(array $points, string $fill, string $stroke = 'none', float $strokeWidth = 0): string
    {
        $coordinates = implode(' ', array_map(fn (array $point) => implode(',', $point), $points));

        return sprintf('<polygon points="%s" fill="%s" stroke="%s" stroke-width="%s" stroke-linejoin="round"/>', $coordinates, $fill, $stroke, $strokeWidth);
    }

    private function color(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    }
}
