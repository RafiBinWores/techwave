<?php

namespace App\Support\QrCode;

class LogoPresets
{
    public static function all(): array
    {
        $brackets = '<path d="M20 12H12V20M44 12H52V20M12 44V52H20M52 44V52H44" fill="none" stroke="#111827" stroke-width="3"/>';
        $textStyle = 'text-anchor="middle" fill="#111827" font-family="Arial, sans-serif" font-weight="700"';

        return [
            'link' => self::icon('Link', '#a033ff', '<g fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round"><path d="M28 23l5-5a9 9 0 0 1 13 13l-7 7a9 9 0 0 1-12 0M36 41l-5 5a9 9 0 0 1-13-13l7-7a9 9 0 0 1 12 0M25 39l14-14"/></g>'),
            'location' => self::icon('Location', '#f44336', '<ellipse cx="32" cy="45" rx="16" ry="5" fill="none" stroke="#fff" stroke-width="2"/><path d="M32 12c-7 0-12 5-12 12 0 8 12 23 12 23s12-15 12-23c0-7-5-12-12-12z" fill="#fff"/><circle cx="32" cy="24" r="4" fill="#f44336"/>'),
            'email' => self::icon('Email', '#ffc107', '<rect x="14" y="19" width="36" height="27" rx="4" fill="#fff"/><path d="M15 22l17 13 17-13M15 44l12-11M49 44L37 33" fill="none" stroke="#c28b00" stroke-width="2.5"/>'),
            'whatsapp' => self::icon('Phone', '#4caf50', '<path fill="#fff" d="M32 12a20 20 0 0 0-17 30l-4 12 13-4a20 20 0 1 0 8-38zm11 30c-1 2-4 3-6 2-9-2-15-9-17-17 0-2 1-5 3-6 1-1 2 0 3 1l2 5c0 1 0 2-1 3l-1 1c2 4 5 7 9 9l2-2c1-1 2-1 3 0l4 2c1 0 1 1-1 2z"/>'),
            'wifi' => self::icon('Wi-Fi', '#1294ff', '<g fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"><path d="M13 26a29 29 0 0 1 38 0M19 33a20 20 0 0 1 26 0M25 40a11 11 0 0 1 14 0"/></g><circle cx="32" cy="47" r="4" fill="#fff"/>'),
            'contact' => self::icon('Contact', '#0865ac', '<rect x="14" y="20" width="36" height="28" rx="4" fill="#fff"/><rect x="27" y="13" width="10" height="13" rx="3" fill="#fff" stroke="#0865ac" stroke-width="2"/><circle cx="32" cy="18" r="2" fill="#0865ac"/><circle cx="24" cy="32" r="4" fill="#0865ac"/><path d="M18 43v-3c0-5 12-5 12 0v3z" fill="#0865ac"/><path d="M35 31h10M35 36h10M35 41h7" stroke="#0865ac" stroke-width="2"/>'),
            'scan' => self::scan('Scan me', $brackets.'<text x="32" y="28" font-size="10" '.$textStyle.'>SCAN</text><text x="32" y="46" font-size="23" '.$textStyle.'>ME</text>'),
            'scan-text' => self::scan('Scan text', '<text x="32" y="25" font-size="14" '.$textStyle.'>SCAN</text><text x="32" y="50" font-size="31" '.$textStyle.'>ME</text>'),
            'scan-qr' => self::scan('QR scanner', $brackets.'<path d="M19 18h11v11H19zM35 18h11v11H35zM19 34h11v11H19zM35 34h5v5h-5zM41 40h5v5h-5z" fill="#111827"/><path d="M22 21h5v5h-5zM38 21h5v5h-5zM22 37h5v5h-5z" fill="#fff"/><path d="M14 32h36" stroke="#111827" stroke-width="2"/>'),
            'menu' => self::scan('Menu', $brackets.'<text x="32" y="36" font-size="10" '.$textStyle.'>MENU</text><path d="M23 23h18M23 43h18" stroke="#111827" stroke-width="2"/>'),
            'scan-frame' => self::scan('Scan frame', '<path d="M18 17v30M46 17v30M28 23h-5v5M36 23h5v5M23 36v5h5M41 36v5h-5M21 32h22" fill="none" stroke="#111827" stroke-width="3"/>'),
        ];
    }

    private static function icon(string $label, string $color, string $content): array
    {
        return ['label' => $label, 'group' => 'icon', 'svg' => self::svg('<circle cx="32" cy="32" r="29" fill="'.$color.'"/>'.$content)];
    }

    private static function scan(string $label, string $content): array
    {
        return ['label' => $label, 'group' => 'scan', 'svg' => self::svg($content)];
    }

    private static function svg(string $content): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'.$content.'</svg>';
    }
}
