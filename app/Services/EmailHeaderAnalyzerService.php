<?php

namespace App\Services;

class EmailHeaderAnalyzerService
{
    public const VERDICT_TRUSTED = 'trusted';

    public const VERDICT_FLAGGED = 'flagged';

    public const VERDICT_UNVERIFIED = 'unverified';

    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    public const STATUS_WARN = 'warn';

    public const STATUS_SKIP = 'skip';

    /**
     * Cap on the routing hops kept from the Received chain.
     */
    private const MAX_HOPS = 20;

    /**
     * A single hop slower than this marks the chain as delayed.
     */
    private const DELAY_WARN_SECONDS = 300;

    /**
     * Parse raw email headers and explain what they say about the message.
     *
     * @return array{valid: bool, fields: array<string, string|null>, auth: list<array{key: string, label: string, status: string, detail: string}>, chain: list<array{index: int, from: ?string, by: ?string, with: ?string, for: ?string, ip: ?string, at: ?int, delay: ?int, raw: string}>, headers_found: list<array{key: string, name: string, count: int, values: list<string>}>, flags: list<array{status: string, label: string, detail: string}>, verdict: string, summary: string, hop_count: int, analyzed_at: string}
     */
    public function analyze(string $input): array
    {
        $headers = $this->parse($input);

        if ($headers === []) {
            return [
                'valid' => false,
                'fields' => [],
                'auth' => [],
                'chain' => [],
                'headers_found' => [],
                'flags' => [],
                'verdict' => self::VERDICT_UNVERIFIED,
                'summary' => 'Paste a block of raw email headers to analyze.',
                'hop_count' => 0,
                'analyzed_at' => now()->toIso8601String(),
            ];
        }

        $fields = $this->fields($headers);
        $auth = $this->authChecks($headers);
        $chain = $this->receivedChain($headers);
        $flags = $this->flags($headers, $fields, $auth, $chain);
        $found = $this->headersFound($headers);
        ['verdict' => $verdict, 'summary' => $summary] = $this->summarize($auth, $flags);

        return [
            'valid' => true,
            'fields' => $fields,
            'auth' => $auth,
            'chain' => $chain,
            'headers_found' => $found,
            'flags' => $flags,
            'verdict' => $verdict,
            'summary' => $summary,
            'hop_count' => count($chain),
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Split raw input into a map of lowercase header name => list of values.
     * Accepts full raw messages (stops at the blank line) and unfolds
     * continuation lines the way RFC 5322 requires.
     *
     * @return array<string, list<string>>
     */
    public function parse(string $input): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $input);

        $blankLine = strpos($text, "\n\n");

        if ($blankLine !== false) {
            $text = substr($text, 0, $blankLine);
        }

        $text = preg_replace("/\n[ \t]+/", ' ', $text) ?? $text;

        $headers = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $colon = strpos($line, ':');

            if ($colon === false) {
                continue;
            }

            $name = strtolower(trim(substr($line, 0, $colon)));

            if (! preg_match('/^[a-z0-9-]+$/', $name)) {
                continue;
            }

            $headers[$name][] = trim(substr($line, $colon + 1));
        }

