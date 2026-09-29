<?php

namespace App\Services;

class EmailReportCsv
{
    /**
     * Check keys exported as their own columns, in order.
     *
     * @var list<string>
     */
    private const CHECK_KEYS = ['format', 'role', 'disposable', 'mx', 'smtp'];

    private const STATUS_LABELS = [
        'valid' => 'Deliverable',
        'invalid' => 'Undeliverable',
        'risky' => 'Risky',
        'unknown' => 'Unknown',
    ];

    private const CHECK_STATUS_LABELS = [
        'pass' => 'Pass',
        'fail' => 'Fail',
        'warn' => 'Warn',
        'skip' => 'Skipped',
        'unknown' => 'Unknown',
    ];

    /**
     * Build the downloadable CSV report (UTF-8 BOM prefixed so Excel opens it correctly).
     *
     * @param  list<array{email: string, status: string, summary: string, classification: array{label: string, text: string, code: string}, checks: list<array{key: string, label: string, status: string, detail: string}>}>  $results
     */
    public function build(array $results): string
    {
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Email',
            'Overall Status',
            'Classification',
            'Status Code',
            'Classification Detail',
            'Summary',
            'Format',
            'Role Mailbox',
            'Disposable',
            'Mail Servers (MX)',
            'Mailbox (SMTP)',
        ], ',', '"', '\\');

        foreach ($results as $result) {
            $checks = collect($result['checks'])->keyBy('key');

            fputcsv($stream, array_map($this->sanitize(...), [
                $result['email'],
                self::STATUS_LABELS[$result['status']] ?? $result['status'],
                $result['classification']['label'],
                $result['classification']['code'],
                $result['classification']['text'],
                $result['summary'],
                ...array_map(
                    fn (string $key): string => $this->checkCell($checks->get($key)),
                    self::CHECK_KEYS,
                ),
            ]), ',', '"', '\\');
        }

        rewind($stream);

        $csv = (string) stream_get_contents($stream);

        fclose($stream);

        return $csv;
    }

    /**
     * @param  array{status?: string, detail?: string}|null  $check
     */
    private function checkCell(?array $check): string
    {
        if ($check === null) {
            return '';
        }

        $status = self::CHECK_STATUS_LABELS[$check['status'] ?? ''] ?? ucfirst($check['status'] ?? '');

        return $status.' — '.($check['detail'] ?? '');
    }

    /**
     * Stop spreadsheet apps from executing formulas and flatten newlines.
     */
    private function sanitize(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
