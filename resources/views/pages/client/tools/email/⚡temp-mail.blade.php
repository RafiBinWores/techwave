<?php

use App\Models\ToolCategory;
use App\Models\ToolPlan;
use App\Services\TempMailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Temp Mail')] class extends Component
{
    public string $address = '';

    public bool $isCreating = false;

    /** @var list<array{id: string, from: string, from_name: string, subject: string, intro: string, created_at: string, has_attachments: bool, seen: bool}> */
    public array $messages = [];

    public bool $hasLoadedInbox = false;

    public ?string $inboxError = null;

    public ?string $openMessageId = null;

    /** @var array{id: string, from: string, from_name: string, to: string, subject: string, text: string, html: string, created_at: string, has_attachments: bool, attachments: list<array{id: string, filename: string, content_type: string, disposition: string, related: bool, size: int}>, link_expires_at?: int}|null */
    public ?array $openMessage = null;

    /** @var list<string> */
    public array $readIds = [];

    private ?ToolCategory $category = null;

    private const SESSION_KEY = 'temp_mail';

    private const MESSAGE_CACHE_PREFIX = 'temp-mail.message.';

    private const TOKEN_CACHE_PREFIX = 'temp-mail.token.';

    private const ATTACHMENT_LINK_HOURS = 6;

    private const FREE_MESSAGE_LIMIT = 5;

    private const PREMIUM_MESSAGE_LIMIT = 30;

    private const REFRESH_MIN_LOADING_SECONDS = 0.45;

    public function boot(): void
    {
        $this->category = ToolCategory::query()->where('slug', 'email-tools')->first();
    }

    public function mount(): void
    {
        if ($mailbox = $this->mailbox()) {
            $this->address = $mailbox['address'];
            $this->rememberMailboxToken($mailbox);
            $this->refreshInbox(app(TempMailService::class));

            return;
        }

        if ($this->provisionMailbox(app(TempMailService::class)) === null) {
            $this->inboxError = 'Could not reach the mailbox service — hit Retry to create your address.';

            $this->dispatch(
                'toast',
                message: 'Could not create a mailbox right now. Please try again in a moment.',
                type: 'error',
            );
        }
    }

    public function getIsPremiumUserProperty(): bool
    {
        return $this->category !== null
            && auth()->check()
            && auth()->user()->hasActiveToolSubscription($this->category);
    }

    public function getUpgradePlanProperty(): ?ToolPlan
    {
        return $this->category?->activePlans()->first();
    }

    public function getHasMailboxProperty(): bool
    {
        return $this->mailbox() !== null;
    }

    public function getPlanLimitsProperty(): array
    {
        return [
            'free' => self::FREE_MESSAGE_LIMIT,
            'premium' => self::PREMIUM_MESSAGE_LIMIT,
        ];
    }

    public function getUnreadCountProperty(): int
    {
        return count(array_filter($this->messages, fn(array $message) => ! ($message['seen'] ?? false)));
    }

    /**
     * Signed inline links for the open message's attachments, keyed by id.
     * The expiry is stamped once per opened message so every re-render emits
     * the same URL and the body iframe never reloads.
     *
     * @return array<string, string>
     */
    public function getAttachmentUrlsProperty(): array
    {
        return $this->signedAttachmentUrls();
    }

    /**
     * Signed links that force a download when an attachment is clicked,
     * keyed by id.
     *
     * @return array<string, string>
     */
    public function getAttachmentDownloadUrlsProperty(): array
    {
        return $this->signedAttachmentUrls(['download' => 1]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    private function signedAttachmentUrls(array $extra = []): array
    {
        $mailbox = $this->mailbox();
        $attachments = $this->openMessage['attachments'] ?? [];

        if ($mailbox === null || $attachments === [] || $this->openMessage === null) {
            return [];
        }

        $expiresAt = Carbon::createFromTimestamp(
            $this->openMessage['link_expires_at'] ?? now()->addHours(self::ATTACHMENT_LINK_HOURS)->getTimestamp()
        );

        return collect($attachments)
            ->mapWithKeys(fn(array $attachment) => [
                $attachment['id'] => URL::temporarySignedRoute('client.tools.temp-mail.attachment', $expiresAt, array_merge([
                    'mailbox' => $mailbox['id'],
                    'message' => $this->openMessage['id'],
                    'attachment' => $attachment['id'],
                    'name' => $attachment['filename'],
                ], $extra)),
            ])
            ->all();
    }

    /**
     * The message body with provider attachment references (attachment:/cid:)
     * rewritten to links this server can serve, and standalone images wrapped
     * in download anchors so they can be saved straight from the reader.
     */
    public function getRenderedHtmlProperty(): string
    {
        return $this->bodyRewrite()['html'];
    }

    /**
     * Attachments already linked for download inside the body — they do not
     * need to be repeated in the list below the message.
     *
     * @return list<string>
     */
    public function getLinkedBodyAttachmentIdsProperty(): array
    {
        return $this->bodyRewrite()['linked'];
    }

    /** @var array{html: string, linked: list<string>}|null */
    private ?array $bodyRewrite = null;

    private ?string $bodyRewriteFor = null;

    /**
     * @return array{html: string, linked: list<string>}
     */
    private function bodyRewrite(): array
    {
        $key = $this->openMessage['id'] ?? null;

        if ($key === null) {
            return ['html' => '', 'linked' => []];
        }

        if ($this->bodyRewrite !== null && $this->bodyRewriteFor === $key) {
            return $this->bodyRewrite;
        }

        $this->bodyRewriteFor = $key;

        $html = $this->openMessage['html'] ?? '';

        if ($html === '') {
            return $this->bodyRewrite = ['html' => '', 'linked' => []];
        }

        $inlineUrls = $this->attachment_urls;
        $downloadUrls = $this->attachment_download_urls;
        $replacements = [];
        $images = [];

        foreach ($inlineUrls as $id => $url) {
            $replacements['attachment:' . $id] = $url;
            $replacements['cid:' . $id] = $url;

            $images[$url] = [
                'id' => $id,
                'download' => $downloadUrls[$id] ?? $url,
                'filename' => $this->attachmentFilename($id),
            ];
        }

        if ($replacements !== []) {
            $html = strtr($html, $replacements);
        }

        return $this->bodyRewrite = $this->wrapStandaloneImages($html, $images);
    }

    /**
     * Wrap images that sit outside any anchor in a link to their download URL.
     * Images already inside a link keep the sender's destination and are left
     * for the attachments list instead.
     *
     * @param  array<string, array{id: string, download: string, filename: string}>  $images  keyed by inline URL
     * @return array{html: string, linked: list<string>}
     */
    private function wrapStandaloneImages(string $html, array $images): array
    {
        if ($images === []) {
            return ['html' => $html, 'linked' => []];
        }

        preg_match_all('/<a\b[^>]*>|<\/a\s*>|<img\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE);

        $wraps = [];
        $linked = [];
        $depth = 0;

        foreach ($matches[0] as [$tag, $offset]) {
            if (preg_match('/^<a\b/i', $tag) === 1) {
                $depth++;

                continue;
            }

            if (str_starts_with($tag, '</')) {
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($depth > 0) {
                continue;
            }

            $source = $this->imageSource($tag);

            if ($source === null || ! isset($images[$source])) {
                continue;
            }

            $image = $images[$source];

            $wraps[] = [
                'offset' => $offset,
                'length' => strlen($tag),
                'value' => sprintf(
                    '<a href="%s" download="%s">%s</a>',
                    e($image['download']),
                    e($image['filename']),
                    $tag,
                ),
            ];

            $linked[] = $image['id'];
        }

        if ($wraps === []) {
            return ['html' => $html, 'linked' => $linked];
        }

        usort($wraps, fn(array $a, array $b) => $b['offset'] <=> $a['offset']);

        foreach ($wraps as $wrap) {
            $html = substr_replace($html, $wrap['value'], $wrap['offset'], $wrap['length']);
        }

        return ['html' => $html, 'linked' => array_values(array_unique($linked))];
    }

    private function imageSource(string $tag): ?string
    {
        if (preg_match('/\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $tag, $match) !== 1) {
            return null;
        }

        $source = ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');

        return $source !== '' ? $source : null;
    }

    private function attachmentFilename(string $id): string
    {
        foreach ($this->openMessage['attachments'] ?? [] as $attachment) {
            if (($attachment['id'] ?? '') === $id) {
                return $attachment['filename'];
            }
        }

        return 'attachment';
    }

    /**
     * Attachments that are not already linked for download inside the body.
     *
     * @return list<array{id: string, filename: string, content_type: string, size: string, url: string, download_url: string}>
     */
    public function getOpenAttachmentsProperty(): array
    {
        $urls = $this->attachment_urls;

        if ($urls === []) {
            return [];
        }

        $downloadUrls = $this->attachment_download_urls;
        $linkedIds = $this->linked_body_attachment_ids;

        return collect($this->openMessage['attachments'] ?? [])
            ->map(function (array $attachment) use ($urls, $downloadUrls, $linkedIds) {
                $url = $urls[$attachment['id']] ?? null;

                if ($url === null) {
                    return null;
                }

                if (($attachment['disposition'] ?? '') === 'inline' && in_array($attachment['id'], $linkedIds, true)) {
                    return null;
                }

                return [
                    'id' => $attachment['id'],
                    'filename' => $attachment['filename'],
                    'content_type' => $attachment['content_type'],
                    'size' => $this->formatAttachmentSize($attachment['size']),
                    'url' => $url,
                    'download_url' => $downloadUrls[$attachment['id']] ?? $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function messagesLimit(): int
    {
        return $this->is_premium_user ? self::PREMIUM_MESSAGE_LIMIT : self::FREE_MESSAGE_LIMIT;
    }

    public function createMailbox(TempMailService $service): void
    {
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        $mailbox = $this->provisionMailbox($service);

        $this->isCreating = false;

        if ($mailbox === null) {
            $this->inboxError = 'Could not reach the mailbox service — hit Retry to create your address.';

            $this->dispatch(
                'toast',
                message: 'Could not create a mailbox right now. Please try again in a moment.',
                type: 'error',
            );

            return;
        }

        $this->dispatch('toast', message: 'New address created — it is ready to receive mail.', type: 'success');
    }

    /**
     * Replace any existing mailbox with a freshly provisioned one.
     *
     * @return array{address: string, password: string, id: string, token: string, created_at: string}|null
     */
    private function provisionMailbox(TempMailService $service): ?array
    {
        if ($existing = $this->mailbox()) {
            $service->deleteMailbox($existing['token'], $existing['id']);
            Cache::forget(self::TOKEN_CACHE_PREFIX . $existing['id']);
        }

        $mailbox = $service->createMailbox();

        if ($mailbox === null) {
            return null;
        }

        session([self::SESSION_KEY => $mailbox]);
        $this->rememberMailboxToken($mailbox);

        $this->resetInbox();
        $this->address = $mailbox['address'];

        $this->refreshInbox($service);

        return $mailbox;
    }

    public function refreshInbox(TempMailService $service, bool $manual = false): void
    {
        $startedAt = microtime(true);

        try {
            $mailbox = $this->mailbox();

            if ($mailbox === null) {
                return;
            }

            $messages = $service->messages($mailbox['token'], force: $manual);

            if ($messages === null) {
                $this->handleMailboxFailure($service);

                return;
            }

            $this->inboxError = null;

            $this->messages = collect($messages)
                ->map(function (array $message) {
                    $message['seen'] = ($message['seen'] ?? false)
                        || in_array($message['id'], $this->readIds, true);

                    return $message;
                })
                ->take($this->messagesLimit())
                ->values()
                ->all();

            $this->hasLoadedInbox = true;
        } finally {
            if ($manual) {
                $this->holdRefreshLoadingWindow($startedAt);
            }
        }
    }

    public function showMessage(string $id, TempMailService $service): void
    {
        $mailbox = $this->mailbox();

        if ($mailbox === null || $this->openMessageId === $id) {
            return;
        }

        $cacheKey = $this->messageCacheKey($mailbox['id'], $id);
        $message = Cache::get($cacheKey);

        if (! is_array($message)) {
            $message = $service->message($mailbox['token'], $id);

            if ($message === null) {
                $this->handleMailboxFailure($service);

                if ($service->lastError === null || $service->lastError === 'rate') {
                    $this->dispatch('toast', message: 'Could not load that message.', type: 'error');
                }

                return;
            }

            Cache::put($cacheKey, $message, now()->addMinutes(30));
        }

        $message['link_expires_at'] = now()->addHours(self::ATTACHMENT_LINK_HOURS)->getTimestamp();

        $this->openMessage = $message;
        $this->openMessageId = $id;

        if (! in_array($id, $this->readIds, true)) {
            $this->readIds[] = $id;
        }

        $this->messages = collect($this->messages)
            ->map(function (array $listMessage) use ($id) {
                if ($listMessage['id'] === $id) {
                    $listMessage['seen'] = true;
                }

                return $listMessage;
            })
            ->values()
            ->all();
    }

    public function closeMessage(): void
    {
        $this->openMessage = null;
        $this->openMessageId = null;
    }

    public function deleteOpenMessage(TempMailService $service): void
    {
        $mailbox = $this->mailbox();
        $id = $this->openMessageId;

        if ($mailbox === null || $id === null) {
            return;
        }

        $service->deleteMessage($mailbox['token'], $id);
        Cache::forget($this->messageCacheKey($mailbox['id'], $id));

        $this->messages = collect($this->messages)
            ->reject(fn(array $message) => $message['id'] === $id)
            ->values()
            ->all();

        $this->readIds = array_values(array_diff($this->readIds, [$id]));
        $this->closeMessage();

        $this->dispatch('toast', message: 'Message deleted.', type: 'success');
    }

    public function deleteMailbox(TempMailService $service): void
    {
        $mailbox = $this->mailbox();

        if ($mailbox === null) {
            return;
        }

        $service->deleteMailbox($mailbox['token'], $mailbox['id']);

        foreach ($this->messages as $message) {
            Cache::forget($this->messageCacheKey($mailbox['id'], $message['id']));
        }

        $this->forgetMailbox();

        $this->dispatch('toast', message: 'Mailbox deleted.', type: 'success');
    }

    private function handleMailboxFailure(TempMailService $service): void
    {
        if ($service->lastError === 'auth') {
            $this->forgetMailbox();

            $this->dispatch(
                'toast',
                message: 'That mailbox has expired. Generate a new address to continue.',
                type: 'warning',
            );

            return;
        }

        $this->inboxError = $service->lastError === 'rate'
            ? 'The mail provider is rate limiting us — the inbox retries automatically.'
            : 'Could not reach the mailbox service — the inbox retries automatically.';
    }

    /**
     * Manual refreshes can return from cache almost instantly, so hold the
     * request open long enough for the loading spinner to be seen.
     */
    private function holdRefreshLoadingWindow(float $startedAt): void
    {
        $remaining = self::REFRESH_MIN_LOADING_SECONDS - (microtime(true) - $startedAt);

        if ($remaining > 0) {
            usleep((int) round($remaining * 1_000_000));
        }
    }

    private function resetInbox(): void
    {
        $this->messages = [];
        $this->hasLoadedInbox = false;
        $this->inboxError = null;
        $this->readIds = [];
        $this->openMessage = null;
        $this->openMessageId = null;
    }

    private function forgetMailbox(): void
    {
        if ($mailbox = $this->mailbox()) {
            Cache::forget(self::TOKEN_CACHE_PREFIX . $mailbox['id']);
        }

        session()->forget(self::SESSION_KEY);

        $this->address = '';
        $this->resetInbox();
    }

    /**
     * Keep the provider token reachable for signed attachment links, which are
     * fetched from the sandboxed body without the session cookie.
     *
     * @param  array{address: string, password: string, id: string, token: string, created_at: string}  $mailbox
     */
    private function rememberMailboxToken(array $mailbox): void
    {
        Cache::put(
            self::TOKEN_CACHE_PREFIX . $mailbox['id'],
            $mailbox['token'],
            now()->addHours(self::ATTACHMENT_LINK_HOURS * 2),
        );
    }

    /**
     * Attachment sizes arrive from the provider in kilobytes.
     */
    private function formatAttachmentSize(int $sizeInKilobytes): string
    {
        if ($sizeInKilobytes <= 0) {
            return '';
        }

        if ($sizeInKilobytes < 1024) {
            return $sizeInKilobytes . ' KB';
        }

        return round($sizeInKilobytes / 1024, 1) . ' MB';
    }

    /**
     * @return array{address: string, password: string, id: string, token: string, created_at: string}|null
     */
    private function mailbox(): ?array
    {
        $mailbox = session(self::SESSION_KEY);

        if (
            ! is_array($mailbox)
            || ($mailbox['address'] ?? '') === ''
            || ($mailbox['token'] ?? '') === ''
            || ($mailbox['id'] ?? '') === ''
        ) {
            return null;
        }

        return $mailbox;
    }

    private function messageCacheKey(string $mailboxId, string $messageId): string
    {
        return self::MESSAGE_CACHE_PREFIX . $mailboxId . '.' . $messageId;
    }
};
?>

<section class="min-h-screen text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col items-center px-4 pb-24 pt-10 sm:px-6 lg:px-8">

        {{-- Hero Header --}}
        <div class="mb-10 text-center">
            <h1 class="text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl">
                Temp
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text italic text-transparent pr-1.5">
                    Mail
                </span>
            </h1>

            <p class="mx-auto mt-4 max-w-2xl text-base lg:leading-7 text-blue-100/60 sm:text-lg">
                A disposable email address and live temporary inbox — ready the moment this page opens,
                no signup required.
            </p>
        </div>

        <div class="grid w-full grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Main Section --}}
            <section
                class="relative flex min-h-100 flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-8 lg:col-span-8"
                @if ($this->has_mailbox) wire:poll.visible.15s="refreshInbox" @endif>
                <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                </div>

                <div class="relative lg:flex lg:flex-1 lg:flex-col">
                    @if ($this->openMessage !== null)
                    {{-- Reader --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <button type="button" wire:click="closeMessage"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-white/15 bg-white/8 px-2.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-white transition hover:border-cyan-400/30 hover:bg-white/12 sm:gap-2 sm:px-4 sm:py-2 sm:text-xs">
                            <span class="material-symbols-outlined text-sm sm:text-base">arrow_back</span>
                            Back to inbox
                        </button>

                        <button type="button"
                            x-on:click="confirmAlert({
                                title: 'Delete this message?',
                                message: 'This message will be permanently removed and cannot be recovered.',
                                confirmText: 'Delete',
                                danger: true,
                            }).then(confirmed => confirmed &amp;&amp; $wire.deleteOpenMessage())"
                            wire:loading.attr="disabled"
                            wire:target="deleteOpenMessage"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-red-300/25 bg-red-400/10 px-2.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-red-300 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 sm:gap-2 sm:px-4 sm:py-2 sm:text-xs">
                            <span class="material-symbols-outlined text-sm sm:text-base">delete</span>
                            Delete
                        </button>
                    </div>

                    <h2 class="mt-5 text-xl font-extrabold text-white sm:text-2xl">
                        {{ $this->openMessage['subject'] }}
                    </h2>

                    <div class="mt-4 space-y-1.5 rounded-xl border border-white/8 bg-slate-950/35 px-4 py-3 text-xs">
                        <div class="flex items-start gap-2">
                            <span class="w-20 shrink-0 font-bold uppercase tracking-wider text-blue-100/45">From</span>
                            <span class="min-w-0 break-all text-white">
                                {{ $this->openMessage['from_name'] !== '' ? $this->openMessage['from_name'] . ' · ' : '' }}{{ $this->openMessage['from'] }}
                            </span>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="w-20 shrink-0 font-bold uppercase tracking-wider text-blue-100/45">To</span>
                            <span class="min-w-0 break-all text-blue-100/70">{{ $this->openMessage['to'] ?: $this->address }}</span>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="w-20 shrink-0 font-bold uppercase tracking-wider text-blue-100/45">Received</span>
                            <span class="text-blue-100/70">
                                {{ $this->openMessage['created_at'] !== '' ? Carbon::parse($this->openMessage['created_at'])->toDayDateTimeString() : '' }}
                            </span>
                        </div>

                    </div>

                    @if ($this->rendered_html !== '')
                    <iframe sandbox="allow-popups allow-popups-to-escape-sandbox allow-downloads allow-top-navigation-by-user-activation"
                        title="Message body"
                        srcdoc="{{ $this->rendered_html . '<style>:root{color-scheme:dark}html,body{background:#0f172a !important;color:#e2e8f0 !important}html{scrollbar-width:thin;scrollbar-color:rgb(148 163 184 / 0.45) transparent}*::-webkit-scrollbar{width:5px}*::-webkit-scrollbar-track{background:transparent}*::-webkit-scrollbar-thumb{background:rgb(148 163 184 / 0.45);border-radius:999px}a[download]{position:relative}a[download]::before{content:"";position:absolute;inset:0;border-radius:6px;background:rgb(15 23 42 / 0.7);opacity:0;pointer-events:none}a[download]::after{content:"";position:absolute;left:50%;top:50%;width:22px;height:22px;margin:-11px 0 0 -11px;border:2.5px solid rgb(103 232 249 / 0.3);border-top-color:rgb(103 232 249);border-radius:50%;opacity:0;pointer-events:none}a[download]:focus::before,a[download]:focus::after{opacity:1}a[download]:focus::before{animation:dl-hide .4s ease 2.6s forwards}a[download]:focus::after{animation:dl-spin .9s linear infinite,dl-hide .4s ease 2.6s forwards}a[download]:active::before,a[download]:active::after{opacity:1}a[download]:active::before{animation:none}a[download]:active::after{animation:dl-spin .9s linear infinite}@keyframes dl-spin{to{transform:rotate(360deg)} }@keyframes dl-hide{to{opacity:0} }</style>' }}"
                        class="mt-4 h-[460px] w-full rounded-xl border border-white/10 bg-[#0f172a]">
                    </iframe>
                    @elseif ($this->openMessage['text'] !== '')
                    <pre
                        class="mail-body-scroll mt-4 max-h-[460px] overflow-auto whitespace-pre-wrap rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm leading-6 text-blue-100/80">{{ $this->openMessage['text'] }}</pre>
                    @else
                    <div
                        class="mt-4 flex items-start gap-2 rounded-xl border border-white/10 bg-white/4 px-4 py-3 text-xs leading-5 text-blue-100/55">
                        <span class="material-symbols-outlined text-sm text-cyan-300">draft</span>
                        <p>This message has no readable content.</p>
                    </div>
                    @endif

                    @if ($this->openMessage['has_attachments'])
                    <div class="mt-4 rounded-xl border border-white/8 bg-slate-950/35 px-4 py-3 text-xs">
                        <p class="mb-2 text-blue-100/70">Attachments</p>

                        @if ($this->open_attachments !== [])
                        <ul class="flex flex-col gap-2">
                            @foreach ($this->open_attachments as $attachment)
                            <li wire:key="attachment-{{ $attachment['id'] }}" x-data="{ saving: false, percent: 0 }">
                                <a href="{{ $attachment['download_url'] }}" download="{{ $attachment['filename'] }}"
                                    x-on:click="
                                        if (saving) { $event.preventDefault(); return; }
                                        if (typeof tempMailDownload !== 'function') { return; }
                                        $event.preventDefault();
                                        saving = true;
                                        percent = 0;
                                        tempMailDownload($el.href, $el.download, (value) => percent = value)
                                            .then((ok) => {
                                                saving = false;
                                                if (! ok) {
                                                    $dispatch('toast', { message: 'The download could not be prepared. Please try again.', type: 'error' });
                                                }
                                            });
                                    "
                                    class="group relative flex items-center gap-3 rounded-lg border border-white/8 bg-white/4 px-3 py-2 transition hover:border-cyan-300/30 hover:bg-white/8">
                                    @if (str_starts_with($attachment['content_type'], 'image/'))
                                    <span
                                        class="relative block h-10 w-10 shrink-0 overflow-hidden rounded-md border border-white/10 bg-slate-950/60">
                                        <img src="{{ $attachment['url'] }}" alt="{{ $attachment['filename'] }}"
                                            loading="lazy" class="h-10 w-10 object-cover" />
                                        <span
                                            class="absolute inset-0 flex items-center justify-center rounded-md bg-slate-950/75 text-cyan-200 opacity-0 transition group-hover:opacity-100">
                                            <span class="material-symbols-outlined text-base">download</span>
                                        </span>
                                    </span>
                                    @else
                                    <span class="material-symbols-outlined shrink-0 text-cyan-300">description</span>
                                    @endif

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-bold text-white">
                                            {{ $attachment['filename'] }}
                                        </span>
                                        <span class="block truncate text-[11px] text-blue-100/50">
                                            {{ trim($attachment['size'] . ' · ' . $attachment['content_type'], ' ·') }}
                                        </span>
                                    </span>

                                    <span x-show="saving" x-cloak aria-hidden="true"
                                        class="absolute inset-0 z-10 flex items-center justify-center gap-2 rounded-lg bg-slate-950/85">
                                        <span
                                            class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-cyan-300/30 border-t-cyan-300"></span>
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-cyan-100"
                                            x-text="percent > 0 ? 'Saving ' + percent + '%' : 'Preparing download…'"></span>
                                    </span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <span class="inline-flex items-center gap-1 text-amber-300">
                            <span class="material-symbols-outlined text-sm">attach_file</span>
                            Embedded in the message body
                        </span>
                        @endif
                    </div>
                    @endif

                    @else
                    {{-- Address + Inbox --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-cyan-300">alternate_email</span>
                            <h2 class="text-xl font-extrabold text-white">Your temporary address</h2>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div
                            class="min-w-0 flex-1 break-all rounded-xl border {{ $this->has_mailbox ? 'border-cyan-300/25' : 'border-white/10' }} bg-slate-950/40 px-4 py-3 font-mono text-sm leading-6 select-all sm:text-base {{ $this->has_mailbox ? 'text-cyan-100' : 'text-blue-100/40' }}">
                            @if ($this->address !== '')
                            {{ $this->address }}
                            @elseif ($isCreating)
                            Creating your address...
                            @else
                            Address unavailable
                            @endif
                        </div>

                        <div class="shrink-0 gap-2 {{ $this->has_mailbox ? 'grid grid-cols-3 sm:flex sm:flex-wrap' : 'flex' }}">
                            @if ($this->has_mailbox)
                            <button type="button"
                                @click="navigator.clipboard.writeText(@js($this->address)); $dispatch('toast', { message: 'Address copied to clipboard', type: 'success' })"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/8 px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:border-cyan-400/30 hover:bg-white/12 sm:gap-2 sm:px-4 sm:py-3 sm:text-xs">
                                <span class="material-symbols-outlined text-sm sm:text-base">content_copy</span>
                                Copy
                            </button>

                            <button type="button"
                                x-on:click="confirmAlert({
                                    title: 'Replace this address?',
                                    message: 'The old mailbox will be deleted. Create a new address instead?',
                                    confirmText: 'Replace',
                                    danger: false,
                                }).then(confirmed => confirmed &amp;&amp; $wire.createMailbox())"
                                wire:loading.attr="disabled"
                                wire:target="createMailbox"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-white/15 bg-white/8 px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:border-cyan-400/30 hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60 sm:gap-2 sm:px-4 sm:py-3 sm:text-xs">
                                <span wire:loading.remove.flex wire:target="createMailbox"
                                    class="flex h-4 w-4 shrink-0 items-center justify-center">
                                    <span class="material-symbols-outlined text-sm sm:text-base">refresh</span>
                                </span>
                                <span wire:loading.flex wire:target="createMailbox"
                                    class="flex h-4 w-4 shrink-0 items-center justify-center">
                                    <span
                                        class="block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                </span>
                                New
                            </button>

                            <button type="button"
                                x-on:click="confirmAlert({
                                    title: 'Delete this mailbox?',
                                    message: 'This mailbox and all of its messages will be permanently removed.',
                                    confirmText: 'Delete',
                                    danger: true,
                                }).then(confirmed => confirmed &amp;&amp; $wire.deleteMailbox())"
                                wire:loading.attr="disabled"
                                wire:target="deleteMailbox"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-red-300/25 bg-red-400/10 px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-red-300 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 sm:gap-2 sm:px-4 sm:py-3 sm:text-xs">
                                <span class="material-symbols-outlined text-sm sm:text-base">delete</span>
                                Delete
                            </button>
                            @else
                            <button type="button" wire:click="createMailbox" wire:loading.attr="disabled"
                                @disabled($isCreating)
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove.flex wire:target="createMailbox"
                                    class="flex h-4 w-4 shrink-0 items-center justify-center">
                                    <span class="material-symbols-outlined text-base">refresh</span>
                                </span>

                                <span wire:loading.flex wire:target="createMailbox"
                                    class="flex h-4 w-4 shrink-0 items-center justify-center">
                                    <span
                                        class="block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                </span>
                                Retry
                            </button>
                            @endif
                        </div>
                    </div>

                    @if (session('temp_mail.created_at'))
                    <p class="mt-3 text-xs text-blue-100/45">
                        Created
                        {{ Carbon::parse(session('temp_mail.created_at'))->diffForHumans() }} — messages
                        arrive within seconds and refresh automatically.
                    </p>
                    @endif

                    {{-- Inbox --}}
                    <div
                        class="mt-4 rounded-xl border border-white/8 bg-slate-950/30 p-3 sm:mt-6 sm:p-5 lg:flex lg:flex-1 lg:flex-col">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-cyan-300 sm:text-[24px]">inbox</span>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-white sm:text-sm">Inbox</h3>

                                @if ($this->unread_count > 0)
                                <span
                                    class="rounded-full border border-cyan-300/30 bg-cyan-400/10 px-2 py-px text-[11px] font-black text-cyan-200">
                                    {{ $this->unread_count }} new
                                </span>
                                @endif
                            </div>

                            <button type="button"
                                wire:click="{{ $this->has_mailbox ? 'refreshInbox(true)' : 'createMailbox' }}"
                                wire:loading.attr="disabled"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-white/15 bg-white/8 px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-white transition hover:border-cyan-400/30 hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60 sm:gap-2 sm:px-3 sm:py-1.5">
                                <span wire:loading.remove.flex wire:target="refreshInbox, createMailbox"
                                    class="flex h-3.5 w-3.5 shrink-0 items-center justify-center sm:h-4 sm:w-4">
                                    <span class="material-symbols-outlined text-xs sm:text-sm">sync</span>
                                </span>
                                <span wire:loading.flex wire:target="refreshInbox, createMailbox"
                                    class="flex h-3.5 w-3.5 shrink-0 items-center justify-center sm:h-4 sm:w-4">
                                    <span
                                        class="block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white sm:h-4 sm:w-4"></span>
                                </span>
                                {{ $this->has_mailbox ? 'Refresh' : 'Retry' }}
                            </button>
                        </div>

                        @if ($inboxError)
                        <div
                            class="mt-3 flex items-start gap-2 rounded-lg border border-amber-300/20 bg-amber-400/8 px-3 py-2.5 text-xs leading-5 text-amber-100/85">
                            <span class="material-symbols-outlined mt-0.5 text-sm text-amber-300">cloud_off</span>
                            <p>{{ $inboxError }}</p>
                        </div>
                        @endif

                        @if ($messages === [])
                        <div class="flex flex-col items-center px-4 py-10 text-center lg:flex-1 lg:justify-center">
                            @if ($inboxError !== null)
                            <span class="material-symbols-outlined text-4xl text-blue-100/30">cloud_off</span>
                            <p class="mt-3 text-sm font-bold text-white">Inbox unavailable</p>
                            <p class="mt-1 max-w-sm text-xs leading-5 text-blue-100/55">
                                @if ($this->has_mailbox)
                                We could not load your messages right now. It retries on its own — or hit
                                refresh above.
                                @else
                                No mailbox is connected right now. Hit Retry above to create your address.
                                @endif
                            </p>
                            @elseif (! $hasLoadedInbox)
                            <span
                                class="h-6 w-6 animate-spin rounded-full border-2 border-cyan-300/30 border-t-cyan-200"></span>
                            <p class="mt-3 text-sm font-bold text-white">Connecting to your inbox...</p>
                            @else
                            <span class="material-symbols-outlined text-4xl text-blue-100/30 lg:text-6xl">mail_outline</span>
                            <p class="mt-3 text-sm font-bold text-white lg:mt-4 lg:text-xl">No emails yet</p>
                            <!-- <p class="mt-1 max-w-sm text-xs leading-5 text-blue-100/55">
                                Send a message to
                                <span class="font-semibold text-cyan-200">{{ $this->address }}</span> and it
                                will show up here automatically — usually within a few seconds.
                            </p> -->
                            @endif
                        </div>
                        @else
                        <ul class="mt-2 divide-y divide-white/8">
                            @foreach ($messages as $message)
                            <li wire:key="temp-mail-{{ $message['id'] }}">
                                <button type="button" wire:click="showMessage('{{ $message['id'] }}')"
                                    class="group flex w-full cursor-pointer items-start gap-3 px-1 py-3 text-left transition hover:bg-white/4">
                                    <span
                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $message['seen'] ? 'bg-white/15' : 'bg-cyan-400 shadow-lg shadow-cyan-500/40' }}"></span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-baseline justify-between gap-3">
                                            <span class="truncate text-sm font-bold text-white">
                                                {{ $message['from_name'] !== '' ? $message['from_name'] : $message['from'] }}
                                            </span>

                                            <span class="shrink-0 text-[11px] text-blue-100/45">
                                                {{ $message['created_at'] !== '' ? Carbon::parse($message['created_at'])->diffForHumans() : '' }}
                                            </span>
                                        </span>

                                        <span
                                            class="mt-0.5 block truncate text-sm {{ $message['seen'] ? 'font-medium text-blue-100/60' : 'font-bold text-cyan-100' }}">
                                            {{ $message['subject'] }}
                                        </span>

                                        <span class="mt-0.5 block truncate text-xs text-blue-100/50">
                                            {{ $message['intro'] }}
                                        </span>
                                    </span>

                                    @if ($message['has_attachments'])
                                    <span class="material-symbols-outlined mt-1 text-sm text-amber-300"
                                        title="Has attachments">attach_file</span>
                                    @endif

                                    <span
                                        class="material-symbols-outlined mt-1 text-base text-blue-100/30 transition group-hover:translate-x-0.5 group-hover:text-cyan-300">chevron_right</span>
                                </button>
                            </li>
                            @endforeach
                        </ul>

                        @if (count($messages) >= $this->messagesLimit() && ! $this->is_premium_user && $this->upgrade_plan)
                        <p class="mt-3 border-t border-white/8 pt-3 text-center text-[11px] leading-5 text-blue-100/50">
                            Showing the latest
                            <span class="font-bold text-blue-100/80">{{ $this->messagesLimit() }}</span>
                            messages. <span class="font-bold text-blue-100/80">{{ $this->upgrade_plan->name }}</span>
                            shows up to {{ $this->plan_limits['premium'] }}.
                        </p>
                        @endif
                        @endif
                    </div>
                    @endif
                </div>
            </section>

            {{-- How it works --}}
            <aside
                class="relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7 lg:col-span-4">
                <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-blue-400/5 via-transparent to-cyan-500/5">
                </div>

                <div class="relative">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-cyan-300">route</span>
                        <h3 class="text-lg font-extrabold text-white">How it works</h3>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">add_circle</span>
                            <div>
                                <p class="text-sm font-bold text-white">Address ready</p>
                                <p class="text-xs leading-5 text-blue-100/55">A fresh address is created
                                    the moment this page opens — nothing to fill in.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">mark_email_read</span>
                            <div>
                                <p class="text-sm font-bold text-white">Receive</p>
                                <p class="text-xs leading-5 text-blue-100/55">Incoming messages appear in
                                    the inbox within seconds — the page refreshes itself.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">visibility</span>
                            <div>
                                <p class="text-sm font-bold text-white">Read</p>
                                <p class="text-xs leading-5 text-blue-100/55">Open any message to read the
                                    full content, then delete what you no longer need.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-300">do_not_disturb_on</span>
                            <div>
                                <p class="text-sm font-bold text-white">Dispose</p>
                                <p class="text-xs leading-5 text-blue-100/55">Delete the mailbox when you
                                    are done. Nothing is tied to your account.</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 rounded-xl border border-white/10 bg-white/4 px-4 py-3 text-xs leading-5 text-blue-100/55">
                        <p class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-blue-100/75">
                            <span class="material-symbols-outlined text-sm text-cyan-300">schedule</span>
                            Lifetime
                        </p>
                        <p class="mt-1.5">
                            A mailbox lives as long as this browser session. Close the session or delete it
                            manually and the address stops receiving mail.
                        </p>
                    </div>

                    @if (! $this->is_premium_user && $this->upgrade_plan)
                    <div
                        class="mt-6 flex flex-col gap-3 rounded-xl border border-cyan-300/20 bg-cyan-400/8 px-4 py-3">
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5 text-sm text-cyan-300">workspace_premium</span>

                            <p class="text-xs leading-5 text-cyan-100/85">
                                Free plan shows the latest
                                <span class="font-bold">{{ $this->plan_limits['free'] }}</span> messages.
                                <span class="font-bold">{{ $this->upgrade_plan->name }}</span> raises it to
                                <span class="font-bold">{{ $this->plan_limits['premium'] }}</span>.
                            </p>
                        </div>

                        @auth
                        <a href="{{ route('client.tool-subscriptions.checkout', $this->upgrade_plan) }}" wire:navigate
                            class="shrink-0 cursor-pointer rounded-lg bg-linear-to-r from-cyan-500 to-blue-500 px-4 py-2 text-center text-[11px] font-black uppercase tracking-wider text-white transition hover:-translate-y-0.5">
                            Upgrade
                        </a>
                        @else
                        <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))"
                            class="shrink-0 cursor-pointer rounded-lg bg-linear-to-r from-cyan-500 to-blue-500 px-4 py-2 text-[11px] font-black uppercase tracking-wider text-white transition hover:-translate-y-0.5">
                            Login to Unlock
                        </button>
                        @endauth
                    </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
    window.tempMailDownload = async function(url, filename, onProgress) {
        const save = (blob) => {
            const href = URL.createObjectURL(blob);
            const anchor = document.createElement('a');

            anchor.href = href;
            anchor.download = filename || 'attachment';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();

            setTimeout(() => URL.revokeObjectURL(href), 60000);
        };

        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

            const type = response.headers.get('Content-Type') || 'application/octet-stream';

            if (!response.body || typeof response.body.getReader !== 'function') {
                save(await response.blob());

                if (onProgress) {
                    onProgress(100);
                }

                return true;
            }

            const total = parseInt(response.headers.get('Content-Length') || '0', 10) || 0;
            const reader = response.body.getReader();
            const chunks = [];
            let received = 0;

            while (true) {
                const {
                    done,
                    value
                } = await reader.read();

                if (done) {
                    break;
                }

                chunks.push(value);
                received += value.length;

                if (onProgress && total > 0) {
                    onProgress(Math.min(99, Math.round((received / total) * 100)));
                }
            }

            save(new Blob(chunks, {
                type
            }));

            if (onProgress) {
                onProgress(100);
            }

            return true;
        } catch (error) {
            return false;
        }
    };
</script>