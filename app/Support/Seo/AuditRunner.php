<?php

namespace App\Support\Seo;

use App\Models\SeoReport;
use App\Models\User;

class AuditRunner
{
    public function __construct(private PublicUrl $urls, private SafeFetcher $fetcher, private DailyQuota $quota, private Entitlements $entitlements, private Analyzer $analyzer, private ReportPresenter $presenter) {}

    public function run(string $url, ?User $user, string $ip): array
    {
        $deadline = microtime(true) + (float) config('seo.audit_seconds', 20);
        $target = $this->urls->resolve($url, $deadline);
        $premium = $this->entitlements->premium($user);
        $page = $this->fetcher->fetch($target['url'], (int) config('seo.html_bytes', 2097152), $deadline, true, fn () => $this->quota->reserve($user, $ip));
        $report = $this->analyzer->analyze($page);
        $report['requested_url'] = $target['url'];
        $report['findings'] = collect($report['findings'])->keyBy('id')
            ->merge(collect((new ServerProbes($this->fetcher))->inspect($page, $deadline))->keyBy('id'))->values()->all();
        $report = array_replace($report, ReportScores::summarize($report['findings']));
        if ($premium) {
            $report['integrations'] = (new ExternalSignals($this->fetcher, $this->urls))->inspect($page->finalUrl, $deadline);
            $backlinks = $report['integrations']['backlinks'];
            foreach ($report['findings'] as &$finding) {
                if ($finding['id'] !== 'backlinks') {
                    continue;
                }
                if ($backlinks['status'] === 'connected') {
                    $rows = [];
                    $checks = [];
                    foreach ($backlinks['metrics'] as $name => $value) {
                        $label = ucfirst(str_replace('_', ' ', $name));
                        $checks[] = Finding::check($label, 'pass', number_format($value).' '.$label.' reported by the connected provider.');
                        $rows[] = ['metric' => $label, 'count' => $value];
                    }
                    $checks[] = Finding::check('Authority score', 'unknown', 'Provider counts are reported as measured data; no proprietary authority or ranking score is invented.');
                    $finding = Finding::make('backlinks', 'External factors', 'Backlinks', $checks, true, $rows, 2, 0);
                } elseif ($backlinks['status'] === 'unavailable') {
                    $finding = Finding::make('backlinks', 'External factors', 'Backlinks', [Finding::check('Provider availability', 'unknown', 'The configured backlink provider could not return valid data within this check budget.')], true, [], 2, 0);
                }
            }
            unset($finding);
            $premium = $this->entitlements->premium($user);
            if ($premium) {
                $saved = SeoReport::create(['user_id' => $user->id, 'requested_url' => $target['url'], 'final_url' => $page->finalUrl, 'score' => $report['score'], 'analyzer_version' => $report['version'], 'payload' => $report]);
                $report['report_id'] = $saved->id;
            }
        } else {
            foreach (['backlinks', 'screenshot'] as $type) {
                if (config('seo.integrations.'.$type.'.endpoint')) {
                    $report['integrations'][$type]['status'] = 'premium_required';
                }
            }
        }

        return $this->presenter->present($report, $premium);
    }
}
