<?php

namespace App\Support\Seo;

final class ReportGroups
{
    public function build(array $findings): array
    {
        $definitions = [
            'robots' => ['Metadata', 'Crawlability', ['robots']],
            'charset' => ['Metadata', 'Charset encoding', ['charset']],
            'content-length' => ['Content', 'Content', ['content-length', 'duplicate-content', 'resource-counts']],
            'frames' => ['Content', 'Frames', ['frames']],
            'image-alt' => ['Content', 'Image SEO', ['image-alt']],
            'social' => ['Content', 'Social media', ['social', 'social-profiles']],
            'structured-data' => ['Content', 'Additional markup', ['structured-data']],
            'https' => ['Content', 'HTTPS', ['https', 'mixed-content']],
            'links' => ['Structure', 'Internal links', ['links', 'anchors', 'dynamic-links']],
        ];
        $indexed = collect($findings)->keyBy('id');
        $consumed = [];
        $groups = [];
        foreach ($findings as $finding) {
            $id = $finding['id'];
            if (isset($consumed[$id])) {
                continue;
            }
            $definitionId = $id;
            foreach ($definitions as $primary => $definition) {
                if (in_array($id, $definition[2], true)) {
                    $definitionId = $primary;
                    break;
                }
            }
            [$category, $label, $ids] = $definitions[$definitionId] ?? [$finding['category'], $finding['label'], [$id]];
            $sources = $indexed->only($ids)->values()->all();
            $checks = [];
            foreach ($sources as $source) {
                $consumed[$source['id']] = true;
                $checks = array_merge($checks, $source['checks'] ?? [Finding::check($source['label'], $source['status'], $source['evidence'])]);
            }
            $priorities = array_column($sources, 'priority');
            $group = Finding::make($definitionId, $category, $label, $checks, (bool) $finding['premium'], [], $priorities !== [] ? min($priorities) : 2, $finding['weight'] ?? 1);
            $group['source_ids'] = array_column($sources, 'id');
            $group['sources'] = array_map(fn ($source) => ['id' => $source['id'], 'label' => $source['label'], 'details' => $source['details'] ?? [], 'detail_count' => $source['detail_count'] ?? 0], $sources);
            $groups[] = $group;
        }

        return $groups;
    }
}
