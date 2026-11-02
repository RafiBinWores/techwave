<?php

namespace App\Support\Seo;

final class QualityChecks
{
    public function inspect(HtmlPage $page): array
    {
        $r = fn (...$args) => Finding::rule(...$args);
        $make = fn ($id, $label, $checks, $details = [], $priority = 2) => Finding::make($id, 'Content', $label, $checks, false, $details, $priority);
        $visible = $page->visibleText();
        $words = $page->words();
        $ratio = strlen($page->response->body) ? round(100 * strlen($visible) / strlen($page->response->body), 1) : 0;
        $paragraphs = [];
        $repeated = 0;
        foreach ($page->nodes('//p') as $paragraph) {
            $text = mb_strtolower(HtmlPage::clean($paragraph->textContent, 10000));
            if (mb_strlen($text) < 40) {
                continue;
            }
            $hash = hash('sha256', $text);
            if (isset($paragraphs[$hash])) {
                $repeated++;
            }
            $paragraphs[$hash] = true;
        }
        $emphasized = [];
        $longEmphasis = 0;
        $emphasisLength = 0;
        foreach ($page->nodes('//strong|//b') as $node) {
            $text = HtmlPage::clean($node->textContent, 10000);
            $emphasisLength += mb_strlen($text);
            if (mb_strlen($text) > 80) {
                $longEmphasis++;
            }
            $emphasized[] = ['text' => mb_substr($text, 0, 300), 'characters' => mb_strlen($text)];
        }
        $images = [];
        $missingAlt = 0;
        $longAlt = 0;
        $missingDimensions = 0;
        $missingSource = 0;
        foreach ($page->nodes('//img') as $node) {
            $alt = $node->getAttribute('alt');
            $decorative = strtolower($node->getAttribute('role')) === 'presentation' || strtolower($node->getAttribute('role')) === 'none' || strtolower($node->getAttribute('aria-hidden')) === 'true';
            if (! $node->hasAttribute('alt') || (trim($alt) === '' && ! $decorative)) {
                $missingAlt++;
            }
            if (mb_strlen($alt) > 150) {
                $longAlt++;
            }
            if (! $node->getAttribute('width') || ! $node->getAttribute('height')) {
                $missingDimensions++;
            }
            if (trim($node->getAttribute('src')) === '' && trim($node->getAttribute('srcset')) === '') {
                $missingSource++;
            }
            $images[] = ['image' => $page->url($node->getAttribute('src'), $page->base()) ?? HtmlPage::clean($node->getAttribute('src')), 'alt' => $node->hasAttribute('alt') ? HtmlPage::clean($alt) : '(missing)', 'width' => $node->getAttribute('width'), 'height' => $node->getAttribute('height'), 'loading' => $node->getAttribute('loading') ?: 'default'];
        }
        $viewport = $page->meta('viewport');
        $jsonBlocks = 0;
        $invalidJson = 0;
        $typedJson = 0;
        $schemaRows = [];
        foreach ($page->nodes('//script[translate(@type,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="application/ld+json"]') as $node) {
            $jsonBlocks++;
            $json = json_decode($node->textContent, true, 64);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
                $invalidJson++;

                continue;
            }
            $items = array_is_list($json) ? $json : (isset($json['@graph']) && is_array($json['@graph']) ? $json['@graph'] : [$json]);
            foreach ($items as $item) {
                if (! is_array($item) || empty($item['@type'])) {
                    continue;
                }
                $type = is_array($item['@type']) ? implode(', ', array_filter($item['@type'], 'is_string')) : (is_string($item['@type']) ? $item['@type'] : '');
                if (trim($type) === '') {
                    continue;
                }
                $typedJson++;
                $schemaRows[] = ['format' => 'JSON-LD', 'type' => HtmlPage::clean($type), 'name' => is_string($item['name'] ?? null) ? HtmlPage::clean($item['name']) : ''];
                if (count($schemaRows) >= 50) {
                    break;
                }
            }
        }
        $microdata = $page->nodes('//*[@itemscope]')->length;
        foreach ($page->nodes('//*[@itemscope]') as $node) {
            if (count($schemaRows) < 50) {
                $schemaRows[] = ['format' => 'Microdata', 'type' => HtmlPage::clean($node->getAttribute('itemtype')), 'name' => ''];
            }
        }
        $profiles = [];
        foreach ($page->links() as $link) {
            if (preg_match('~(?:^|\.)(facebook\.com|instagram\.com|linkedin\.com|twitter\.com|x\.com|youtube\.com|github\.com)$~i', parse_url($link['url'], PHP_URL_HOST) ?? '')) {
                $profiles[] = ['network' => parse_url($link['url'], PHP_URL_HOST), 'url' => $link['url']];
            }
        }

