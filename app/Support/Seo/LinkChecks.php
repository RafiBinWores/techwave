<?php

namespace App\Support\Seo;

final class LinkChecks
{
    public function inspect(HtmlPage $page): array
    {
        $r = fn (...$args) => Finding::rule(...$args);
        $links = $page->links();
        $internal = array_values(array_filter($links, fn ($row) => $row['internal']));
        $external = array_values(array_filter($links, fn ($row) => ! $row['internal']));
        $empty = 0;
        $long = 0;
        $generic = 0;
        $sameAnchorDifferent = 0;
        $duplicateDestinations = 0;
        $anchors = [];
        $destinations = [];
        foreach ($internal as $row) {
            $text = mb_strtolower($row['accessible_text']);
            if ($text === '') {
                $empty++;
            }
            if (mb_strlen($text) > 120) {
                $long++;
            }
            if (in_array($text, ['click here', 'here', 'read more', 'learn more', 'more'], true)) {
                $generic++;
            }
            if ($text !== '' && isset($anchors[$text]) && $anchors[$text] !== $row['url']) {
                $sameAnchorDifferent++;
            }
            $anchors[$text] = $row['url'];
            if (isset($destinations[$row['url']])) {
                $duplicateDestinations++;
            }
            $destinations[$row['url']] = true;
        }
        $dynamic = count(array_filter($internal, fn ($row) => $row['query']));
        $nofollow = count(array_filter($internal, fn ($row) => preg_match('/\bnofollow\b/', $row['rel'])));
        $insecure = count(array_filter($external, fn ($row) => str_starts_with($row['url'], 'http:')));
        $tables = fn ($rows) => array_map(fn ($row) => ['url' => $row['url'], 'anchor' => $row['accessible_text'] ?: '(empty)', 'rel' => $row['rel'] ?: 'follow (default)'], $rows);

        return [
            Finding::make('links', 'Structure', 'Internal links', [
                $r('Discoverable links', count($internal) > 0, count($internal).' internal web links inspected.', 'No internal web links found.', 'Add useful internal links to related pages.'),
                $r('Link volume', count($internal) <= 150, 'Internal link volume is within the review guideline.', count($internal).' internal links; review whether all are useful.', 'Keep navigation focused and remove unnecessary repetitive links.'),
                $r('Followable navigation', $nofollow === 0, 'No nofollow internal links found.', $nofollow.' internal links carry nofollow.', 'Review whether internal navigation links should be followable.'),
                Finding::check('Repeated destinations', 'pass', $duplicateDestinations.' repeated internal destinations; repeated navigation can be intentional.'),
                Finding::check('Inspection scope', 'unknown', 'Up to 2,000 web links are inspected; the first 50 rows are shown. Destination pages are not fetched to assert broken-link status.'),
            ], true, $tables($internal), 1),
            Finding::make('external-links', 'Structure', 'External links', [
                Finding::check('External destinations', 'pass', count($external).' external web links inspected.'),
                $r('Secure destinations', $insecure === 0, 'External links use HTTPS.', $insecure.' external links use HTTP.', 'Prefer secure external destinations when available.'),
                $r('Outbound volume', count($external) <= 100, 'External link volume is within the review guideline.', count($external).' external links; review relevance.', 'Keep external links relevant and useful to visitors.'),
                Finding::check('Paid links', 'unknown', 'nofollow, sponsored and ugc relationships are listed as declared. Paid-link context cannot be inferred automatically.'),
            ], true, $tables($external), 3),
            Finding::make('anchors', 'Structure', 'Anchor text', [
                $r('Accessible descriptions', $empty === 0, 'Internal links have accessible descriptions.', $empty.' internal links lack accessible descriptions.', 'Give links descriptive anchor text, image alt text or aria-labels.'),
                $r('Distinct destinations', $sameAnchorDifferent === 0, 'No identical anchors point to different internal URLs.', $sameAnchorDifferent.' identical anchor texts point to different internal URLs.', 'Use distinct anchor text where destinations serve different purposes.'),
                $r('Concise anchors', $long === 0, 'Anchor descriptions are concise.', $long.' internal anchors exceed 120 characters.', 'Keep anchor descriptions clear and concise.'),
                $r('Specific anchors', $generic === 0, 'No generic click-here/read-more internal anchors found.', $generic.' generic internal anchors found.', 'Replace generic anchors with text describing the destination.'),
            ], true, [], 2),
            Finding::make('dynamic-links', 'Structure', 'Internal URL parameters', [
                $r('Parameter variants', $dynamic === 0, 'Internal links do not contain query parameters.', $dynamic.' internal links contain parameters.', 'Review parameter variants and use canonical URLs where appropriate.'),
                $r('Session identifiers', count(array_filter($internal, fn ($row) => preg_match('/[?&](?:sid|sessionid|phpsessid)=/i', $row['url']))) === 0, 'No common session identifiers in internal links.', 'Possible session identifiers occur in internal URLs.', 'Keep session identifiers out of crawlable links.'),
            ], true, [], 3),
        ];
    }
}
