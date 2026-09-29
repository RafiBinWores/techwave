<?php

namespace App\Services;

class EmailValidityService
{
    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_RISKY = 'risky';

    public const STATUS_UNKNOWN = 'unknown';

    private const SMTP_PORT = 25;

    private const SMTP_TIMEOUT_SECONDS = 4;

    private const MAX_MX_HOSTS = 2;

    /**
     * Domains that generate throwaway, disposable mailboxes.
     *
     * @var list<string>
     */
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com',
        '10minutemail.net',
        'burnermail.io',
        'dispostable.com',
        'dropmail.me',
        'emailondeck.com',
        'fakeinbox.com',
        'getnada.com',
        'guerrillamail.biz',
        'guerrillamail.com',
        'guerrillamail.net',
        'maildrop.cc',
        'mailinator.com',
        'mailnesia.com',
        'moakt.com',
        'mytemp.email',
        'sharklasers.com',
        'spam4.me',
        'tempail.com',
        'temp-mail.org',
        'tempmail.com',
        'tempmail.plus',
        'tempmailo.com',
        'throwawaymail.com',
        'tmail.ws',
        'trashmail.com',
        'trashmail.de',
        'yopmail.com',
        'yopmail.fr',
    ];

    /**
     * Local parts that reach a role or department instead of a person.
     *
     * @var list<string>
     */
    private const ROLE_LOCAL_PARTS = [
        'abuse',
        'admin',
        'administrator',
        'billing',
        'contact',
        'help',
        'hr',
        'info',
        'inquiries',
        'jobs',
        'legal',
        'marketing',
        'noreply',
        'no-reply',
        'office',
        'postmaster',
        'sales',
        'security',
        'service',
        'support',
        'team',
        'webmaster',
    ];

    /**
     * Run every validity check against a single email address.
     *
     * @return array{email: string, status: string, summary: string, classification: array{label: string, text: string, code: string}, checks: list<array{key: string, label: string, status: string, detail: string}>}
     */
    public function check(string $email, bool $deep = true): array
    {
        $result = $this->analyze($email, $deep);
        $result['classification'] = $this->classify($result);

        return $result;
    }

    /**
     * @return array{email: string, status: string, summary: string, checks: list<array{key: string, label: string, status: string, detail: string}>}
     */
    private function analyze(string $email, bool $deep): array
    {
        $email = trim($email);
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        $checks = [$this->formatCheck($email)];

        if ($checks[0]['status'] === 'fail') {
            return $this->result($email, self::STATUS_INVALID, 'The email address is not a valid format.', $checks);
        }

        $checks[] = $this->roleCheck($email);
        $checks[] = $this->disposableCheck($domain);

        $mx = $this->resolveMailHosts($domain);
        $checks[] = $mx['check'];

        if ($mx['check']['status'] === 'fail') {
            return $this->result($email, self::STATUS_INVALID, 'The domain cannot receive emails.', $checks);
        }

        if (! $deep) {
            $checks[] = [
                'key' => 'smtp',
                'label' => 'Mailbox (SMTP)',
                'status' => 'skip',
                'detail' => 'Deep verification is turned off.',
            ];

            $risky = $this->riskyLocalPart($email) || $this->isDisposable($domain);

            $status = $risky ? self::STATUS_RISKY : self::STATUS_VALID;

            return $this->result($email, $status, 'Format and mail servers verified. Mailbox not probed.', $checks);
        }

        $smtp = $this->smtpCheck($email, $mx['hosts']);
        $checks[] = $smtp['check'];

        $risky = $this->riskyLocalPart($email) || $this->isDisposable($domain);

        return match ($smtp['check']['status']) {
            'pass' => $this->result(
                $email,
                $risky ? self::STATUS_RISKY : self::STATUS_VALID,
                $risky ? 'Mailbox exists, but the address is low quality.' : 'Mailbox confirmed by the mail server.',
                $checks,
            ),
            'fail' => $this->result($email, self::STATUS_INVALID, 'The mail server rejected this mailbox.', $checks),
            default => $this->result($email, self::STATUS_UNKNOWN, 'The mail server could not confirm the mailbox.', $checks),
        };
    }

    /**
     * Build the human readable classification shown on the result card.
     *
     * @param  array{email: string, status: string, summary: string, checks: list<array{key: string, label: string, status: string, detail: string}>}  $result
     * @return array{label: string, text: string, code: string}
     */
    private function classify(array $result): array
    {
        $format = collect($result['checks'])->firstWhere('key', 'format');
        $mx = collect($result['checks'])->firstWhere('key', 'mx');
        $role = collect($result['checks'])->firstWhere('key', 'role');
        $disposable = collect($result['checks'])->firstWhere('key', 'disposable');
        $smtp = collect($result['checks'])->firstWhere('key', 'smtp');

        return match ($result['status']) {
            self::STATUS_VALID => [
                'label' => 'Deliverable',
                'code' => 'Success',
                'text' => ($smtp['status'] ?? null) === 'skip'
                    ? 'Format and mail server checks passed. Run deep verification to confirm the mailbox.'
                    : 'Valid email, with no high-risk factors detected: safe to send mail.',
            ],
            self::STATUS_RISKY => [
                'label' => 'Risky',
                'code' => 'Risky',
                'text' => ($disposable['status'] ?? null) === 'warn'
                    ? 'Mailbox exists, but the domain is a throwaway email provider: not safe for long-term contact.'
                    : 'Deliverable, but this is a team/role inbox rather than a personal mailbox.',
            ],
            self::STATUS_INVALID => $this->classifyInvalid($format, $mx),
            default => $this->classifyUnknown($smtp),
        };
    }

    /**
     * @param  array{status?: string}|null  $format
     * @param  array{status?: string}|null  $mx
     * @return array{label: string, text: string, code: string}
     */
    private function classifyInvalid(?array $format, ?array $mx): array
    {
        if (($format['status'] ?? null) === 'fail') {
            return [
                'label' => 'Invalid',
                'code' => 'Syntax error',
                'text' => 'Not a valid email address format: mail to this address will never be delivered.',
            ];
        }

        if (($mx['status'] ?? null) === 'fail') {
            return [
                'label' => 'Undeliverable',
                'code' => 'No mail server',
                'text' => 'The domain cannot receive email: messages sent to it will bounce.',
            ];
        }

        return [
            'label' => 'Undeliverable',
            'code' => 'Mailbox rejected',
            'text' => 'The mail server confirmed that this mailbox does not exist.',
        ];
    }

    /**
     * @param  array{status?: string, detail?: string}|null  $smtp
     * @return array{label: string, text: string, code: string}
     */
    private function classifyUnknown(?array $smtp): array
    {
        $detail = strtolower($smtp['detail'] ?? '');

        if (str_contains($detail, 'catch-all')) {
            return [
                'label' => 'Unknown',
                'code' => 'Catch-all domain',
                'text' => 'The domain accepts every address, so this mailbox cannot be confirmed.',
            ];
        }

        if (str_contains($detail, 'could not connect') || str_contains($detail, 'port 25')) {
            return [
                'label' => 'Unknown',
                'code' => 'Connection blocked',
                'text' => 'The mail server could not be reached on port 25: deliverability could not be verified.',
            ];
        }

        if (str_contains($detail, 'deferred')) {
            return [
                'label' => 'Unknown',
                'code' => 'Temporarily deferred',
                'text' => 'The mail server deferred the request, often greylisting: try again in a few minutes.',
            ];
        }

        if (str_contains($detail, 'refused') || str_contains($detail, 'blocked') || str_contains($detail, 'did not accept')) {
            return [
                'label' => 'Unknown',
                'code' => 'Server refused probe',
                'text' => 'The mail server refused the verification session: deliverability could not be confirmed.',
            ];
        }

        return [
            'label' => 'Unknown',
            'code' => 'Unknown',
            'text' => 'The mail server did not reveal whether this mailbox exists: deliverability could not be confirmed.',
        ];
    }

    /**
     * @return array{email: string, status: string, summary: string, checks: list<array{key: string, label: string, status: string, detail: string}>}
     */
    private function result(string $email, string $status, string $summary, array $checks): array
    {
        return [
            'email' => $email,
            'status' => $status,
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    private function formatCheck(string $email): array
    {
        $fail = fn (string $detail) => ['key' => 'format', 'label' => 'Format', 'status' => 'fail', 'detail' => $detail];

        if ($email === '' || strlen($email) > 254) {
            return $fail('Email address is empty or longer than 254 characters.');
        }

        if (! str_contains($email, '@')) {
            return $fail('Email address must contain an "@" symbol.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $fail('Email address does not match the standard email format.');
        }

        return ['key' => 'format', 'label' => 'Format', 'status' => 'pass', 'detail' => 'Valid RFC-compliant syntax.'];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    private function roleCheck(string $email): array
    {
        $local = strtolower(substr($email, 0, strpos($email, '@') ?: 0));

        if (in_array($local, self::ROLE_LOCAL_PARTS, true)) {
            return [
                'key' => 'role',
                'label' => 'Role Mailbox',
                'status' => 'warn',
                'detail' => 'Sends to a team inbox, not a personal mailbox.',
            ];
        }

        return ['key' => 'role', 'label' => 'Role Mailbox', 'status' => 'pass', 'detail' => 'Looks like a personal mailbox.'];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    private function disposableCheck(string $domain): array
    {
        if ($this->isDisposable($domain)) {
            return [
                'key' => 'disposable',
                'label' => 'Disposable',
                'status' => 'warn',
                'detail' => 'Domain is a known throwaway email provider.',
            ];
        }

        return ['key' => 'disposable', 'label' => 'Disposable', 'status' => 'pass', 'detail' => 'Not a known throwaway provider.'];
    }

    private function isDisposable(string $domain): bool
    {
        return in_array($domain, self::DISPOSABLE_DOMAINS, true);
    }

    private function riskyLocalPart(string $email): bool
    {
        $local = strtolower(substr($email, 0, strpos($email, '@') ?: 0));

        return in_array($local, self::ROLE_LOCAL_PARTS, true);
    }

    /**
     * Look up the mail exchangers for a domain, falling back to its A record.
     *
     * @return array{hosts: list<string>, check: array{key: string, label: string, status: string, detail: string}}
     */
    private function resolveMailHosts(string $domain): array
    {
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)) {
            return [
                'hosts' => [],
                'check' => ['key' => 'mx', 'label' => 'Mail Servers (MX)', 'status' => 'fail', 'detail' => 'Domain part is not a valid hostname.'],
            ];
        }

        $hosts = [];
        $weights = [];

        if (getmxrr($domain, $hosts, $weights) && $hosts !== []) {
            array_multisort($weights, $hosts);

            $check = [
                'key' => 'mx',
                'label' => 'Mail Servers (MX)',
                'status' => 'pass',
                'detail' => sprintf(
                    '%d MX record%s found%s',
                    count($hosts),
                    count($hosts) === 1 ? '' : 's',
                    isset($hosts[0]) ? ' ('.$hosts[0].')' : '',
                ),
            ];

            return ['hosts' => array_values(array_map(fn ($host) => rtrim($host, '.'), $hosts)), 'check' => $check];
        }

        if (gethostbyname($domain) !== $domain) {
            return [
                'hosts' => [],
                'check' => ['key' => 'mx', 'label' => 'Mail Servers (MX)', 'status' => 'pass', 'detail' => 'No MX record, but the domain resolves (implicit MX).'],
            ];
        }

        return [
            'hosts' => [],
            'check' => ['key' => 'mx', 'label' => 'Mail Servers (MX)', 'status' => 'fail', 'detail' => 'Domain does not exist or has no mail capability.'],
        ];
    }

    /**
     * Probe the domain's mail servers over SMTP to confirm the mailbox.
     *
     * @param  list<string>  $hosts
     * @return array{check: array{key: string, label: string, status: string, detail: string}}
     */
    private function smtpCheck(string $email, array $hosts): array
    {
        if ($hosts === []) {
            return ['check' => ['key' => 'smtp', 'label' => 'Mailbox (SMTP)', 'status' => 'skip', 'detail' => 'No mail server available to probe.']];
        }

        $failures = [];

        foreach (array_slice($hosts, 0, self::MAX_MX_HOSTS) as $host) {
            $attempt = $this->probeMailHost($host, $email);
            if ($attempt['status'] !== 'unreachable') {
                return ['check' => ['key' => 'smtp', 'label' => 'Mailbox (SMTP)', 'status' => $attempt['status'], 'detail' => $attempt['detail']]];
            }

            $failures[] = $host;
        }

        return [
            'check' => [
                'key' => 'smtp',
                'label' => 'Mailbox (SMTP)',
                'status' => 'unknown',
                'detail' => 'Could not connect to '.implode(', ', $failures).' on port '.self::SMTP_PORT.' (often blocked by hosts or ISPs).',
            ],
        ];
    }

    /**
     * Open an SMTP session and ask the server whether the mailbox exists.
     *
     * @return array{status: string, detail: string}
     */
    private function probeMailHost(string $host, string $email): array
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, self::SMTP_PORT, $errno, $errstr, self::SMTP_TIMEOUT_SECONDS);

        if (! is_resource($socket)) {
            return ['status' => 'unreachable', 'detail' => $errstr !== '' ? $errstr : 'Connection failed.'];
        }

        stream_set_timeout($socket, self::SMTP_TIMEOUT_SECONDS);

        try {
            $banner = $this->readReply($socket);

            if ($banner['code'] === 0) {
                return ['status' => 'unknown', 'detail' => $host.' did not send a valid SMTP banner.'];
            }

            if ($banner['code'] >= 500) {
                return ['status' => 'unknown', 'detail' => $host.' refused the session ('.$banner['code'].').'];
            }

            $this->write($socket, 'EHLO localhost');
            $hello = $this->readReply($socket);

            if ($hello['code'] !== 250) {
                $this->write($socket, 'HELO localhost');
                $hello = $this->readReply($socket);

                if ($hello['code'] !== 250) {
                    return ['status' => 'unknown', 'detail' => $host.' did not accept the EHLO/HELO handshake.'];
                }
            }

            $this->write($socket, 'MAIL FROM:<verify@techwave.test>');
            $from = $this->readReply($socket);

            if ($from['code'] !== 250) {
                return ['status' => 'unknown', 'detail' => $host.' blocked the probe sender ('.$from['code'].').'];
            }

            $this->write($socket, 'RCPT TO:<'.$email.'>');
            $rcpt = $this->readReply($socket);

            if ($rcpt['code'] >= 200 && $rcpt['code'] < 300) {
                $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

                if ($this->acceptsUnknownMailbox($socket, $domain)) {
                    return ['status' => 'unknown', 'detail' => 'Domain accepts every address (catch-all), so the mailbox cannot be confirmed.'];
                }

                return ['status' => 'pass', 'detail' => $host.' accepted the recipient.'];
            }

            if ($rcpt['code'] >= 500) {
                if ($this->isMailboxMissing($rcpt)) {
                    return ['status' => 'fail', 'detail' => $host.' reported the mailbox does not exist ('.$rcpt['code'].').'];
                }

                return ['status' => 'unknown', 'detail' => $host.' rejected the recipient ('.$rcpt['code'].').'];
            }

            return ['status' => 'unknown', 'detail' => $host.' deferred the request ('.$rcpt['code'].').'];
        } finally {
            if (is_resource($socket)) {
                $this->write($socket, 'QUIT');
                fclose($socket);
            }
        }
    }

    /**
     * Check whether the server also accepts a randomly generated address.
     */
    private function acceptsUnknownMailbox($socket, string $domain): bool
    {
        if ($domain === '') {
            return false;
        }

        $probe = 'verify'.bin2hex(random_bytes(6)).'@'.$domain;

        $this->write($socket, 'RCPT TO:<'.$probe.'>');
        $reply = $this->readReply($socket);

        return $reply['code'] >= 200 && $reply['code'] < 300;
    }

    private function isMailboxMissing(array $reply): bool
    {
        if (in_array($reply['code'], [550, 551, 553], true)) {
            return true;
        }

        return str_contains($reply['text'], '5.1.1')
            || str_contains($reply['text'], '5.1.0')
            || str_contains(strtolower($reply['text']), 'user unknown')
            || str_contains(strtolower($reply['text']), 'does not exist')
            || str_contains(strtolower($reply['text']), 'no such user')
            || str_contains(strtolower($reply['text']), 'recipient rejected');
    }

    private function write($socket, string $command): void
    {
        @fwrite($socket, $command."\r\n");
    }

    /**
     * Read a full SMTP reply, including multi-line responses.
     *
     * @return array{code: int, text: string}
     */
    private function readReply($socket): array
    {
        $code = 0;
        $lines = [];

        while (($line = fgets($socket, 1024)) !== false) {
            $line = rtrim($line, "\r\n");
            $lines[] = $line;

            if (preg_match('/^(\d{3})(?: |$)/', $line, $matches)) {
                $code = (int) $matches[1];
                break;
            }
        }

        if ($code === 0 && $lines !== []) {
            $meta = stream_get_meta_data($socket);

            if (! ($meta['timed_out'] ?? false)) {
                return ['code' => 0, 'text' => 'Malformed SMTP reply.'];
            }
        }

        return ['code' => $code, 'text' => implode(' | ', $lines)];
    }
}
