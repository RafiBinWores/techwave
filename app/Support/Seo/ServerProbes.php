<?php

namespace App\Support\Seo;

use DOMDocument;
use GuzzleHttp\Psr7\Uri;

final class ServerProbes
{
    public function __construct(private SafeFetcher $fetcher) {}

    public function inspect(FetchedPage $page, float $deadline): array
    {
        $uri = new Uri($page->finalUrl);
        $origin = (string) $uri->withPath('')->withQuery('')->withFragment('');
        $findings = [];
        $robotsResponse = null;
        foreach (['robots-file' => ['/robots.txt', 'Robots.txt'], 'sitemap-file' => ['/sitemap.xml', 'Sitemap']] as $id => [$path, $label]) {
            $check = Finding::check('Availability', 'unknown', 'Availability could not be confirmed within the probe budget.');
            try {
                $response = $this->probe($origin.$path, $deadline);
                if ($id === 'robots-file') {
                    $robotsResponse = $response;
                }
                if ($response->status === 200 && ($id === 'robots-file' ? $this->robots($response->body) : $this->sitemap($response->body))) {
                    $check = Finding::check('Availability', 'pass', $path.' returned a recognizable file. File availability does not prove full crawlability or coverage.');
                } elseif ($response->status === 404) {
                    $check = Finding::check('Availability', 'warning', $path.' returned HTTP 404.', 'Publish this file if appropriate, or declare a sitemap location in robots.txt.');
                }
            } catch (SeoException) {
            }
            $findings[] = Finding::make($id, 'Metadata', $label, [$check], false, [], 2, 0);
        }
        $findings[] = (new CrawlabilityChecks)->inspect($page, $robotsResponse);
        $redirectChecks = [Finding::rule('Direct destination', $page->redirects === [], 'The checked page does not redirect to another URL.', count($page->redirects).' redirects were followed before reaching this page.', 'Link directly to the preferred page URL.')];
        $preferred = $uri->withScheme('https')->withPort(null)->withFragment('');
        $httpUrl = (string) $uri->withScheme('http')->withPort(null)->withFragment('');
        try {
            $response = $this->probe($httpUrl, $deadline, (int) config('seo.html_bytes', 2097152));
            $redirectChecks[] = $this->redirectCheck($response, (string) $preferred, 'HTTP to HTTPS', 'The HTTP version redirects to the preferred HTTPS page.', 'The redirect to HTTPS is not configured correctly for this page.');
        } catch (SeoException) {
            $redirectChecks[] = Finding::check('HTTP to HTTPS', 'unknown', 'The HTTP-version redirect could not be verified.');
        }
        if (! filter_var(trim($uri->getHost(), '[]'), FILTER_VALIDATE_IP)) {
            $alternate = str_starts_with(strtolower($uri->getHost()), 'www.') ? substr($uri->getHost(), 4) : 'www.'.$uri->getHost();
            try {
                $response = $this->probe((string) $uri->withHost($alternate)->withFragment(''), $deadline, (int) config('seo.html_bytes', 2097152));
                $redirectChecks[] = $this->redirectCheck($response, (string) $preferred, 'Preferred host', 'The alternate www/non-www host redirects to the preferred page.', 'Both www and non-www hosts respond without a correct redirect to the preferred page.');
            } catch (SeoException) {
                $redirectChecks[] = Finding::check('Preferred host', 'unknown', 'The alternate www/non-www host could not be checked; this does not prove a misconfiguration.');
            }
        }
        $findings[] = Finding::make('http-redirects', 'Technical', 'HTTP redirects', $redirectChecks, false, array_merge($page->redirects, [['url' => $page->finalUrl, 'status' => $page->status]]), 1);

        return $findings;
    }

    private function probe(string $url, float $deadline, ?int $maxBytes = null): FetchedPage
    {
        return $this->fetcher->fetch($url, $maxBytes ?? (int) config('seo.auxiliary_bytes', 262144), min($deadline, microtime(true) + (float) config('seo.probe_seconds', 2)), false);
    }

    private function redirectCheck(FetchedPage $response, string $preferred, string $label, string $good, string $bad): array
    {
        if ($response->status < 200 || $response->status >= 300) {
            return Finding::check($label, 'unknown', 'The alternate URL did not return a successful page; its redirect configuration could not be verified.');
        }
        $actual = (new Uri($response->finalUrl))->withFragment('');
        $expected = new Uri($preferred);
        $matches = (string) $actual->withPath(rtrim($actual->getPath(), '/'))->withPort(null)
            === (string) $expected->withPath(rtrim($expected->getPath(), '/'))->withPort(null);

        return Finding::rule($label, $response->redirects !== [] && $matches, $good, $bad, 'Redirect the alternate URL to the same path and query on the preferred HTTPS host.', 'fail');
    }

    private function robots(string $body): bool
    {
        return ! preg_match('/<\s*(html|!doctype)/i', $body) && (bool) preg_match('/^\s*(user-agent|sitemap)\s*:/im', $body);
    }

    private function sitemap(string $body): bool
    {
        if (trim($body) === '' || preg_match('/<!DOCTYPE|<!ENTITY/i', $body)) {
            return false;
        }
        $xml = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            return $xml->loadXML($body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING) && in_array($xml->documentElement?->localName, ['urlset', 'sitemapindex'], true);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
