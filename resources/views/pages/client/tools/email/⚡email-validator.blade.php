<?php

use App\Models\ToolCategory;
use App\Models\ToolPlan;
use App\Services\EmailValidityService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Email Validity Checker')] class extends Component {
    public string $input = '';

    public array $results = [];

    public bool $hasChecked = false;

    private ?ToolCategory $category = null;

    private const FALLBACK_MAX_EMAILS = 5;

    public function boot(): void
    {
        $this->category = ToolCategory::query()->where('slug', 'email-tools')->first();
    }

    public function maxEmails(): int
    {
        if (! $this->category) {
            return self::FALLBACK_MAX_EMAILS;
        }

        if (! auth()->check()) {
            return $this->category->free_max_file_upload ?? self::FALLBACK_MAX_EMAILS;
        }

        return auth()->user()->maxFileUploadFor($this->category);
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

    public function check(EmailValidityService $service): void
    {
        set_time_limit(300);

        $maxEmails = $this->maxEmails();

        $this->validate([
            'input' => ['required', 'string', 'max:20000'],
        ], [
            'input.required' => 'Paste at least one email address to check.',
        ]);

        $emails = $this->parseEmails();

        if ($emails === []) {
            $this->dispatch('toast', message: 'Paste at least one email address to check.', type: 'error');

            return;
        }

        if (count($emails) > $maxEmails) {
            $message = 'You can check up to ' . $maxEmails . ' email' . ($maxEmails === 1 ? '' : 's') . ' at a time.';

            if (! $this->is_premium_user && ($plan = $this->upgrade_plan)) {
                $message = 'Free plan allows ' . $maxEmails . ' emails per run. Upgrade to check up to '
                    . $plan->max_file_upload . ' emails at a time.';
            }

            $this->dispatch('toast', message: $message, type: 'error');

            return;
        }

        $this->results = array_map(
            fn(string $email) => $service->check($email),
            $emails,
        );

        $this->hasChecked = true;
    }

    public function resetChecker(): void
    {
        $this->input = '';
        $this->results = [];
        $this->hasChecked = false;

        $this->resetValidation();
    }

    public function getEmailCountProperty(): int
    {
        return count($this->parseEmails());
    }

    public function getStatsProperty(): array
    {
        $stats = [
            'total' => count($this->results),
            EmailValidityService::STATUS_VALID => 0,
            EmailValidityService::STATUS_INVALID => 0,
            EmailValidityService::STATUS_RISKY => 0,
            EmailValidityService::STATUS_UNKNOWN => 0,
        ];

        foreach ($this->results as $result) {
            if (isset($stats[$result['status']])) {
                $stats[$result['status']]++;
            }
        }

        return $stats;
    }

    public function statusMeta(string $status): array
    {
        return match ($status) {
            EmailValidityService::STATUS_VALID => [
                'label' => 'Deliverable',
                'icon' => 'verified',
                'classes' => 'border-emerald-300/25 bg-emerald-400/10 text-emerald-300',
            ],
            EmailValidityService::STATUS_INVALID => [
                'label' => 'Undeliverable',
                'icon' => 'cancel',
                'classes' => 'border-red-300/25 bg-red-400/10 text-red-300',
            ],
            EmailValidityService::STATUS_RISKY => [
                'label' => 'Risky',
                'icon' => 'warning',
                'classes' => 'border-amber-300/25 bg-amber-400/10 text-amber-300',
            ],
            default => [
                'label' => 'Unknown',
                'icon' => 'help',
                'classes' => 'border-blue-300/25 bg-blue-400/10 text-blue-300',
            ],
        };
    }

    public function checkMeta(string $status): array
    {
        return match ($status) {
            'pass' => ['icon' => 'check_circle', 'classes' => 'text-emerald-300'],
            'fail' => ['icon' => 'cancel', 'classes' => 'text-red-300'],
            'warn' => ['icon' => 'warning', 'classes' => 'text-amber-300'],
            default => ['icon' => 'remove', 'classes' => 'text-blue-100/40'],
        };
    }

    private function parseEmails(): array
    {
        $parts = preg_split('/[\s,;]+/', trim($this->input), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($parts));
    }
};
?>

