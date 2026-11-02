<?php

namespace App\Support\Seo;

final class CrawlabilityChecks
{
    public function inspect(FetchedPage $response, ?FetchedPage $robots = null): array
    {
        $page = new HtmlPage($response);
        $directives = strtolower(trim($page->meta('robots').' '.$page->meta('googlebot').' '.$page->meta('bingbot').' '.($response->headers['x-robots-tag'] ?? '')));
        $blockedIndexing = (bool) preg_match('/\b(noindex|none)\b/', $directives);
        $blockedFollowing = (bool) preg_match('/\b(nofollow|none)\b/', $directives);
        $status = 'unknown';
        $messages = [$blockedIndexing ? 'A noindex directive prevents indexing.' : 'No noindex directive was observed.', $blockedFollowing ? 'A page-wide nofollow directive restricts link following.' : 'No page-wide nofollow directive was observed.'];
        $details = [['source' => 'Page directives', 'result' => $directives ?: 'No explicit directives; index and follow are the defaults.']];
        if ($robots === null) {
            $messages[] = 'robots.txt crawling restrictions could not be verified.';
        } elseif (in_array($robots->status, [404, 410], true)) {
            $status = 'pass';
            $messages[] = 'robots.txt returned HTTP '.$robots->status.'; no robots.txt crawling restrictions apply.';
        } elseif ($robots->status >= 500) {
            $status = 'fail';
            $messages[] = 'robots.txt returned HTTP '.$robots->status.'; crawlers can temporarily stop crawling while the file is unreachable.';
        } elseif ($robots->status >= 200 && $robots->status < 300 && ! preg_match('/<\s*(html|!doctype)/i', $robots->body)) {
            $status = 'pass';
            foreach (['Googlebot', 'Bingbot', 'TechWaveSEOChecker'] as $agent) {
                $result = (new RobotsRules)->evaluate($robots->body, $response->finalUrl, $agent);
                $details[] = ['source' => $agent.' robots.txt rules', 'result' => ($result['allowed'] ? 'Allowed' : 'Blocked').($result['rule'] !== null ? ' — '.$result['rule'] : ' — no matching restriction')];
                if (! $result['allowed']) {
                    $status = 'fail';
                    $messages[] = 'robots.txt blocks '.$agent.' from crawling the checked URL ('.$result['rule'].').';
                }
            }
            if ($status === 'pass') {
                $messages[] = 'robots.txt allows Googlebot, Bingbot and general crawlers to access the checked URL.';
            }
        } else {
            $messages[] = 'robots.txt returned HTTP '.$robots->status.' or an HTML response; crawling restrictions remain unverified.';
        }
        if ($blockedIndexing || $response->status < 200 || $response->status >= 300) {
            $status = 'fail';
        } elseif ($blockedFollowing && $status === 'pass') {
            $status = 'warning';
        }

        return Finding::make('robots', 'Metadata', 'Crawlability', [Finding::check('Page crawlability', $status, implode(' ', $messages), in_array($status, ['fail', 'warning'], true) ? 'If this page should appear in search, allow its URL in robots.txt, resolve robots.txt server errors and remove restrictive indexing directives.' : '')], false, $details, 1);
    }
}
