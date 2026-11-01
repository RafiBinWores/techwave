<?php

namespace App\Support\Seo;

final class ServerChecks
{
    public function inspect(HtmlPage $page): array
    {
        $r = fn (...$args) => Finding::rule(...$args);
        $make = fn ($id, $label, $checks, $details = [], $priority = 2) => Finding::make($id, 'Technical', $label, $checks, false, $details, $priority);
        $response = $page->response;
        $headers = $response->headers;
        $secure = parse_url($response->finalUrl, PHP_URL_SCHEME) === 'https';
        $css = $page->nodes($page->rel('stylesheet'))->length;
        $js = $page->nodes('//script[@src]')->length;
        $resources = [];
        $mixed = 0;
        foreach ($page->nodes('//img[@src]|//script[@src]|//link[@href]') as $node) {
            $value = $node->getAttribute($node->hasAttribute('src') ? 'src' : 'href');
            if ($node->nodeName === 'link' && ! preg_match('/\b(stylesheet|preload|icon)\b/i', $node->getAttribute('rel'))) {
                continue;
            }
            $url = $page->url($value, $page->base());
            if ($secure && $url !== null && str_starts_with($url, 'http:')) {
                $mixed++;
            }
            if (count($resources) < 2000) {
                $resources[] = ['type' => $node->nodeName, 'url' => $url ?? HtmlPage::clean($value)];
            }
        }
        $headerRows = [];
        foreach ($headers as $name => $value) {
            // Cookies and authentication headers never belong in a public SEO report.
            if (in_array(strtolower($name), ['set-cookie', 'cookie', 'authorization', 'proxy-authorization', 'www-authenticate'], true)) {
                continue;
            }
            $headerRows[] = ['header' => HtmlPage::clean($name), 'value' => HtmlPage::clean((string) $value)];
        }
        $encoding = strtolower($headers['content-encoding'] ?? '');
        $seconds = number_format($response->elapsedMilliseconds / 1000, 2);
        $kilobytes = number_format(strlen($response->body) / 1000, 0);
        $content = fn ($id, $label, $checks, $details = [], $priority = 2) => Finding::make($id, 'Content', $label, $checks, false, $details, $priority);

        return [
            $make('http-redirects', 'HTTP redirects', [
                $r('Direct destination', $response->redirects === [], 'The checked page does not redirect to another URL.', count($response->redirects).' redirects were followed before reaching this page.', 'Link directly to the preferred page URL.'),
                Finding::check('HTTP to HTTPS', 'unknown', 'The HTTP-to-HTTPS redirect has not been verified.'),
                Finding::check('Preferred host', 'unknown', 'The www/non-www redirect has not been verified.'),
            ], array_merge($response->redirects, [['url' => $response->finalUrl, 'status' => $response->status]]), 1),
            $make('http-headers', 'HTTP header', [
                $r('Technology disclosure', ! isset($headers['x-powered-by']) && ! isset($headers['x-powered']), 'No X-Powered HTTP header is sent.', 'An X-Powered HTTP header exposes server technology.', 'Remove unnecessary X-Powered and X-Powered-By response headers.'),
                $r('Compression', in_array($encoding, ['gzip', 'br', 'deflate', 'zstd'], true), 'This page uses compression for data transfer.', 'No recognized compression was observed for this page.', 'Enable HTML compression when appropriate.'),
            ], $headerRows, 2),
            $make('performance', 'Performance', [
                $r('Response time', $response->elapsedMilliseconds <= 400, 'The measured page response time of '.$seconds.' seconds is within the 0.4 seconds guideline.', 'The measured page response time of '.$seconds.' seconds exceeds the recommended limit of 0.4 seconds.', 'Review server response time, caching and hosting performance.'),
                $r('HTML document size', strlen($response->body) <= 512000, 'The file size of the HTML document is fine. ('.$kilobytes.' kB)', 'The HTML document is large. ('.$kilobytes.' kB; review guideline: 512 kB)', 'Reduce unnecessarily large HTML and inline data.'),
            ], [['fetch_ms' => $response->elapsedMilliseconds, 'decoded_html_bytes' => strlen($response->body)]], 3),
            $content('resource-counts', 'CSS and JavaScript resources', [
                $r('Stylesheet requests', $css <= 5, $css.' external stylesheet references.', $css.' stylesheet references; review request overhead.', 'Remove unused styles and reduce unnecessary stylesheet requests.'),
                $r('Script requests', $js <= 10, $js.' external script references.', $js.' script references; review request overhead.', 'Remove unused scripts and avoid unnecessary third-party requests.'),
                $r('Legacy plugins', $page->nodes('//object|//embed|//applet')->length === 0, 'No legacy plugin elements found.', 'Legacy plugin/embed elements were found.', 'Replace unsupported plugin content with accessible HTML when possible.'),
                Finding::check('Transfer sizes', 'unknown', 'Resource references are counted from HTML. Referenced files are not downloaded or executed.'),
            ], $resources, 3),
            $content('mixed-content', 'HTTPS resource references', [
                $r('Secure resource URLs', ! $secure || $mixed === 0, $secure ? 'No HTTP script, image or stylesheet references were observed.' : 'Mixed-content checks require an HTTPS page.', $mixed.' HTTP resource references on an HTTPS page.', 'Use HTTPS for scripts, styles and images.'),
                Finding::check('Rendered requests', 'unknown', 'Only HTML-declared resource URLs were inspected; CSS imports and JavaScript-generated requests are not executed.'),
            ], [], 1),
        ];
    }
}