        return [
            $make('content-length', 'Content', [
                $r('Useful text', $words >= 300, $words.' words in server-rendered body text.', $words.' words in server-rendered body text; review whether the page fully answers its purpose.', 'Make the page useful and complete. The 300-word threshold is a review guideline, not a ranking rule.'),
                $r('Text / HTML ratio', $ratio >= 10, $ratio.'% text-to-HTML ratio.', $ratio.'% text-to-HTML ratio; markup dominates the response.', 'Review excessive markup or inline data; text ratio alone is not a ranking factor.'),
                $r('Paragraphs', $page->nodes('//p')->length > 0, 'The page contains paragraph text.', 'No paragraph elements were found.', 'Organize readable content into clear paragraphs.'),
            ], [['words' => $words, 'text_html_ratio' => $ratio.'%']], 1),
            $make('duplicate-content', 'Repeated content', [
                $r('Repeated paragraphs', $repeated === 0, 'No identical substantial paragraphs were repeated on this page.', $repeated.' repeated substantial paragraph occurrences.', 'Remove unnecessary repeated paragraphs within this page.'),
                Finding::check('Cross-page duplication', 'unknown', 'Duplicate content across other pages or websites cannot be determined from a single-page fetch.'),
            ]),
            $make('frames', 'Frames and embedded content', [
                $r('Legacy frames', $page->nodes('//frame|//frameset')->length === 0, 'No legacy framesets are used.', 'Legacy frames or framesets are present.', 'Replace legacy framesets with accessible HTML page content.', 'fail'),
                $r('Embedded frames', $page->nodes('//iframe')->length === 0, 'No iframe-dependent content found.', $page->nodes('//iframe')->length.' iframes found; inspect whether important content exists outside the embeds.', 'Keep important indexable content in the main document; review embedded content accessibility.'),
            ]),
            $make('viewport', 'Mobile optimization', [
                $r('Device viewport', (bool) preg_match('/width\s*=\s*device-width/i', $viewport), 'The viewport adapts to device width.', 'A device-width viewport is missing.', 'Set width=device-width in the viewport declaration.'),
                $r('Apple touch icon', $page->text($page->rel('apple-touch-icon').'/@href') !== '', 'An Apple touch icon is specified.', 'No Apple touch icon is specified.', 'Declare an apple-touch-icon for saved mobile shortcuts.'),
                Finding::check('Rendered mobile layout', 'unknown', 'Viewport markup was inspected. Touch targets and rendered overflow need a browser/mobile rendering test.'),
            ]),
            $make('strong-bold', 'Strong and bold tags', [
                $r('Emphasis length', $longEmphasis === 0, 'No unusually long bold text blocks were found.', $longEmphasis.' emphasized text blocks exceed 80 characters.', 'Use emphasis for short important phrases rather than entire paragraphs.'),
                $r('Emphasis density', $emphasisLength <= max(80, mb_strlen($visible) * 0.35), 'Bold text does not dominate the visible content.', 'A large share of the visible text is emphasized.', 'Reduce excessive bold text so important information stands out.'),
            ], $emphasized, 4),
            $make('image-alt', 'Image SEO', [
                $r('Image alternatives', $missingAlt === 0 && $longAlt === 0 && $missingSource === 0, 'All '.$page->nodes('//img')->length.' images have suitable alternative attributes and sources.', $missingAlt.' images have missing or empty alternatives; '.$longAlt.' have unusually long alternatives and '.$missingSource.' have no source. Empty alternatives should be reviewed to confirm the images are decorative.', 'Add useful alt text to meaningful images and valid image sources; explicitly mark decorative images when appropriate.'),
            ], $images, 3),
            $make('image-layout', 'Image dimensions and layout', [
                $r('Reserved dimensions', $missingDimensions === 0, 'Images declare width and height attributes.', $missingDimensions.' images lack explicit width or height attributes.', 'Reserve image space using dimensions or CSS aspect-ratio to reduce layout movement.'),
                Finding::check('Image delivery', 'unknown', 'Image URLs and attributes were inspected; files were not downloaded to measure format, compression or rendered layout shift.'),
            ], [], 3),
            $make('social-profiles', 'Social network links', [
                $r('Profile links', count($profiles) > 0, count($profiles).' recognized social network links found.', 'No recognized social network links found.', 'Link relevant official social profiles when useful to visitors.'),
                Finding::check('Shares and engagement', 'unknown', 'Social share and follower counts require external platform data; no counts are inferred.'),
            ], $profiles, 4),
            $make('structured-data', 'Additional markup / structured data', [
                $r('Structured markup', $jsonBlocks > 0 || $microdata > 0, $jsonBlocks.' JSON-LD blocks and '.$microdata.' microdata scopes found.', 'No JSON-LD or microdata markup found.', 'Add appropriate structured data when supported by the page’s visible content.'),
                $r('JSON syntax', $invalidJson === 0, 'All discovered JSON-LD blocks have valid JSON syntax.', $invalidJson.' JSON-LD blocks contain invalid JSON or exceed parser depth.', 'Fix malformed JSON-LD before testing structured data.', 'fail'),
                $r('Typed entities', $typedJson > 0 || $microdata > 0 || $jsonBlocks === 0, $typedJson.' typed JSON-LD entities identified.', 'JSON-LD was found without recognizable @type entities.', 'Declare the intended schema types and required properties.'),
                Finding::check('Rich-result eligibility', 'unknown', 'Syntax and declared types are reported; full schema compliance and search-engine rich-result eligibility are not guaranteed.'),
            ], $schemaRows, 3),
            $make('https', 'HTTPS', [
                $r('HTTPS page', parse_url($page->response->finalUrl, PHP_URL_SCHEME) === 'https', 'The final page is served over HTTPS.', 'The final page uses unencrypted HTTP.', 'Serve the preferred page over HTTPS with a valid certificate.', 'fail'),
                Finding::check('Certificate verification', parse_url($page->response->finalUrl, PHP_URL_SCHEME) === 'https' ? 'pass' : 'unknown', parse_url($page->response->finalUrl, PHP_URL_SCHEME) === 'https' ? 'The fetch completed with TLS certificate verification enabled.' : 'TLS is not applicable to an HTTP response.'),
            ], [], 1),
        ];
    }

    public function headings(HtmlPage $page): array
    {
        $r = fn (...$args) => Finding::rule(...$args);
        $rows = $page->headings();
        $h1s = array_values(array_filter($rows, fn ($row) => $row['level'] === 'H1'));
        $empty = 0;
        foreach ($rows as $row) {
            if ($row['text'] === '') {
                $empty++;
            }
        }

        return [
            Finding::make('h1', 'Others', 'H1 heading', [
                $r('Main heading exists', count($h1s) > 0, 'A main H1 heading is present.', 'No H1 heading was found.', 'Add a clear main heading describing the page.', 'fail'),
                $r('Single H1', count($h1s) <= 1, 'At most one H1 heading is used.', count($h1s).' H1 headings found.', 'Use a clear main heading and organize subtopics with lower levels.'),
                $r('H1 text', count($h1s) > 0 && $h1s[0]['text'] !== '' && mb_strlen($h1s[0]['text']) <= 100, 'The first H1 has concise text.', 'The first H1 is missing, empty or unusually long.', 'Use a concise, descriptive H1.'),
            ], false, $h1s, 1),
            Finding::make('headings', 'Others', 'Headings', [
                $r('Nonempty headings', $empty === 0, 'No empty headings were found.', $empty.' headings are empty.', 'Remove empty headings or provide meaningful text.'),
                $r('Heading density', count($rows) <= max(1, (int) floor($page->words() / 100)), count($rows).' headings are proportionate to the page text.', count($rows).' headings for '.$page->words().' words; the heading count is high relative to the text.', 'Use headings for substantive sections; review the guideline of approximately 100 words per heading.'),
            ], false, $rows, 2),
        ];
    }
}
