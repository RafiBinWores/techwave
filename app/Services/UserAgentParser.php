<?php

namespace App\Services;

/**
 * Lightweight User-Agent parser used to infer the visitor's device type,
 * browser and operating system from a raw User-Agent header string.
 */
class UserAgentParser
{
    /**
     * Return a normalized device type: Mobile, Tablet, Laptop or Desktop.
     */
    public function device(string $userAgent): string
    {
        $agent = mb_strtolower($userAgent);

        $isTablet = str_contains($agent, 'ipad')
            || (str_contains($agent, 'tablet'))
            || (str_contains($agent, 'playbook'))
            || (str_contains($agent, 'silk'))
            || (! str_contains($agent, 'mobi') && preg_match('/android(?!.*mobile)/', $agent) === 1);

        if ($isTablet) {
            return 'Tablet';
        }

        $isMobile = str_contains($agent, 'mobi')
            || str_contains($agent, 'iphone')
            || str_contains($agent, 'ipod')
            || preg_match('/android.*mobile/', $agent) === 1
            || str_contains($agent, 'blackberry')
            || str_contains($agent, 'windows phone')
            || str_contains($agent, 'opera mini');

        if ($isMobile) {
            return 'Mobile';
        }

        // Laptops typically advertise touch capability alongside a
        // conventional desktop browser engine that is not a phone/tablet.
        $isLaptop = str_contains($agent, 'touch')
            || str_contains($agent, 'max_touch_points');

        return $isLaptop ? 'Laptop' : 'Desktop';
    }

    /**
     * Return the browser family, e.g. Chrome, Firefox, Safari, Edge, Opera.
     *
     * The optional $secChUa header (from client hints) is preferred because
     * Chromium-based browsers such as Brave intentionally hide their brand
     * in the User-Agent string but expose it in Sec-CH-UA.
     */
    public function browser(string $userAgent, ?string $secChUa = null): string
    {
        if ($secChUa !== null && $secChUa !== '') {
            $brand = $this->browserFromClientHints($secChUa);

            if ($brand !== null) {
                return $brand;
            }
        }

        $agent = $userAgent;

        if ($this->contains($agent, ['edg/'])) {
            return 'Edge';
        }

        if ($this->contains($agent, ['opr/', 'opera'])) {
            return 'Opera';
        }

        if ($this->contains($agent, ['brave'])) {
            return 'Brave';
        }

        if ($this->contains($agent, ['vivaldi'])) {
            return 'Vivaldi';
        }

        if ($this->contains($agent, ['chrome', 'crios'])) {
            return 'Chrome';
        }

        if ($this->contains($agent, ['firefox', 'fxios'])) {
            return 'Firefox';
        }

        if ($this->contains($agent, ['safari'])) {
            return 'Safari';
        }

        if ($this->contains($agent, ['samsungbrowser'])) {
            return 'Samsung Internet';
        }

        if ($this->contains($agent, ['ucbrowser'])) {
            return 'UC Browser';
        }

        if ($this->contains($agent, ['msie', 'trident'])) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Return a normalized operating system family.
     *
     * Windows versions (10 vs 11) are intentionally reported simply as
     * "Windows" because the User-Agent cannot reliably distinguish them.
     */
    public function operatingSystem(string $userAgent): string
    {
        $agent = mb_strtolower($userAgent);

        if (str_contains($agent, 'windows')) {
            return 'Windows';
        }

        if (str_contains($agent, 'iphone') || str_contains($agent, 'ipod')) {
            return 'iOS';
        }

        if (str_contains($agent, 'ipad')) {
            return 'iPadOS';
        }

        if (str_contains($agent, 'android')) {
            return 'Android';
        }

        if (str_contains($agent, 'mac os x') || str_contains($agent, 'macintosh')) {
            return 'macOS';
        }

        if (str_contains($agent, 'linux')) {
            return 'Linux';
        }

        if (str_contains($agent, 'cros')) {
            return 'Chrome OS';
        }

        if (str_contains($agent, 'x11')) {
            return 'Unix';
        }

        return 'Other';
    }

    private function contains(string $agent, array $needles): bool
    {
        $agent = mb_strtolower($agent);

        foreach ($needles as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the browser family from the Sec-CH-UA client-hint header
     * (e.g. '"Not/A)Brand";v="99", "Brave";v="127", "Chromium";v="127"').
     */
    private function browserFromClientHints(string $secChUa): ?string
    {
        $brands = array_map(
            fn (string $part) => trim($part, " \n\r\t\v\x00\"'"),
            explode(',', $secChUa)
        );

        $brands = array_filter($brands, fn (string $part) => $part !== '');

        foreach (['brave', 'edg', 'oper', 'opr', 'vivaldi', 'yabrowser', 'samsung'] as $needle) {
            foreach ($brands as $brand) {
                if (str_contains(mb_strtolower($brand), $needle)) {
                    return $this->normalizeBrand($brand);
                }
            }
        }

        return null;
    }

    private function normalizeBrand(string $match): string
    {
        $lower = mb_strtolower($match);

        return match (true) {
            str_contains($lower, 'brave') => 'Brave',
            str_contains($lower, 'edg') => 'Edge',
            str_contains($lower, 'oper'), str_contains($lower, 'opr') => 'Opera',
            str_contains($lower, 'vivaldi') => 'Vivaldi',
            str_contains($lower, 'yabrowser') => 'Yandex',
            str_contains($lower, 'samsung') => 'Samsung Internet',
            default => 'Chrome',
        };
    }
}
