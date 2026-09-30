<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TempMailService
{
    /**
     * Disposable mailbox provider (mail.tm) — free API, no key required.
     */
    private const BASE_URL = 'https://api.mail.tm';

    private const TIMEOUT = 12;

    private const DOMAINS_CACHE_KEY = 'temp-mail.domains';

    private const INBOX_CACHE_SECONDS = 3;

    /**
     * 'auth' | 'rate' | 'unavailable' — why the last request failed, or null when it succeeded.
     */
    public ?string $lastError = null;

    /**
     * @return list<string> Active public domains the provider offers.
     */
    public function domains(): array
    {
        $this->lastError = null;

        $cached = Cache::get(self::DOMAINS_CACHE_KEY);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $response = $this->request('GET', '/domains');

        if ($response === null) {
            return [];
        }

        $domains = collect($this->collection($response))
            ->filter(fn (array $domain) => ($domain['isActive'] ?? false) === true)
            ->filter(fn (array $domain) => ($domain['isPrivate'] ?? false) === false)
            ->pluck('domain')
            ->filter(fn ($domain) => is_string($domain) && str_contains($domain, '.'))
            ->values()
            ->all();

        if ($domains !== []) {
            Cache::put(self::DOMAINS_CACHE_KEY, $domains, now()->addDay());
        }

        return $domains;
    }

    /**
     * Create a fresh mailbox and return its credentials for session storage.
     *
     * @return array{address: string, password: string, id: string, token: string, created_at: string}|null
     */
    public function createMailbox(): ?array
    {
        $domains = $this->domains();

        if ($domains === []) {
            $this->lastError ??= 'unavailable';

            return null;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $address = Str::lower(Str::random(10)).'@'.$domains[array_rand($domains)];
            $password = Str::random(24);

            $account = $this->request('POST', '/accounts', data: [
                'address' => $address,
                'password' => $password,
            ]);

            if ($account === null) {
                continue;
            }

            $token = $this->fetchToken($address, $password);

            if ($token === null) {
                $this->deleteAccount($account->json('id'), $token);

                continue;
            }

            return [
                'address' => $address,
                'password' => $password,
                'id' => (string) $account->json('id'),
                'token' => $token,
                'created_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Latest messages in the mailbox, normalized for the inbox list.
     *
     * @return list<array{id: string, from: string, from_name: string, subject: string, intro: string, created_at: string, has_attachments: bool}>|null
     */
    public function messages(string $token, bool $force = false): ?array
    {
        if (! $force) {
            $cached = Cache::get($this->inboxCacheKey($token));

            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->request('GET', '/messages?page=1', $token);

        if ($response === null) {
            return null;
        }

        $messages = collect($this->collection($response))
            ->filter(fn ($message) => is_array($message) && ($message['id'] ?? '') !== '')
            ->map(fn (array $message) => [
                'id' => (string) $message['id'],
                'from' => (string) ($message['from']['address'] ?? ''),
                'from_name' => (string) ($message['from']['name'] ?? ''),
                'subject' => $this->subject($message['subject'] ?? ''),
                'intro' => trim(strip_tags((string) ($message['intro'] ?? ''))),
                'created_at' => (string) ($message['createdAt'] ?? ''),
                'has_attachments' => (bool) ($message['hasAttachments'] ?? false),
                'seen' => (bool) ($message['seen'] ?? false),
            ])
            ->values()
            ->all();

        Cache::put($this->inboxCacheKey($token), $messages, self::INBOX_CACHE_SECONDS);

        return $messages;
    }

    /**
     * Full message body, normalized for the reader view.
     *
     * @return array{id: string, from: string, from_name: string, to: string, subject: string, text: string, html: string, created_at: string, has_attachments: bool}|null
     */
    public function message(string $token, string $id): ?array
    {
        if (! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) {
            $this->lastError = 'unavailable';

            return null;
        }

        $response = $this->request('GET', '/messages/'.$id, $token);

        if ($response === null) {
            return null;
        }

        $message = $response->json();

        if (! is_array($message)) {
            $this->lastError = 'unavailable';

            return null;
        }

        $html = $message['html'] ?? [];
        $html = is_array($html) ? implode("\n", $html) : (string) $html;

        return [
            'id' => (string) ($message['id'] ?? $id),
            'from' => (string) ($message['from']['address'] ?? ''),
            'from_name' => (string) ($message['from']['name'] ?? ''),
            'to' => collect($message['to'] ?? [])
                ->map(fn (array $recipient) => (string) ($recipient['address'] ?? ''))
                ->filter()
                ->implode(', '),
            'subject' => $this->subject($message['subject'] ?? ''),
            'text' => (string) ($message['text'] ?? ''),
            'html' => $html,
            'created_at' => (string) ($message['createdAt'] ?? ''),
            'has_attachments' => (bool) ($message['hasAttachments'] ?? false),
        ];
    }

    public function deleteMessage(string $token, string $id): bool
    {
        if (! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) {
            return false;
        }

        return $this->request('DELETE', '/messages/'.$id, $token) !== null;
    }

    /**
     * Best effort: remove the mailbox remotely so abandoned inboxes do not pile up.
     */
    public function deleteMailbox(string $token, string $id): bool
    {
        if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) {
            return false;
        }

        $deleted = $this->deleteAccount($id, $token);

        Cache::forget($this->inboxCacheKey($token));

        return $deleted;
    }

    private function deleteAccount(string $id, ?string $token): bool
    {
        if ($token === null || $id === '' || ! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) {
            return false;
        }

        return $this->request('DELETE', '/accounts/'.$id, $token) !== null;
    }

    private function fetchToken(string $address, string $password): ?string
    {
        $response = $this->request('POST', '/token', data: [
            'address' => $address,
            'password' => $password,
        ]);

        $token = $response?->json('token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Normalize list payloads — the API answers with a plain JSON array or a
     * hydra collection depending on the Accept header it receives.
     *
     * @return list<mixed>
     */
    private function collection(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['hydra:member']) && is_array($payload['hydra:member'])) {
            return array_values($payload['hydra:member']);
        }

        return array_is_list($payload) ? $payload : [];
    }

    private function inboxCacheKey(string $token): string
    {
        return 'temp-mail.inbox.'.hash('sha256', $token);
    }

    private function subject(mixed $subject): string
    {
        $subject = trim(strip_tags((string) $subject));

        return $subject !== '' ? $subject : '(no subject)';
    }

    private function request(string $method, string $uri, ?string $token = null, ?array $data = null): ?Response
    {
        try {
            $client = Http::timeout(self::TIMEOUT)
                ->acceptJson()
                ->withHeaders(array_filter([
                    'User-Agent' => 'TechWave-TempMail/1.0',
                    'Authorization' => $token !== null ? 'Bearer '.$token : null,
                ]));

            $response = match ($method) {
                'POST' => $client->post(self::BASE_URL.$uri, $data ?? []),
                'DELETE' => $client->delete(self::BASE_URL.$uri),
                default => $client->get(self::BASE_URL.$uri),
            };
        } catch (\Throwable) {
            $this->lastError = 'unavailable';

            return null;
        }

        $this->lastError = match (true) {
            in_array($response->status(), [401, 403], true) => 'auth',
            $response->status() === 429 => 'rate',
            ! $response->successful() => 'unavailable',
            default => null,
        };

        return $this->lastError === null ? $response : null;
    }
}
