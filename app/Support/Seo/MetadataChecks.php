<?php

namespace App\Support\Seo;

final class MetadataChecks
{
    public function inspect(HtmlPage $page): array
    {
        $r = fn (...$args) => Finding::rule(...$args);
        $make = fn ($id, $label, $checks, $details = [], $priority = 2) => Finding::make($id, 'Metadata', $label, $checks, false, $details, $priority);
        $title = $page->text('//title');
        $titleLength = mb_strlen($title);
        $description = $page->meta('description');
        $descriptionLength = mb_strlen($description);
        $canonical = $page->text($page->rel('canonical').'/@href');
        $canonicalUrl = $canonical !== '' ? $page->url($canonical, $page->base()) : null;
        $language = $page->text('//html/@lang');
        $directives = trim(strtolower($page->meta('robots').' '.$page->meta('googlebot').' '.($page->response->headers['x-robots-tag'] ?? '')));
        $hreflangRows = [];
        $invalidHreflang = 0;
        $duplicateHreflang = 0;
        $seenLanguages = [];
        foreach ($page->nodes('//link[@hreflang]') as $node) {
            $lang = strtolower(trim($node->getAttribute('hreflang')));
            $href = $page->url($node->getAttribute('href'), $page->base());
            if (! preg_match('/^(?:[a-z]{2,3}(?:-[a-z]{4})?(?:-[a-z]{2}|-[0-9]{3})?|x-default)$/i', $lang) || $href === null || ! preg_match('/\balternate\b/i', $node->getAttribute('rel'))) {
                $invalidHreflang++;
            }
            if (isset($seenLanguages[$lang])) {
                $duplicateHreflang++;
            }
            $seenLanguages[$lang] = true;
            $hreflangRows[] = ['language' => $lang, 'url' => $href ?? 'Invalid URL'];
        }
        $charset = $page->text('//meta/@charset');
        if ($charset === '' && preg_match('/charset\s*=\s*([\w-]+)/i', $page->response->headers['content-type'] ?? '', $matches)) {
            $charset = $matches[1];
        }
        if ($charset === '' && preg_match('/charset\s*=\s*([\w-]+)/i', $page->text('//meta[translate(@http-equiv,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="content-type"]/@content'), $matches)) {
            $charset = $matches[1];
        }
        $host = parse_url($page->response->finalUrl, PHP_URL_HOST) ?? '';
        $path = parse_url($page->response->finalUrl, PHP_URL_PATH) ?: '/';
        $socialRows = array_values(array_filter($page->metadata(), fn ($row) => str_starts_with(strtolower($row['name']), 'og:') || str_starts_with(strtolower($row['name']), 'twitter:')));
        $favicon = $page->text($page->rel('icon').'/@href');
        $htmlLangValid = $language !== '' && preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i', $language);

        return [
            $make('title', 'Title', [
                $r('Present', $title !== '', 'A page title is present.', 'No title was found.', 'Add a descriptive page title.', 'fail'),
                $r('Unique declaration', $page->nodes('//title')->length <= 1, 'Only one title element is declared.', 'Multiple title elements were found.', 'Keep one title element in the document head.'),
                $r('Title length', $titleLength >= 30 && $titleLength <= 60, $titleLength.' characters; within the preview guideline.', $titleLength.' characters; review the search preview length.', 'Use a clear title; 30–60 characters is a useful preview guideline.'),
                $r('Title placement', $page->nodes('//head/title')->length > 0, 'The title is in the document head.', 'No title was found in the document head.', 'Place the title in the document head.'),
            ], [['title' => $title, 'characters' => $titleLength]], 1),
            $make('description', 'Meta description', [
                $r('Present', $description !== '', 'A meta description is present.', 'The meta description is missing or empty.', 'Write a useful description of what this page offers.', 'fail'),
                $r('Length', $descriptionLength >= 70 && $descriptionLength <= 160, $descriptionLength.' characters; within the preview guideline.', $descriptionLength.' characters; review the snippet length.', 'Use a useful description; 70–160 characters is a preview guideline.'),
                $r('Unique declaration', $page->nodes('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]')->length <= 1, 'The description is declared at most once.', 'Multiple descriptions were found.', 'Keep one relevant meta description.'),
            ], [['description' => $description, 'characters' => $descriptionLength]], 1),
            $make('robots', 'Crawlability and indexing', [
                $r('Indexing', ! preg_match('/\b(noindex|none)\b/', $directives), 'No noindex directive was observed.', 'A noindex directive is present.', 'Remove noindex only if this page should be searchable.', 'fail'),
                $r('Link following', ! preg_match('/\b(nofollow|none)\b/', $directives), 'No page-wide nofollow directive was observed.', 'Page-wide nofollow is present.', 'Review whether search engines should follow this page’s links.'),
                Finding::check('Robots source', 'unknown', 'Observed directives: '.($directives ?: 'none').'. robots.txt access rules require the separate robots-file probe.'),
            ], [], 1),
            $make('canonical', 'Canonical link', [
                $r('Declaration', $canonical !== '', 'A canonical link is declared.', 'No canonical link is declared.', 'Declare the preferred canonical version of this page.'),
                $r('Valid URL', $canonicalUrl !== null, 'The canonical resolves to a valid web URL.', 'The canonical is missing or uses an invalid URL.', 'Use a valid HTTP or HTTPS canonical URL.'),
                $r('Single canonical', $page->nodes($page->rel('canonical'))->length <= 1, 'No conflicting canonical declarations were observed.', 'More than one canonical link is declared.', 'Use a single canonical declaration.'),
                $r('Preferred version', $canonicalUrl !== null && rtrim(explode('#', $canonicalUrl)[0], '/') === rtrim(explode('#', $page->response->finalUrl)[0], '/'), 'The canonical matches the inspected page.', 'The canonical points elsewhere or is missing; review whether this is intentional.', 'Confirm that the canonical points to the intended preferred page.'),
            ], [['canonical' => $canonicalUrl ?? $canonical]]),
            $make('language', 'Language', [
                $r('Declared', $language !== '', 'Document language: '.$language.'.', 'No document language is declared.', 'Set a correct html lang attribute.'),
                $r('Language syntax', (bool) $htmlLangValid, 'The language tag has a recognized structure.', 'The language tag is missing or malformed.', 'Use a valid language tag such as en or en-GB.'),
                Finding::check('Content language', 'unknown', 'The declared language is reported; automatic language detection is not inferred from short page text.'),
            ]),
            $make('hreflang', 'Alternate / hreflang links', [
                $r('Valid alternatives', $invalidHreflang === 0, count($hreflangRows).' alternate language links inspected; syntax and URLs are valid.', $invalidHreflang.' alternate language links have an invalid language, relationship or URL.', 'Use valid hreflang language tags, alternate relationships and web URLs.'),
                $r('Unique languages', $duplicateHreflang === 0, 'No duplicate hreflang language declarations.', $duplicateHreflang.' duplicate language declarations.', 'Choose one alternate URL per language and region.'),
                Finding::check('Reciprocal declarations', 'unknown', 'Reciprocal hreflang and translated content require inspecting the alternate pages; they are not assumed.'),
            ], $hreflangRows, 3),
            $make('social', 'Social networks and sharing metadata', [
                $r('Open Graph title', $page->meta('og:title', 'property') !== '', 'Open Graph title is present.', 'Open Graph title is missing.', 'Add og:title for shared link previews.'),
                $r('Open Graph description', $page->meta('og:description', 'property') !== '', 'Open Graph description is present.', 'Open Graph description is missing.', 'Add og:description for shared link previews.'),
                $r('Sharing image', $page->meta('og:image', 'property') !== '' && $page->url($page->meta('og:image', 'property'), $page->base()) !== null, 'A valid sharing-image URL is declared.', 'No valid Open Graph image URL was found.', 'Add a representative og:image URL.'),
                $r('Twitter / X card', $page->meta('twitter:card') !== '', 'A Twitter / X card type is declared.', 'No Twitter / X card type is declared.', 'Declare a Twitter card when this page is intended for social sharing.'),
            ], $socialRows, 3),
            $make('other-meta', 'Other meta tags', [
                $r('Meta refresh', $page->nodes('//meta[translate(@http-equiv,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="refresh"]')->length === 0, 'No meta-refresh redirect was found.', 'A meta-refresh redirect is declared.', 'Prefer a server-side redirect for permanently moved pages.'),
                $r('Legacy keywords', $page->meta('keywords') === '', 'No legacy keywords tag is used.', 'A legacy meta keywords tag is present; it is not a Google ranking signal.', 'Focus on useful page content rather than keyword meta tags.'),
                Finding::check('Metadata inventory', 'pass', count($page->metadata()).' meta declarations inventoried (up to 100 shown).'),
            ], $page->metadata(), 4),
            $make('domain', 'Domain', [
                $r('Domain length', strlen($host) <= 63, 'The hostname length is reasonable.', 'The hostname is long.', 'Prefer a clear, recognizable domain when choosing a new site address.'),
                $r('Domain readability', ! str_contains($host, 'xn--'), 'No punycode label was observed.', 'An internationalized/punycode domain label is present.', 'Check that your internationalized domain is recognizable to your audience.'),
                $r('Domain separators', substr_count($host, '-') <= 3, 'The domain uses few hyphens.', 'The domain contains many hyphens.', 'Review domain readability; hyphen count is a usability heuristic.'),
            ], [['domain' => $host]], 3),
            $make('url', 'Page URL', [
                $r('URL length', strlen($page->response->finalUrl) <= 115, 'The page URL is concise.', 'The page URL is long.', 'Prefer concise, descriptive page URLs.'),
                $r('Parameters', parse_url($page->response->finalUrl, PHP_URL_QUERY) === null, 'No query parameters in the page URL.', 'The page URL contains query parameters.', 'Review query variants and canonicalize duplicate content.'),
                $r('Session identifiers', ! preg_match('/(?:^|[?&])(phpsessid|sessionid|sid)=/i', $page->response->finalUrl), 'No common session identifier in the URL.', 'The URL includes a possible session identifier.', 'Keep session identifiers out of publicly indexable URLs.'),
                $r('Readable path', ! preg_match('/[_A-Z]/', $path), 'The path uses a consistent lowercase/hyphen style.', 'The path contains uppercase characters or underscores.', 'Use consistent readable URL paths; avoid changing established URLs without redirects.'),
            ], [['url' => $page->response->finalUrl]], 3),
            $make('charset', 'Character encoding', [
                $r('Declared encoding', $charset !== '', 'Declared encoding: '.$charset.'.', 'No character encoding was found in the response or HTML.', 'Declare UTF-8 using an HTTP Content-Type or meta charset.'),
                $r('UTF-8', in_array(strtolower($charset), ['utf-8', 'utf8'], true), 'UTF-8 encoding is declared.', 'UTF-8 is not declared.', 'Prefer UTF-8 for consistent multilingual text.'),
            ]),
            $make('doctype', 'Doctype', [
                $r('Declaration', $page->dom->doctype !== null, 'A document type is declared.', 'No document type was declared.', 'Start HTML documents with <!doctype html>.'),
                $r('HTML5', (bool) preg_match('/<!doctype\s+html\s*>/i', $page->response->body), 'An HTML5 doctype was found.', 'A modern HTML5 doctype was not found.', 'Use the HTML5 doctype for standards-mode rendering.'),
            ], [], 4),
            $make('favicon', 'Favicon', [
                $r('Icon declaration', $favicon !== '', 'A favicon link is declared.', 'No favicon link is declared in the HTML.', 'Add a favicon link to support recognition in browser tabs.'),
                $r('Icon URL', $favicon !== '' && ($page->url($favicon, $page->base()) !== null || preg_match('~^data:image/(?:png|gif|x-icon|vnd.microsoft.icon);base64,~i', $favicon)), 'The favicon uses a supported image/web URL.', 'No supported favicon URL was found.', 'Use a public icon URL; the icon file itself is not fetched in this check.'),
            ], [['icon' => $favicon]], 4),
        ];
    }
}