        return $headers;
    }

    /**
     * Decide the overall verdict from authentication results and red flags.
     *
     * @param  list<array{key: string, label: string, status: string, detail: string}>  $auth
     * @param  list<array{status: string, label: string, detail: string}>  $flags
     * @return array{verdict: string, summary: string}
     */
    public function summarize(array $auth, array $flags): array
    {
        $authFailed = array_filter($auth, fn (array $check) => $check['status'] === self::STATUS_FAIL);
        $authPassed = array_filter($auth, fn (array $check) => $check['status'] === self::STATUS_PASS);
        $flagFailed = array_filter($flags, fn (array $flag) => $flag['status'] === self::STATUS_FAIL);

        if ($authFailed !== [] || $flagFailed !== []) {
            $labels = array_merge(
                array_column($authFailed, 'label'),
                array_column($flagFailed, 'label'),
            );

            return [
                'verdict' => self::VERDICT_FLAGGED,
                'summary' => 'Flagged — '.implode(', ', array_unique($labels)).' reported a problem.',
            ];
        }

        $passedKeys = array_column($authPassed, 'key');

        if (in_array('spf', $passedKeys, true) && in_array('dkim', $passedKeys, true)) {
            return [
                'verdict' => self::VERDICT_TRUSTED,
                'summary' => 'Trusted — SPF and DKIM both passed and no red flags were found.',
            ];
        }

        return [
            'verdict' => self::VERDICT_UNVERIFIED,
            'summary' => 'Unverified — there is not enough authentication data to confirm the sender.',
        ];
    }

    /**
     * Pull the core envelope and identity headers out of the parsed block.
     *
     * @param  array<string, list<string>>  $headers
     * @return array<string, string|null>
     */
    private function fields(array $headers): array
    {
        $fields = [];

        foreach (['from', 'to', 'cc', 'subject', 'date', 'message-id', 'return-path', 'reply-to'] as $name) {
            $value = $headers[$name][0] ?? null;
            $fields[$name] = $value === null ? null : mb_decode_mimeheader($value);
        }

        return $fields;
    }

    /**
     * Read SPF, DKIM, and DMARC results from the receiving server's report.
     *
     * @param  array<string, list<string>>  $headers
     * @return list<array{key: string, label: string, status: string, detail: string}>
     */
    private function authChecks(array $headers): array
    {
        $sources = [
            ...($headers['authentication-results'] ?? []),
            ...($headers['arc-authentication-results'] ?? []),
        ];

        $tokens = [];

        foreach ($sources as $source) {
            $pattern = '/\b(spf|dkim|dmarc)\s*=\s*(pass|fail|softfail|none|neutral|permerror|temperror)\b/i';

            if (preg_match_all($pattern, $source, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $key = strtolower($match[1]);
                    $tokens[$key] ??= strtolower($match[2]);
                }
            }
        }

        if (! isset($tokens['spf']) && isset($headers['received-spf'][0])) {
            if (preg_match('/^(pass|fail|softfail|none|neutral|permerror|temperror)\b/i', $headers['received-spf'][0], $match)) {
                $tokens['spf'] = strtolower($match[1]);
            }
        }

        $identity = $this->reportedIdentity($sources);
        $signature = $this->dkimSignature($headers);

        return [
            $this->authCheck('spf', 'SPF', $tokens['spf'] ?? null, $identity),
            $this->authCheck('dkim', 'DKIM', $tokens['dkim'] ?? null, $signature),
            $this->authCheck('dmarc', 'DMARC', $tokens['dmarc'] ?? null, $identity),
        ];
    }

    private function authCheck(string $key, string $label, ?string $result, ?string $context): array
    {
        if ($result === null) {
            return [
                'key' => $key,
                'label' => $label,
                'status' => self::STATUS_WARN,
                'detail' => 'No '.$label.' result reported by the receiving server.',
            ];
        }

        $detail = match ($result) {
            'pass' => $label.' passed'.($context !== null ? ' for '.$context : '').'.',
            'fail' => $label.' failed'.($context !== null ? ' for '.$context : '').' — the sending server did not authenticate as this domain.',
            'softfail' => $label.' returned softfail — the sending server is not authorized but the message was still delivered.',
            'none' => $label.' returned none — the domain publishes no '.$label.' policy.',
            'neutral' => $label.' returned neutral — the server made no assertion about the sender.',
            'permerror' => $label.' returned a permanent error — the policy is malformed or cannot be evaluated.',
            'temperror' => $label.' returned a temporary error — the check could not complete.',
            default => $label.' returned '.$result.'.',
        };

        $status = match ($result) {
            'pass' => self::STATUS_PASS,
            'fail' => self::STATUS_FAIL,
            'temperror' => self::STATUS_SKIP,
            default => self::STATUS_WARN,
        };

        return ['key' => $key, 'label' => $label, 'status' => $status, 'detail' => $detail];
    }

    /**
     * Identity the auth results refer to (smtp.mailfrom / header.from).
     *
     * @param  list<string>  $sources
     */
    private function reportedIdentity(array $sources): ?string
    {
        $joined = implode(' ', $sources);

        if (preg_match('/header\.from=([^\s;]+)/i', $joined, $match)) {
            return 'header.from='.$match[1];
        }

        if (preg_match('/smtp\.mailfrom=([^\s;]+)/i', $joined, $match)) {
            return 'mailfrom='.$match[1];
        }

        return null;
    }

    /**
     * Describe the DKIM-Signature header when one is attached.
     *
     * @param  array<string, list<string>>  $headers
     */
    private function dkimSignature(array $headers): ?string
    {
        $signature = $headers['dkim-signature'][0] ?? null;

        if ($signature === null) {
            return null;
        }

        preg_match('/(?:^|;)\s*d=([^;\s]+)/i', $signature, $domain);
        preg_match('/(?:^|;)\s*s=([^;\s]+)/i', $signature, $selector);

        if (isset($domain[1])) {
            return 'selector "'.($selector[1] ?? '?').'" for '.$domain[1];
        }

        return null;
    }

    /**
     * Build the routing timeline from the Received headers (top header is the
     * most recent hop) and compute the delay introduced by each hop.
     *
     * @param  array<string, list<string>>  $headers
     * @return list<array{index: int, from: ?string, by: ?string, with: ?string, for: ?string, ip: ?string, at: ?int, delay: ?int, raw: string}>
     */
    private function receivedChain(array $headers): array
    {
        $chain = [];

        foreach (array_slice($headers['received'] ?? [], 0, self::MAX_HOPS) as $line) {
            $chain[] = $this->parseReceived($line);
        }

        $count = count($chain);

        for ($i = 0; $i < $count; $i++) {
            $chain[$i]['index'] = $i + 1;
            $chain[$i]['delay'] = null;

            if ($i < $count - 1 && $chain[$i]['at'] !== null && $chain[$i + 1]['at'] !== null) {
                $chain[$i]['delay'] = $chain[$i]['at'] - $chain[$i + 1]['at'];
            }
        }

        return $chain;
    }

    /**
     * Inventory of every header present in the paste, in input order.
     *
     * @param  array<string, list<string>>  $headers
     * @return list<array{key: string, name: string, count: int, values: list<string>}>
     */
    private function headersFound(array $headers): array
    {
        $acronyms = [
            'arc' => 'ARC',
            'auth' => 'Auth',
            'cc' => 'CC',
            'dkim' => 'DKIM',
            'dmarc' => 'DMARC',
            'id' => 'ID',
            'ip' => 'IP',
            'mime' => 'MIME',
            'otp' => 'OTP',
            'smtp' => 'SMTP',
            'spf' => 'SPF',
            'ssl' => 'SSL',
            'tls' => 'TLS',
            'url' => 'URL',
            'x' => 'X',
        ];

        $found = [];

        foreach ($headers as $key => $values) {
            $name = implode('-', array_map(
                fn (string $part) => $acronyms[$part] ?? ucfirst($part),
                explode('-', $key),
            ));

            $found[] = [
                'key' => $key,
                'name' => $name,
                'count' => count($values),
                'values' => array_map(fn (string $value) => mb_decode_mimeheader($value), $values),
            ];
        }

        return $found;
    }

    /**
     * @return array{index: int, from: ?string, by: ?string, with: ?string, for: ?string, ip: ?string, at: ?int, delay: ?int, raw: string}
     */
    private function parseReceived(string $line): array
    {
        $hop = [
            'index' => 0,
            'from' => null,
            'by' => null,
            'with' => null,
            'for' => null,
            'ip' => null,
            'at' => null,
            'delay' => null,
            'raw' => $line,
        ];

        if (preg_match('/\bfrom\s+([^\s(;]+)/i', $line, $match)) {
            $hop['from'] = $match[1];
        }

        if (preg_match('/\bby\s+([^\s(;]+)/i', $line, $match)) {
            $hop['by'] = $match[1];
        }

        if (preg_match('/\bwith\s+([^\s;]+)/i', $line, $match)) {
            $hop['with'] = $match[1];
        }

        if (preg_match('/\bfor\s+<([^>]+)>/i', $line, $match)) {
            $hop['for'] = $match[1];
        } elseif (preg_match('/\bfor\s+([^\s;]+)/i', $line, $match)) {
            $hop['for'] = $match[1];
        }

        if (preg_match('/\[([0-9a-fA-F:.]+)\]/', $line, $match)) {
            $hop['ip'] = $match[1];
        }

        if (preg_match('/;\s*(.+)$/', $line, $match)) {
            $timestamp = strtotime(trim($match[1]));
            $hop['at'] = $timestamp === false ? null : $timestamp;
        }

        return $hop;
    }

    /**
     * Turn the parsed evidence into human-readable red flags.
     *
     * @param  array<string, list<string>>  $headers
     * @param  array<string, string|null>  $fields
     * @param  list<array{key: string, label: string, status: string, detail: string}>  $auth
     * @param  list<array{index: int, from: ?string, by: ?string, with: ?string, for: ?string, ip: ?string, at: ?int, delay: ?int, raw: string}>  $chain
     * @return list<array{status: string, label: string, detail: string}>
     */
    private function flags(array $headers, array $fields, array $auth, array $chain): array
    {
        $flags = [];

        $failed = array_filter($auth, fn (array $check) => $check['status'] === self::STATUS_FAIL);

        if ($failed !== []) {
            $flags[] = [
                'status' => self::STATUS_FAIL,
                'label' => 'Authentication failure',
                'detail' => implode(', ', array_column($failed, 'label')).' reported a failure — treat this message as spoofed until verified.',
            ];
        } else {
            $passed = array_filter($auth, fn (array $check) => $check['status'] === self::STATUS_PASS);

            if (count($passed) >= 2) {
                $flags[] = [
                    'status' => self::STATUS_PASS,
                    'label' => 'Sender authenticated',
                    'detail' => implode(' and ', array_column($passed, 'label')).' both passed — the message comes from the domain it claims.',
                ];
            }
        }

        if (($fields['from'] ?? null) === null) {
            $flags[] = [
                'status' => self::STATUS_WARN,
                'label' => 'Missing From header',
                'detail' => 'The message has no From header, which legitimate mail always carries.',
            ];
        }

        $mismatch = $this->envelopeMismatch($fields);

        if ($mismatch !== null) {
            $flags[] = [
                'status' => self::STATUS_WARN,
                'label' => 'Envelope sender mismatch',
                'detail' => $mismatch,
            ];
        }

        if ($chain === []) {
            $flags[] = [
                'status' => self::STATUS_WARN,
                'label' => 'No routing history',
                'detail' => 'No Received headers were found — the routing path cannot be reconstructed.',
            ];
        } else {
            $worst = $this->worstDelay($chain);

            if ($worst !== null && $worst >= self::DELAY_WARN_SECONDS) {
                $flags[] = [
                    'status' => self::STATUS_WARN,
                    'label' => 'Delivery delayed',
                    'detail' => 'A hop in the routing chain took '.$this->formatDelay($worst).' to forward the message.',
                ];
            }

            if (count($chain) >= 8) {
                $flags[] = [
                    'status' => self::STATUS_WARN,
                    'label' => 'Long routing chain',
                    'detail' => 'The message passed through '.count($chain).' servers before delivery.',
                ];
            }
        }

        if (isset($headers['x-mailer'][0]) || isset($headers['x-originating-ip'][0])) {
            $flags[] = [
                'status' => self::STATUS_SKIP,
                'label' => 'Client fingerprints present',
                'detail' => 'Optional client headers leak software or IP details the sender may not have intended to expose.',
            ];
        }

        return $flags;
    }

    /**
     * Compare the envelope sender with the visible From domain.
     *
     * @param  array<string, string|null>  $fields
     */
    private function envelopeMismatch(array $fields): ?string
    {
        $envelope = $fields['return-path'] ?? null;
        $from = $fields['from'] ?? null;

        if ($envelope === null || $from === null) {
            return null;
        }

        $envelopeDomain = $this->domainOf($envelope);
        $fromDomain = $this->domainOf($from);

        if ($envelopeDomain === null || $fromDomain === null || $envelopeDomain === $fromDomain) {
            return null;
        }

        if (str_ends_with($envelopeDomain, '.'.$fromDomain) || str_ends_with($fromDomain, '.'.$envelopeDomain)) {
            return null;
        }

        return 'Return-Path is '.$envelopeDomain.' while From is '.$fromDomain.' — the envelope sender differs from the visible sender (forwarding, or a spoof attempt).';
    }

    private function domainOf(string $value): ?string
    {
        if (preg_match('/<([^>]*)>/', $value, $match)) {
            $value = $match[1];
        }

        $at = strrpos($value, '@');

        if ($at === false) {
            return null;
        }

        $domain = strtolower(trim(substr($value, $at + 1)));

        return $domain === '' ? null : $domain;
    }

    /**
     * @param  list<array{index: int, from: ?string, by: ?string, with: ?string, for: ?string, ip: ?string, at: ?int, delay: ?int, raw: string}>  $chain
     */
    private function worstDelay(array $chain): ?int
    {
        $delays = array_filter(array_column($chain, 'delay'));

        if ($delays === []) {
            return null;
        }

        return max($delays);
    }

    public function formatDelay(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        if ($seconds < 3600) {
            return intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        return intdiv($seconds, 3600).'h '.intdiv($seconds % 3600, 60).'m';
    }
}
