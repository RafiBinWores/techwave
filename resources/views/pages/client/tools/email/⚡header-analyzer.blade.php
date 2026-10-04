<?php

use App\Services\EmailHeaderAnalyzerService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Email Header Analyzer')] class extends Component {
    public string $input = '';

    public ?array $report = null;

    public bool $isAnalyzing = false;

    public function analyze(EmailHeaderAnalyzerService $service): void
    {
        $this->validate([
            'input' => ['required', 'string'],
        ], [
            'input.required' => 'Paste the raw email headers to analyze.',
        ]);

        $this->isAnalyzing = true;
        $this->report = $service->analyze($this->input);
        $this->isAnalyzing = false;
        $this->resetValidation();

        if (! $this->report['valid']) {
            $this->dispatch('toast', message: 'No headers found — paste the raw header block (Show original / View source).', type: 'error');
        }
    }

    public function resetAnalysis(): void
    {
        $this->input = '';
        $this->report = null;
        $this->resetValidation();
    }

    public function verdictMeta(string $verdict): array
    {
        return match ($verdict) {
            EmailHeaderAnalyzerService::VERDICT_TRUSTED => [
                'label' => 'Trusted',
                'icon' => 'verified_user',
                'title' => 'text-emerald-300',
                'classes' => 'border-emerald-300/25 bg-emerald-400/10 text-emerald-300',
            ],
            EmailHeaderAnalyzerService::VERDICT_FLAGGED => [
                'label' => 'Flagged',
                'icon' => 'gpp_maybe',
                'title' => 'text-red-300',
                'classes' => 'border-red-300/25 bg-red-400/10 text-red-300',
            ],
            default => [
                'label' => 'Unverified',
                'icon' => 'help',
                'title' => 'text-amber-300',
                'classes' => 'border-amber-300/25 bg-amber-400/10 text-amber-300',
            ],
        };
    }

    public function statusMeta(string $status): array
    {
        return match ($status) {
            EmailHeaderAnalyzerService::STATUS_PASS => ['icon' => 'check_circle', 'label' => 'Pass', 'classes' => 'border-emerald-300/25 bg-emerald-400/10 text-emerald-300', 'icon_class' => 'text-emerald-300'],
            EmailHeaderAnalyzerService::STATUS_FAIL => ['icon' => 'cancel', 'label' => 'Fail', 'classes' => 'border-red-300/25 bg-red-400/10 text-red-300', 'icon_class' => 'text-red-300'],
            EmailHeaderAnalyzerService::STATUS_WARN => ['icon' => 'warning', 'label' => 'Warning', 'classes' => 'border-amber-300/25 bg-amber-400/10 text-amber-300', 'icon_class' => 'text-amber-300'],
            default => ['icon' => 'remove', 'label' => 'Skipped', 'classes' => 'border-blue-300/25 bg-blue-400/10 text-blue-300', 'icon_class' => 'text-blue-100/50'],
        };
    }

    public function hopTime(?int $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp)->format('M j, Y H:i:s');
    }

    public function formatDelay(int $seconds): string
    {
        return app(EmailHeaderAnalyzerService::class)->formatDelay($seconds);
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
                    Header Analyzer
                </span>
            </h1>

            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100/60 sm:text-lg">
                Paste raw email headers to see where the message came from, every server it passed through,
                and whether SPF, DKIM, and DMARC authenticated it.
            </p>
        </div>

        <div class="grid w-full grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Input Section --}}
            <section
                class="relative flex min-h-100 flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-8 lg:col-span-8">
                <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                </div>

                <div class="relative">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-cyan-300">manage_search</span>
                            <h2 class="text-xl font-extrabold text-white">Paste raw headers</h2>
                        </div>
                    </div>

                    <textarea wire:model="input" rows="6"
                        placeholder="Received: from mail.example.com (mail.example.com [203.0.113.5])&#10;    by mx.google.com with ESMTPS id 4XyZ&#10;    for &lt;you@example.com&gt;; Thu, 01 Jan 2026 10:00:01 +0000&#10;Authentication-Results: mx.google.com; spf=pass; dkim=pass; dmarc=pass&#10;From: Jane Doe &lt;jane@example.com&gt;&#10;Subject: Hello"
                        class="mt-4 w-full resize-y rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3 font-mono text-xs leading-6 text-white placeholder:text-blue-100/35 focus:border-cyan-300/40 focus:ring-0 focus:outline-none [scrollbar-width:thin] [scrollbar-color:rgba(255,255,255,0.18)_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar]:rounded-full [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-white/20 hover:[&::-webkit-scrollbar-thumb]:bg-white/35"></textarea>

                    @error('input')
                    <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-300">
                        <span class="material-symbols-outlined text-xs">error</span>
                        {{ $message }}
                    </p>
                    @enderror

                    <p class="mt-2 text-xs text-blue-100/45">
                        Gmail: <span class="font-semibold">Show original</span> &middot; Outlook:
                        <span class="font-semibold">View message source</span> &middot; Apple Mail:
                        <span class="font-semibold">Raw Source</span>. Paste everything from
                        <span class="font-semibold">Return-Path</span> down to the blank line.
                    </p>

                    {{-- Actions --}}
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <button type="button" wire:click="analyze" wire:loading.attr="disabled"
                            @disabled($isAnalyzing)
                            class="group relative flex flex-1 cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                            <span
                                class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>

                            <span wire:loading.remove wire:target="analyze" class="relative flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">travel_explore</span>
                                Analyze Headers
                            </span>

                            <span wire:loading wire:target="analyze" class="relative flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Analyzing...
                            </span>
                        </button>

                        @if ($input !== '' || $report !== null)
                        <button type="button" wire:click="resetAnalysis"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/8 px-6 py-3.5 text-sm font-bold text-white transition hover:border-cyan-400/30 hover:bg-white/12">
                            <span class="material-symbols-outlined text-base">refresh</span>
                            Clear
                        </button>
                        @endif
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
                        <h3 class="text-lg font-extrabold text-white">What we parse</h3>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">person</span>
                            <div>
                                <p class="text-sm font-bold text-white">Sender fields</p>
                                <p class="text-xs leading-5 text-blue-100/55">From, Return-Path, Subject, Date,
                                    and Reply-To at a glance.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">policy</span>
                            <div>
                                <p class="text-sm font-bold text-white">SPF, DKIM &amp; DMARC</p>
                                <p class="text-xs leading-5 text-blue-100/55">The receiving server's own
                                    authentication report, decoded.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-emerald-300">route</span>
                            <div>
                                <p class="text-sm font-bold text-white">Routing chain</p>
                                <p class="text-xs leading-5 text-blue-100/55">Every Received hop with servers,
                                    IPs, timestamps, and delays.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-300">flag</span>
                            <div>
                                <p class="text-sm font-bold text-white">Red flags</p>
                                <p class="text-xs leading-5 text-blue-100/55">Spoof indicators, envelope
                                    mismatches, and missing history.</p>
                            </div>
                        </div>
                    </div>

                    <!-- <div
                        class="mt-6 flex items-start gap-2 rounded-xl border border-amber-300/20 bg-amber-400/8 px-4 py-3 text-xs leading-5 text-amber-100/85">
                        <span class="material-symbols-outlined text-sm">info</span>
                        <p>
                            Headers are parsed in your browser session and nothing is stored. Auth results are
                            whatever the receiving server recorded at delivery time.
                        </p>
                    </div> -->
                </div>
            </aside>
        </div>

        {{-- Results --}}
        @if ($report !== null && $report['valid'])
        @php $verdictMeta = $this->verdictMeta($report['verdict']); @endphp

        <div class="mt-10 w-full">
            {{-- Verdict banner --}}
            <div
                class="relative overflow-hidden rounded-2xl border {{ $verdictMeta['classes'] }} p-4 shadow-[0_24px_80px_rgba(0,0,0,0.25)]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <!-- <span class="material-symbols-outlined text-4xl sm:text-5xl">{{ $verdictMeta['icon'] }}</span> -->

                        <div>
                            <!-- <p class="text-[11px] font-bold uppercase tracking-[0.22em] opacity-70">
                                Header verdict
                            </p> -->

                            <h2 class="text-2xl font-extrabold sm:text-3xl {{ $verdictMeta['title'] }}">
                                {{ $verdictMeta['label'] }}
                            </h2>

                            <p class="mt-1 max-w-2xl text-sm leading-6 text-white/75">
                                {{ $report['summary'] }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="rounded-full border border-white/15 bg-white/8 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-white/70">
                        {{ $report['hop_count'] }} hop{{ $report['hop_count'] === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>

            {{-- Authentication results --}}
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($report['auth'] as $check)
                @php $meta = $this->statusMeta($check['status']); @endphp

                <article
                    class="relative overflow-hidden rounded-xl border border-white/10 bg-white/6 p-4 shadow-[0_16px_40px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
                    <div class="relative flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-base {{ $meta['icon_class'] }}">{{ $meta['icon'] }}</span>
                            <p class="text-sm font-extrabold uppercase tracking-wider text-white">{{ $check['label'] }}</p>
                        </div>

                        <span class="rounded-full border px-2 py-px text-[10px] font-bold uppercase tracking-wider {{ $meta['classes'] }}">
                            {{ $meta['label'] }}
                        </span>
                    </div>

                    <p class="relative mt-2 text-xs leading-5 text-blue-100/55">{{ $check['detail'] }}</p>
                </article>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-8 lg:grid-cols-2">
                {{-- Message fields --}}
                <section
                    class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl">
                    <div class="relative flex items-center gap-2">
                        <span class="material-symbols-outlined text-cyan-300">mail</span>
                        <h3 class="text-lg font-extrabold text-white">Message fields</h3>
                    </div>

                    <div class="relative mt-4 space-y-1 rounded-xl border border-white/8 bg-slate-950/35 px-4 py-3">
                        @foreach (['from' => 'From', 'to' => 'To', 'cc' => 'Cc', 'subject' => 'Subject', 'date' => 'Date', 'return-path' => 'Return-Path', 'reply-to' => 'Reply-To', 'message-id' => 'Message-ID'] as $key => $label)
                        <div class="flex items-start gap-3 py-1.5">
                            <span class="w-24 shrink-0 text-[10px] font-bold uppercase tracking-wider text-blue-100/45">{{ $label }}</span>

                            @if ($report['fields'][$key] ?? null)
                            <span class="min-w-0 flex-1 break-all text-xs leading-5 text-white/85">{{ $report['fields'][$key] }}</span>
                            @else
                            <span class="text-xs leading-5 text-blue-100/35">Not present</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>

                {{-- Red flags --}}
                <section
                    class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl">
                    <div class="relative flex items-center gap-2">
                        <span class="material-symbols-outlined text-cyan-300">flag</span>
                        <h3 class="text-lg font-extrabold text-white">Red flags</h3>
                    </div>

                    <div class="relative mt-4 space-y-3">
                        @forelse ($report['flags'] as $flag)
                        @php $meta = $this->statusMeta($flag['status']); @endphp

                        <div class="flex items-start gap-3 rounded-xl border border-white/8 bg-white/[0.035] px-4 py-3">
                            <span class="material-symbols-outlined mt-0.5 text-base {{ $meta['icon_class'] }}">{{ $meta['icon'] }}</span>

                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wider text-white">{{ $flag['label'] }}</p>
                                <p class="mt-0.5 text-xs leading-5 text-blue-100/55">{{ $flag['detail'] }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="rounded-xl border border-dashed border-white/10 px-4 py-6 text-center text-xs text-blue-100/45">
                            No flags found in these headers.
                        </p>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- Routing chain --}}
            @if ($report['chain'] !== [])
            <section
                class="relative mt-8 overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7">
                <div class="relative flex items-center gap-2">
                    <span class="material-symbols-outlined text-cyan-300">route</span>
                    <h3 class="text-lg font-extrabold text-white">Routing chain</h3>
                    <span
                        class="ml-auto rounded-full border border-white/10 bg-white/6 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-100/60">
                        Newest hop first
                    </span>
                </div>

                <div class="relative mt-5 space-y-3">
                    @foreach ($report['chain'] as $hop)
                    <div
                        class="relative flex flex-col gap-2 rounded-xl border border-white/8 bg-white/[0.035] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-cyan-300/30 bg-cyan-400/10 text-[11px] font-black text-cyan-200">{{ $hop['index'] }}</span>

                            <div class="min-w-0">
                                <p class="break-all text-xs font-semibold leading-5 text-white">
                                    @if ($hop['from'])
                                    <span class="text-cyan-200">{{ $hop['from'] }}</span>
                                    @if ($hop['ip'])<span class="text-blue-100/50"> [{{ $hop['ip'] }}]</span>@endif
                                    @else
                                    <span class="text-blue-100/50">unknown origin</span>
                                    @endif

                                    <span class="material-symbols-outlined align-middle text-sm text-blue-100/40">trending_flat</span>

                                    @if ($hop['by'])
                                    <span class="text-cyan-200">{{ $hop['by'] }}</span>
                                    @else
                                    <span class="text-blue-100/50">unknown relay</span>
                                    @endif
                                </p>

                                <p class="mt-0.5 text-[11px] leading-4 text-blue-100/45">
                                    @if ($hop['with'])<span class="font-semibold">{{ $hop['with'] }}</span> &middot; @endif
                                    @if ($hop['for'])for {{ $hop['for'] }} &middot; @endif
                                    @if ($hop['at'] !== null){{ $this->hopTime($hop['at']) }}@else<em>timestamp not parsed</em>@endif
                                </p>
                            </div>
                        </div>

                        @if ($hop['delay'] !== null)
                        <span
                            class="shrink-0 self-start rounded-full border {{ $hop['delay'] >= 300 ? 'border-amber-300/25 bg-amber-400/10 text-amber-300' : 'border-white/12 bg-white/6 text-blue-100/60' }} px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider sm:self-center">
                            +{{ $this->formatDelay($hop['delay']) }}
                        </span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- Headers found --}}
            @if ($report['headers_found'] !== [])
            <section x-data="{ openHeader: null }"
                class="relative mt-8 overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7">
                <div class="relative flex items-center gap-2">
                    <span class="material-symbols-outlined text-cyan-300">list_alt</span>
                    <h3 class="text-lg font-extrabold text-white">Headers Found</h3>
                    <span
                        class="ml-auto rounded-full border border-white/10 bg-white/6 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-100/60">
                        {{ count($report['headers_found']) }} unique
                    </span>
                </div>

                <div class="relative mt-5 grid grid-cols-1 items-start gap-1.5 md:grid-cols-2">
                    @foreach ($report['headers_found'] as $index => $header)
                    <div class="overflow-hidden rounded-lg border border-white/8 bg-white/[0.035]">
                        <button type="button"
                            @click="openHeader = openHeader === {{ $index }} ? null : {{ $index }}"
                            :aria-expanded="openHeader === {{ $index }}"
                            class="flex w-full cursor-pointer items-center gap-3 px-3 py-2 text-left transition hover:bg-cyan-400/8">
                            <span
                                class="shrink-0 font-mono text-[11px] font-bold text-cyan-200">{{ $header['name'] }}</span>

                            @if ($header['count'] > 1)
                            <span
                                class="shrink-0 rounded-full border border-white/12 bg-white/6 px-1.5 text-[10px] font-bold text-blue-100/60">&times;{{ $header['count'] }}</span>
                            @endif

                            <span class="min-w-0 flex-1 truncate font-mono text-[11px] text-blue-100/45">{{ $header['values'][0] ?? '' }}</span>

                            <span class="material-symbols-outlined shrink-0 text-sm text-blue-100/40 transition-transform duration-200"
                                :class="openHeader === {{ $index }} ? 'rotate-180 text-cyan-300' : ''">
                                expand_more
                            </span>
                        </button>

                        <div x-show="openHeader === {{ $index }}" x-transition x-cloak
                            class="border-t border-white/8 bg-slate-950/30 px-3 py-2.5">
                            @foreach ($header['values'] as $value)
                            <p class="whitespace-pre-wrap break-all font-mono text-[11px] leading-5 text-white/80">{{ $value }}</p>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            <div
                class="mt-6 flex items-start gap-2 rounded-2xl border border-white/10 bg-white/4 px-5 py-4 text-xs leading-5 text-blue-100/55">
                <span class="material-symbols-outlined text-sm text-cyan-300">shield_lock</span>
                <p>
                    Parsed server-side from the text you pasted and nothing is stored. Results reflect what the
                    receiving servers recorded at delivery time — forwarded messages keep the original hop
                    history, and stripped headers simply show as missing.
                </p>
            </div>
        </div>
        @endif
    </div>
</section>
