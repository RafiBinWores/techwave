<?php

namespace App\Support\Seo;

class ReportPresenter
{
    public function present(array $report, bool $premium): array
    {
        $all = $report['findings'];
        if (! isset($report['group_version']) && ($report['scoring_version'] ?? null) === ReportScores::VERSION) {
            $categoryScores = ReportScores::summarize((new ReportGroups)->build($all))['categories'];
            foreach ($categoryScores as $category => $score) {
                if ($score !== null || ! isset($report['categories'][$category])) {
                    $report['categories'][$category] = $score;
                }
            }
            $report['group_version'] = 1;
        }
        $fixes = [];
        $summary = ['passed' => 0, 'warnings' => 0, 'failed' => 0, 'unknown' => 0];
        $checkCount = 0;
        foreach ($all as $finding) {
            $checks = $finding['checks'] ?? [['label' => $finding['label'], 'status' => $finding['status'], 'message' => $finding['evidence'], 'recommendation' => $finding['recommendation']]];
            $seen = [];
            foreach ($checks as $check) {
                $checkCount++;
                $summary[match ($check['status']) {
                    'pass' => 'passed', 'warning' => 'warnings', 'fail' => 'failed', default => 'unknown'
                }]++;
                $advice = $check['recommendation'] ?? '';
                if (! in_array($check['status'], ['fail', 'warning'], true) || $advice === '' || isset($seen[$advice])) {
                    continue;
                }
                $seen[$advice] = true;
                $fixes[] = ['id' => $finding['id'], 'label' => $finding['label'].' · '.$check['label'], 'status' => $check['status'], 'recommendation' => $advice, 'priority' => $finding['priority'] ?? 2];
            }
        }
        usort($fixes, fn ($a, $b) => (($a['status'] === 'fail' ? 0 : 1) <=> ($b['status'] === 'fail' ? 0 : 1)) ?: ($a['priority'] <=> $b['priority']) ?: strcmp($a['id'], $b['id']));
        $report['recommendations'] = $premium ? $fixes : array_slice($fixes, 0, 5);
        $report['recommendation_count'] = max((int) ($report['recommendation_count'] ?? 0), count($fixes));
        $report['findings'] = array_values(array_filter($all, fn ($finding) => $premium || (! $finding['premium'] && $finding['category'] !== 'Structure')));
        $report['groups'] = (new ReportGroups)->build($report['findings']);
        $report['locked_count'] = (int) ($report['locked_count'] ?? 0) + ($premium ? 0 : count($all) - count($report['findings']));
        $report['summary'] ??= $summary;
        $report['check_count'] = max((int) ($report['check_count'] ?? 0), $checkCount);
        $integrations = $report['integrations'] ?? ['backlinks' => ['status' => 'not_connected', 'metrics' => null], 'screenshot' => ['status' => 'not_connected', 'image_url' => null]];
        if (! $premium) {
            foreach ($integrations as $type => $integration) {
                $integrations[$type] = ['status' => in_array($integration['status'] ?? '', ['connected', 'premium_required'], true) ? 'premium_required' : ($integration['status'] ?? 'not_connected')];
            }
            unset($report['report_id']);
        }
        if (config('seo.integrations.screenshot.provider', 'thumio') === 'thumio'
            && empty($integrations['screenshot']['image_url'])
            && filter_var($report['url'] ?? '', FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($report['url'], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) {
            $integrations['screenshot'] = [
                'status' => 'ready',
                'image_url' => 'https://image.thum.io/get/width/600/crop/900/?url='.rawurlencode($report['url']),
            ];
        }
        $report['integrations'] = $integrations;

        return $report;
    }
}
