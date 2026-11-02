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
        $repeatedAnchors = 0;
        $anchors = [];
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
            if ($text !== '' && isset($anchors[$text])) {
                $repeatedAnchors++;
            }
            $anchors[$text] = $row['url'];
        }
        $dynamic = count(array_filter($internal, fn ($row) => $row['query']));
        $insecure = count(array_filter($external, fn ($row) => str_starts_with($row['url'], 'http:')));
        $tables = fn ($rows) => array_map(fn ($row) => ['url' => $row['url'], 'anchor' => $row['accessible_text'] ?: '(empty)', 'rel' => $row['rel'] ?: 'follow (default)'], $rows);

        return [
            Finding::make('links', 'Structure', 'Internal links', [
                $r('Link volume', count($internal) > 0 && count($internal) <= 150, count($internal).' internal links; link volume is within the review guideline.', count($internal).' internal links; review navigation and link volume.', 'Keep navigation focused and link to useful related pages.'),
                Finding::check('Inspection scope', 'unknown', 'Up to 2,000 web links are inspected; the first 50 rows are shown. Destination pages are not fetched to assert broken-link status.'),
            ], true, $tables($internal), 2),
            Finding::make('external-links', 'Structure', 'External links', [
                Finding::check('External destinations', 'pass', count($external).' external web links inspected.'),
                $r('Secure destinations', $insecure === 0, 'External links use HTTPS.', $insecure.' external links use HTTP.', 'Prefer secure external destinations when available.'),
                $r('Outbound volume', count($external) <= 100, 'External link volume is within the review guideline.', count($external).' external links; review relevance.', 'Keep external links relevant and useful to visitors.'),
                Finding::check('Paid links', 'unknown', 'nofollow, sponsored and ugc relationships are listed as declared. Paid-link context cannot be inferred automatically.'),
            ], true, $tables($external), 3),
            Finding::make('anchors', 'Structure', 'Anchor text', [
                $r('Repeated anchors', $repeatedAnchors === 0, 'No repeated internal anchor text was found.', $repeatedAnchors.' repeated internal anchor texts were found; repeated navigation may be intentional.', 'Review repeated anchor text and use descriptions appropriate to each destination.'),
                $r('Concise anchors', $long === 0, 'Anchor descriptions are concise.', $long.' internal anchors exceed 120 characters.', 'Keep anchor descriptions clear and concise.'),
                $r('Specific anchors', $generic === 0 && $empty === 0, 'Internal anchors have specific accessible descriptions.', $generic.' generic anchors and '.$empty.' links without accessible descriptions were found.', 'Replace generic or empty anchors with text describing the destination.'),
            ], true, [], 2),
            Finding::make('dynamic-links', 'Structure', 'Internal URL parameters', [
                $r('Parameter variants', $dynamic === 0, 'Internal links do not contain dynamic parameters or session identifiers.', $dynamic.' internal links contain parameters.', 'Review parameter variants, remove session identifiers and use canonical URLs where appropriate.'),
            ], true, [], 3),
        ];
    }
}
