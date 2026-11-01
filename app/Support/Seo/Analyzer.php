<?php

namespace App\Support\Seo;

class Analyzer
{
    public function analyze(FetchedPage $response): array
    {
        $page = new HtmlPage($response);
        $quality = new QualityChecks;
        $findings = array_merge(
            (new MetadataChecks)->inspect($page), $quality->inspect($page), $quality->headings($page),
            (new LinkChecks)->inspect($page), (new ServerChecks)->inspect($page),
            [Finding::make('backlinks', 'External factors', 'Backlinks', [Finding::check('Backlink index', 'unknown', 'Not connected. Connect a backlink data provider to retrieve backlink, referring-domain and referring-IP counts.')], true, [], 2, 0)],
        );

        $paragraphs = [];
        $paragraphCount = 0;
        foreach ($page->nodes('//p') as $paragraph) {
            $text = HtmlPage::clean($paragraph->textContent, 2000);
            if ($text === '') {
                continue;
            }
            $paragraphCount++;
            if (count($paragraphs) < 50) {
                $paragraphs[] = ['paragraph' => $paragraphCount, 'text' => $text];
            }
        }

        return ReportScores::summarize($findings) + [
            'version' => '3.0', 'url' => $response->finalUrl, 'checked_at' => now()->toIso8601String(),
            'elements' => ['paragraphs' => $paragraphs, 'paragraph_count' => $paragraphCount],
            'snippet' => ['title' => $page->text('//title'), 'description' => $page->meta('description')],
            'page' => [
                'title' => $page->text('//title'), 'description' => $page->meta('description'),
                'language' => $page->text('//html/@lang'), 'charset' => $page->text('//meta/@charset') ?: 'See character encoding checks',
                'indexable' => ! preg_match('/\b(noindex|none)\b/i', $page->meta('robots').' '.$page->meta('googlebot').' '.($response->headers['x-robots-tag'] ?? '')),
                'followable' => ! preg_match('/\b(nofollow|none)\b/i', $page->meta('robots').' '.$page->meta('googlebot').' '.($response->headers['x-robots-tag'] ?? '')),
                'response_ms' => $response->elapsedMilliseconds, 'html_bytes' => strlen($response->body), 'word_count' => $page->words(),
                'image_count' => $page->nodes('//img')->length, 'status' => $response->status,
            ],
            'findings' => $findings,
            'integrations' => [
                'backlinks' => ['status' => 'not_connected', 'metrics' => null],
                'screenshot' => ['status' => 'not_connected', 'image_url' => null],
            ],
        ];
    }
}
