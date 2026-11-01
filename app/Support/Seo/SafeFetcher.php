<?php

namespace App\Support\Seo;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class SafeFetcher
{
    public function __construct(private PublicUrl $urls, private Transport $transport) {}

    public function fetch(string $url, int $maxBytes, float $deadline, bool $htmlOnly = true, ?\Closure $beforeConnect = null, array $headers = []): FetchedPage
    {
        $started = microtime(true);
        $redirects = [];
        $seen = [];
        while (true) {
            if (microtime(true) >= $deadline) {
                throw new SeoException('This check exceeded the time limit. Try again later.');
            }
            $target = $this->urls->resolve($url, $deadline);
            if (isset($seen[$target['url']])) {
                throw new SeoException('This website has a redirect loop.');
            }
            $seen[$target['url']] = true;
            if ($beforeConnect !== null) {
                $beforeConnect();
                $beforeConnect = null;
            }
            $response = $headers === [] ? $this->transport->get($target, $maxBytes, $deadline) : $this->transport->get($target, $maxBytes, $deadline, $headers);
            if (strlen($response['body']) > $maxBytes) {
                throw new SeoException('This page is too large to analyze.');
            }
            if (in_array($response['status'], [301, 302, 303, 307, 308], true)) {
                if ($headers !== []) {
                    throw new SeoException('Provider redirects are not allowed. Configure the final API endpoint.');
                }
                if (count($redirects) >= config('seo.max_redirects', 5) || empty($response['headers']['location'])) {
                    throw new SeoException('This website has too many or invalid redirects.');
                }
                try {
                    $next = (string) UriResolver::resolve(new Uri($target['url']), new Uri($response['headers']['location']));
                } catch (\Throwable) {
                    throw new SeoException('This website returned an invalid redirect.');
                }
                $redirects[] = ['url' => $target['url'], 'status' => $response['status']];
                $url = $next;

                continue;
            }
            if ($htmlOnly && ! preg_match('~^(text/html|application/xhtml\+xml)(;|$)~i', $response['headers']['content-type'] ?? '')) {
                throw new SeoException('This URL did not return an HTML page.');
            }
            if ($htmlOnly && ($response['status'] < 200 || $response['status'] >= 300)) {
                throw new SeoException('The website returned an error (HTTP '.$response['status'].').');
            }

            return new FetchedPage($target['url'], $response['body'], $response['status'], $response['headers'], $redirects, (int) round((microtime(true) - $started) * 1000));
        }
    }
}
