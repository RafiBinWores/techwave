<?php

namespace App\Support\Seo;

final class ReportScores
{
    public const VERSION = 'weighted-v4';

    public const CATEGORIES = ['Metadata', 'Content', 'Others', 'Structure', 'Technical'];

    public const CATEGORY_LABELS = [
        'Metadata' => 'Meta data',
        'Content' => 'Page quality',
        'Others' => 'Others',
        'Structure' => 'Link structure',
        'Technical' => 'Server configuration',
    ];

    private const CATEGORY_WEIGHTS = ['Metadata' => 30, 'Content' => 25, 'Others' => 15, 'Structure' => 15, 'Technical' => 15];

    private const PRIORITY_WEIGHTS = [1 => 8, 2 => 4, 3 => 2, 4 => 1];

    public static function summarize(array $findings): array
    {
        $findings = (new ReportGroups)->build($findings);
        $categories = [];
        $earned = 0;
        $total = 0;
        foreach (self::CATEGORY_WEIGHTS as $category => $weight) {
            $score = self::score(array_filter($findings, fn ($finding) => $finding['category'] === $category));
            $categories[$category] = $score === null ? null : (int) round(100 * $score);
            if ($score !== null) {
                $earned += $score * $weight;
                $total += $weight;
            }
        }
        $categories['External factors'] = null;

        return ['score' => $total ? (int) round(100 * $earned / $total) : null, 'categories' => $categories, 'scoring_version' => self::VERSION];
    }

    private static function score(array $findings): ?float
    {
        $total = 0;
        $earned = 0;
        foreach ($findings as $finding) {
            if (($finding['weight'] ?? 1) === 0) {
                continue;
            }
            $checkTotal = 0;
            $checkEarned = 0;
            foreach ($finding['checks'] ?? [$finding] as $check) {
                if ($check['status'] === 'unknown' && ! in_array($finding['id'], ['http-redirects', 'robots'], true)) {
                    continue;
                }
                $checkTotal++;
                $checkEarned += match ($check['status']) {
                    'pass' => 1, 'warning' => 0.25, default => 0
                };
            }
            if ($checkTotal > 0) {
                $weight = self::PRIORITY_WEIGHTS[$finding['priority'] ?? 2] ?? 4;
                $groupScore = $checkEarned / $checkTotal;
                $groupScore = match ($finding['status']) {
                    'fail' => 0,
                    'warning' => min(0.25, $groupScore),
                    default => $groupScore,
                };
                $total += $weight;
                $earned += $groupScore * $weight;
            }
        }

        return $total ? $earned / $total : null;
    }
}