<section class="min-h-screen text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col items-center px-4 pb-24 pt-10 sm:px-6 lg:px-8">

        {{-- Hero Header --}}
        <div class="mb-10 text-center">
            <h1 class="text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl">
                Email
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text italic text-transparent pr-1.5">
                    Validity Checker
                </span>
            </h1>

            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100/60 sm:text-lg">
                Verify if an email address is real and working: syntax, domain mail servers, and live mailbox
                confirmation.
            </p>
        </div>

        {{-- Progress Stepper --}}
        <div class="mb-12 hidden flex-wrap items-center justify-center gap-3 sm:gap-4 lg:flex">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border {{ $hasChecked ? 'border-white/15 bg-white/5 text-blue-100/60' : 'border-cyan-300/40 bg-cyan-400/15 text-sm font-bold text-cyan-200 shadow-lg shadow-cyan-500/20' }} text-sm font-bold">
                    1
                </div>
                <span class="text-xs font-bold tracking-[0.22em] {{ $hasChecked ? 'text-blue-100/50' : 'text-white' }}">PASTE</span>
            </div>

            <div class="hidden h-px w-10 bg-white/15 sm:block"></div>

            <div class="flex items-center gap-3 opacity-70">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-white/15 bg-white/5 text-sm font-bold text-blue-100/60">
                    2
                </div>
                <span class="text-xs font-bold tracking-[0.22em] text-blue-100/50">CHECK</span>
            </div>

            <div class="hidden h-px w-10 bg-white/15 sm:block"></div>

            <div class="flex items-center gap-3 {{ $hasChecked ? '' : 'opacity-70' }}">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border {{ $hasChecked ? 'border-cyan-300/40 bg-cyan-400/15 text-cyan-200 shadow-lg shadow-cyan-500/20' : 'border-white/15 bg-white/5 text-blue-100/60' }} text-sm font-bold">
                    3
                </div>
                <span class="text-xs font-bold tracking-[0.22em] {{ $hasChecked ? 'text-white' : 'text-blue-100/50' }}">RESULTS</span>
            </div>
        </div>

        <div class="grid w-full grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Input Section --}}
            <section
                class="relative flex min-h-100 flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-8 lg:col-span-8">
                <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                </div>

                <div class="relative">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-cyan-300">alternate_email</span>
                            <h2 class="text-xl font-extrabold text-white">Paste email addresses</h2>
                        </div>

                        <span class="rounded-full border border-white/10 bg-white/6 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-100/60">
                            {{ $this->email_count }} / {{ $this->maxEmails() }} emails
                        </span>
                    </div>

                    <textarea wire:model="input" rows="7"
                        placeholder="jane@gmail.com&#10;support@company.com&#10;not-an-email"
                        class="mt-4 w-full resize-y rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm leading-6 text-white placeholder:text-blue-100/35 focus:border-cyan-300/40 focus:ring-0 focus:outline-none"></textarea>

                    @error('input')
                    <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-300">
                        <span class="material-symbols-outlined text-sm">error</span>
                        {{ $message }}
                    </p>
                    @enderror

                    <p class="mt-2 text-xs text-blue-100/45">
                        Separate multiple addresses with a comma, space, or new line. Maximum
                        {{ $this->maxEmails() }} per run.
                    </p>

                    {{-- Plan limit --}}
                    @if ($this->is_premium_user)
                    <div
                        class="mt-5 flex items-start gap-2 rounded-xl border border-emerald-300/20 bg-emerald-400/8 px-4 py-3 text-xs leading-5 text-emerald-200/90">
                        <span class="material-symbols-outlined mt-0.5 text-sm text-emerald-300">workspace_premium</span>
                        <p>
                            Premium active — you can check up to
                            <span class="font-bold">{{ $this->maxEmails() }}</span> emails at a time.
                        </p>
                    </div>

                    @elseif ($this->upgrade_plan)
                    <div
                        class="mt-5 flex flex-col gap-3 rounded-xl border border-cyan-300/20 bg-cyan-400/8 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5 text-sm text-cyan-300">workspace_premium</span>

                            <p class="text-xs leading-5 text-cyan-100/85">
                                Free plan allows <span class="font-bold">{{ $this->maxEmails() }}</span> emails per
                                run. <span class="font-bold">{{ $this->upgrade_plan->name }}</span> raises it to
                                <span class="font-bold">{{ $this->upgrade_plan->max_file_upload }}</span> emails at a
                                time.
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

                    {{-- Deep verification notice --}}
                    <!-- <div
                        class="mt-5 flex flex-col gap-4 rounded-xl border border-white/10 bg-white/4 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined mt-0.5 text-cyan-300">network_intelligence</span>

                            <div>
                                <p class="text-sm font-bold text-white">Deep SMTP verification</p>
                                <p class="mt-0.5 text-xs leading-5 text-blue-100/55">
                                    Connects to the mail server to confirm the mailbox really exists. This runs on
                                    every check and can take a few seconds per email.
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-cyan-300/30 bg-cyan-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-cyan-300">
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            Always on
                        </span>
                    </div> -->

                    {{-- Actions --}}
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <button type="button" wire:click="check" wire:loading.attr="disabled"
                            class="group relative flex flex-1 cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                            <span
                                class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>

                            <span wire:loading.remove wire:target="check" class="relative flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">fact_check</span>
                                Check Email{{ $this->email_count > 1 ? 's' : '' }}
                            </span>

                            <span wire:loading wire:target="check" class="relative flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Verifying...
                            </span>
                        </button>

                        @if ($hasChecked || $input !== '')
                        <button type="button" wire:click="resetChecker"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/8 px-6 py-3.5 text-sm font-bold text-white transition hover:border-cyan-400/30 hover:bg-white/12">
                            <span class="material-symbols-outlined text-base">refresh</span>
                            Clear
                        </button>
                        @endif
                    </div>

                    <div wire:loading wire:target="check"
                        class="mt-4 flex items-start gap-2 rounded-xl border border-cyan-300/20 bg-cyan-400/8 px-4 py-3 text-xs leading-5 text-cyan-100/85">
                        <span class="material-symbols-outlined text-sm">hourglass_top</span>
                        <p>
                            Probing mail servers... Deep checks open a real SMTP conversation and can take a few
                            seconds per address. If port 25 is blocked on this server, results fall back to
                            domain-level checks.
                        </p>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <aside
                class="relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7 lg:col-span-4">
                <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-blue-400/5 via-transparent to-cyan-500/5">
                </div>

                <div class="relative">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-cyan-300">insights</span>
                        <h3 class="text-lg font-extrabold text-white">What we check</h3>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">spellcheck</span>
                            <div>
                                <p class="text-sm font-bold text-white">Format</p>
                                <p class="text-xs leading-5 text-blue-100/55">RFC-compliant syntax and length rules.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">dns</span>
                            <div>
                                <p class="text-sm font-bold text-white">Mail servers (MX)</p>
                                <p class="text-xs leading-5 text-blue-100/55">The domain must publish a server that accepts mail.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">mark_email_read</span>
                            <div>
                                <p class="text-sm font-bold text-white">Mailbox existence</p>
                                <p class="text-xs leading-5 text-blue-100/55">Live SMTP probe asks the server if the mailbox is real, with catch-all detection.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-300">do_not_disturb_on</span>
                            <div>
                                <p class="text-sm font-bold text-white">Quality flags</p>
                                <p class="text-xs leading-5 text-blue-100/55">Detects disposable domains and role inboxes like info@ or support@.</p>
                            </div>
                        </div>
                    </div>

                    <!-- <div
                        class="mt-6 flex items-start gap-2 rounded-xl border border-amber-300/20 bg-amber-400/8 px-4 py-3 text-xs leading-5 text-amber-100/85">
                        <span class="material-symbols-outlined text-sm">info</span>
                        <p>
                            Some providers (like Gmail) never reveal whether a mailbox exists. Those show as
                            <span class="font-bold">Unknown</span>, not invalid.
                        </p>
                    </div> -->
                </div>
            </aside>
        </div>

        {{-- Results --}}
        @if ($hasChecked)
        <div class="mt-10 w-full">
            {{-- Summary --}}
            <div class="mb-5 flex flex-wrap items-center gap-2.5">
                <span class="mr-1 text-[11px] font-bold uppercase tracking-[0.22em] text-blue-100/45">Results</span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-white/12 bg-white/6 px-3 py-1 text-[11px] font-bold text-white">
                    <span class="material-symbols-outlined text-sm text-cyan-300">list_alt</span>
                    {{ $this->stats['total'] }} checked
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/25 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-300">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    {{ $this->stats['valid'] }} deliverable
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-red-300/25 bg-red-400/10 px-3 py-1 text-[11px] font-bold text-red-300">
                    <span class="material-symbols-outlined text-sm">cancel</span>
                    {{ $this->stats['invalid'] }} undeliverable
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-300/25 bg-amber-400/10 px-3 py-1 text-[11px] font-bold text-amber-300">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    {{ $this->stats['risky'] }} risky
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-blue-300/25 bg-blue-400/10 px-3 py-1 text-[11px] font-bold text-blue-300">
                    <span class="material-symbols-outlined text-sm">help</span>
                    {{ $this->stats['unknown'] }} unknown
                </span>
            </div>

            {{-- Result cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($results as $result)
                @php $meta = $this->statusMeta($result['status']); @endphp

                <article
                    class="group relative overflow-hidden rounded-xl border border-white/10 bg-white/6 p-4 shadow-[0_16px_40px_rgba(0,0,0,0.22)] backdrop-blur-2xl transition duration-300 hover:border-cyan-300/30">
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.06] bg-[linear-gradient(rgba(255,255,255,.18)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.18)_1px,transparent_1px)] bg-size-[32px_32px]">
                    </div>

                    <div class="relative flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-bold text-white">{{ $result['email'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-blue-100/55">{{ $result['summary'] }}</p>
                        </div>

                        {{-- <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider {{ $meta['classes'] }}">
                            <span class="material-symbols-outlined text-sm">{{ $meta['icon'] }}</span>
                            {{ $meta['label'] }}
                        </span> --}}
                    </div>

                    {{-- Classification --}}
                    <div class="relative mt-3 space-y-1 rounded-lg border border-white/8 bg-slate-950/35 px-3 py-2">
                        <div class="flex items-start gap-2">
                            <span
                                class="w-24 shrink-0 text-[10px] font-bold uppercase tracking-wider text-blue-100/45">Classification</span>

                            <span
                                class="rounded-full border px-2 py-px text-[10px] font-bold  tracking-wider {{ $meta['classes'] }}">
                                {{ $result['classification']['label'] }}
                            </span>
                        </div>

                        <div class="flex items-start gap-2">
                            <span
                                class="w-24 shrink-0 text-[10px] font-bold uppercase tracking-wider text-blue-100/45">Status</span>
                            <span class="text-[11px] leading-4 text-blue-100/70">{{ $result['classification']['text'] }}</span>
                        </div>

                        <div class="flex items-start gap-2">
                            <span
                                class="w-24 shrink-0 text-[10px] font-bold uppercase tracking-wider text-blue-100/45">Status code</span>
                            <span class="text-[11px] font-semibold text-white"
                                title="Machine-readable outcome of the verification pipeline.">{{ $result['classification']['code'] }}</span>
                        </div>
                    </div>

                    @php
                    $gridChecks = array_slice($result['checks'], 0, 4);
                    $fullWidthChecks = array_slice($result['checks'], 4);
                    @endphp

                    <div class="relative mt-4 grid grid-cols-2 gap-2">
                        @foreach ($gridChecks as $check)
                        @php $checkMeta = $this->checkMeta($check['status']); @endphp

                        <div
                            class="flex items-start gap-3 rounded-xl border border-white/8 bg-white/[0.035] px-3.5 py-2.5">
                            <span class="material-symbols-outlined mt-0.5 text-base {{ $checkMeta['classes'] }}">
                                {{ $checkMeta['icon'] }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-wider text-white">{{ $check['label'] }}</p>
                                <p class="mt-0.5 text-xs leading-5 text-blue-100/55">{{ $check['detail'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @foreach ($fullWidthChecks as $check)
                    @php $checkMeta = $this->checkMeta($check['status']); @endphp

                    <div
                        class="relative mt-2 flex items-start gap-3 rounded-xl border border-white/8 bg-white/[0.035] px-3.5 py-2.5">
                        <span class="material-symbols-outlined mt-0.5 text-base {{ $checkMeta['classes'] }}">
                            {{ $checkMeta['icon'] }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-white">{{ $check['label'] }}</p>
                            <p class="mt-0.5 text-xs leading-5 text-blue-100/55">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </article>
                @endforeach
            </div>

            <div
                class="mt-6 flex items-start gap-2 rounded-2xl border border-white/10 bg-white/4 px-5 py-4 text-xs leading-5 text-blue-100/55">
                <span class="material-symbols-outlined text-sm text-cyan-300">shield_lock</span>
                <p>
                    Checks run server-side and nothing is stored. Results are a best-effort diagnosis: some mail
                    servers greylist, block port 25, or hide mailbox existence by design.
                </p>
            </div>
        </div>
        @endif
    </div>
</section>