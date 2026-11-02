<?php

use App\Models\SeoReport;
use App\Support\Seo\AuditRunner;
use App\Support\Seo\DailyQuota;
use App\Support\Seo\Entitlements;
use App\Support\Seo\ReportPresenter;
use App\Support\Seo\ReportScores;
use App\Support\Seo\SeoException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('SEO Checker')] class extends Component {
    use WithPagination;

    public string $url = '';
    #[Locked]
    public ?string $resultKey = null;

    /** @var array<string> */
    public array $selectedCategories = ['Metadata', 'Content', 'Others', 'Structure', 'Technical'];

    #[Computed]
    public function premium(): bool
    {
        return app(Entitlements::class)->premium(auth()->user());
    }

    #[Computed]
    public function dailyLimit(): int
    {
        return app(Entitlements::class)->dailyLimit(auth()->user());
    }

    #[Computed]
    public function freeDailyLimit(): int
    {
        return app(Entitlements::class)->freeDailyLimit();
    }

    #[Computed]
    public function premiumDailyLimit(): ?int
    {
        return $this->premium ? $this->dailyLimit : app(Entitlements::class)->upgradePlan()?->max_file_upload;
    }

    #[Computed]
    public function remaining(): int
    {
        return app(DailyQuota::class)->remaining(auth()->user(), request()->ip() ?? 'unknown');
    }

    #[Computed]
    public function upgradeUrl(): string
    {
        $plan = app(Entitlements::class)->upgradePlan();
        return $plan ? route('client.tool-subscriptions.checkout', $plan) : route('account.tool-subscriptions');
    }

    #[Computed]
    public function report(): ?array
    {
        $cached = $this->resultKey ? Cache::get('seo:result:' . $this->resultKey) : null;
        if (!$cached || $cached['owner'] !== $this->owner()) {
            return null;
        }
        $premium = app(Entitlements::class)->premium(auth()->user());
        $report = app(ReportPresenter::class)->present($cached['payload'], $premium);
        if (!$premium) {
            unset($report['report_id']);
        }
        return $report;
    }

    #[Computed]
    public function history(): LengthAwarePaginator
    {
        $reports = $this->premium
            ? SeoReport::where('user_id', auth()->id())
                ->where('created_at', '>=', now()->subDays(config('seo.retention_days', 90)))
                ->latest('id')
                ->limit(config('seo.history_limit', 50))
                ->get(['id', 'final_url', 'score', 'created_at'])
            : collect();
        $page = max(1, (int) $this->getPage());
        return new LengthAwarePaginator($reports->forPage($page, 10)->values(), $reports->count(), 10, $page, ['path' => request()->url()]);
    }

    public function analyze(): void
    {
        $this->url = trim($this->url);
        if ($this->url !== '' && !preg_match('/^[a-z][a-z0-9+.-]*:/i', $this->url)) {
            $this->url = 'https://' . $this->url;
        }

        $this->validate([
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'selectedCategories' => ['required', 'array', 'min:1'],
            'selectedCategories.*' => ['string', Rule::in(ReportScores::CATEGORIES)],
        ], [
            'selectedCategories.required' => 'Select at least one scoring category.',
            'selectedCategories.min' => 'Select at least one scoring category.',
        ]);
        $actor = auth()->check() ? 'user:' . auth()->id() : 'ip:' . hash_hmac('sha256', request()->ip() ?? 'unknown', config('app.key'));
        $lock = Cache::lock('seo:processing:' . $actor, 30);
        if (!$lock->get()) {
            $this->addError('url', 'A check is already running. Please wait for it to finish.');
            return;
        }
        try {
            if (RateLimiter::tooManyAttempts('seo:throttle:' . $actor, 1)) {
                throw new SeoException('Please wait a few seconds before starting another check.');
            }
            RateLimiter::hit('seo:throttle:' . $actor, 5);
            $this->resultKey = null;
            unset($this->report);
            $report = app(AuditRunner::class)->run(
                trim($this->url),
                auth()->user(),
                request()->ip() ?? 'unknown',
                array_values(array_unique($this->selectedCategories)),
            );
            $this->remember($report);
            $this->resetPage();
        } catch (SeoException $exception) {
            $this->addError('url', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('url', 'We could not complete this check. Please try again later.');
        } finally {
            $lock->release();
            unset($this->remaining, $this->history);
        }
    }

    public function viewReport(int $id): void
    {
        $saved = $this->ownedReport($id);
        $this->url = $saved->final_url;
        $this->remember(app(ReportPresenter::class)->present($saved->payload + ['report_id' => $saved->id], true));
    }

    public function deleteReport(int $id): void
    {
        $saved = $this->ownedReport($id);
        if (($this->report['report_id'] ?? null) === $id) {
            Cache::forget('seo:result:' . $this->resultKey);
            $this->resultKey = null;
            unset($this->report);
        }
        $saved->delete();
        unset($this->history);
    }

    private function ownedReport(int $id): SeoReport
    {
        abort_unless(auth()->check() && app(Entitlements::class)->premium(auth()->user()), 403);
        return SeoReport::where('user_id', auth()->id())->where('created_at', '>=', now()->subDays(config('seo.retention_days', 90)))->findOrFail($id);
    }

    private function remember(array $report): void
    {
        if ($this->resultKey) {
            Cache::forget('seo:result:' . $this->resultKey);
        }
        $this->resultKey = (string) Str::uuid();
        Cache::put('seo:result:' . $this->resultKey, ['owner' => $this->owner(), 'premium' => $this->premium, 'payload' => $report], now()->addHour());
        unset($this->report);
    }

    private function owner(): string
    {
        return hash_hmac('sha256', session()->getId() . '|' . (auth()->id() ?? 'guest'), config('app.key'));
    }
};
?>

@push('meta')
    <meta name="description"
        content="Check your page's SEO score, metadata, headings, images and technical setup. Get prioritized recommendations with three free checks every day.">
@endpush

<section class="mx-auto min-h-screen w-full max-w-7xl px-4 pt-10 pb-24 text-white sm:px-6 lg:px-8">
    <header class="mx-auto mb-10 max-w-3xl text-center">

        <h1 class="mt-4 text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl">SEO <span
                class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text pr-1.5 text-transparent italic">Checker</span>
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100/60 sm:text-lg">Understand your page, inspect
            the evidence, and fix what matters. A detailed SEO report from metadata to server configuration.</p>
    </header>
    <div
        class="rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7 [&_h2]:text-lg [&_h2]:font-extrabold">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold">Check Web Page</h2>
            <span
                class="inline-flex items-center gap-2 rounded-xl border border-cyan-300/25 bg-cyan-400/10 px-3 py-1 text-xs font-bold text-cyan-200"><span
                    class="material-symbols-outlined text-base"
                    aria-hidden="true">{{ $this->premium ? 'workspace_premium' : 'search_check' }}</span><strong
                    class="font-extrabold text-white">{{ $this->remaining }}</strong> of {{ $this->dailyLimit }} checks
                left today</span>
        </div>
        <form wire:submit="analyze">
            <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <label for="seo-url" class="text-sm leading-7 text-blue-100/60">Page URL</label>
                <span id="seo-usage" class="text-[10px] leading-4 text-blue-100/45">Resets {{ app(DailyQuota::class)->resetAt()->format('M j, H:i T') }}</span>
            </div>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <input id="seo-url" type="text" inputmode="url" wire:model="url"
                    placeholder="https://example.com/your-page" required maxlength="2048"
                    class="h-11 w-full min-w-0 rounded-xl border border-white/15 bg-slate-800 px-3.5 py-2.5 text-sm text-white placeholder:text-blue-100/35 transition focus:border-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-300/20"
                    aria-describedby="seo-usage seo-error" autocomplete="url">
                <button type="submit" wire:loading.attr="disabled" wire:target="analyze" @disabled($this->remaining === 0)
                    class="group relative flex h-11 shrink-0 cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-2.5 text-sm font-bold tracking-wider text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300 disabled:cursor-not-allowed disabled:opacity-80 sm:min-w-60">
                    <span aria-hidden="true"
                        class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                    <span wire:loading.remove wire:target="analyze"
                        class="relative flex items-center gap-2 whitespace-nowrap">

                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-search preview-icon">
                            <path d="m21 21-4.34-4.34" />
                            <circle cx="11" cy="11" r="8" />
                        </svg>

                        Analyze Web Page
                    </span>
                    <span wire:loading.flex wire:target="analyze" class="relative items-center justify-center gap-3 whitespace-nowrap">
                        <span aria-hidden="true" class="relative flex size-5 shrink-0 items-center justify-center">
                            <span class="absolute inset-0 animate-spin rounded-full border-2 border-white/25 border-t-white border-r-white motion-reduce:animate-none"></span>
                            <span class="size-1.5 rounded-full bg-white"></span>
                        </span>
                        <span>Analyzing page</span>
                    </span>
                </button>
            </div>
            <fieldset class="mt-4 rounded-xl border border-white/10 bg-slate-800 p-3">
                <legend class="flex w-full flex-wrap items-center justify-between gap-2 text-sm font-bold text-white">
                    <span>Scoring categories</span>
                    <span class="text-[11px] font-normal text-blue-100/45">All on-page categories are selected by default</span>
                </legend>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach (\App\Support\Seo\ReportScores::CATEGORY_LABELS as $category => $label)
                        <label wire:key="seo-category-{{ $category }}"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-white/15 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-blue-100/70 transition hover:border-white/25 hover:text-white has-checked:border-cyan-300/50 has-checked:bg-cyan-400/10 has-checked:text-white">
                            <input type="checkbox" value="{{ $category }}" wire:model="selectedCategories"
                                class="size-4 shrink-0 cursor-pointer rounded border-white/25 bg-slate-950/50 accent-cyan-400 focus-visible:outline-2 focus-visible:outline-cyan-300">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('selectedCategories')
                    <p role="alert" class="mt-3 rounded-xl border border-red-300/25 bg-red-400/10 p-3 text-sm text-red-200">
                        {{ $message }}</p>
                @enderror
                @error('selectedCategories.*')
                    <p role="alert" class="mt-3 rounded-xl border border-red-300/25 bg-red-400/10 p-3 text-sm text-red-200">
                        {{ $message }}</p>
                @enderror
            </fieldset>
            @error('url')
                <p id="seo-error" role="alert"
                    class="mt-3 rounded-xl border border-red-300/25 bg-red-400/10 p-3 text-sm text-red-200">
                    {{ $message }}</p>
            @enderror
            @if ($this->remaining === 0)
                <p class="mt-3 rounded-xl border border-red-300/25 bg-red-400/10 p-3 text-sm text-red-200">You've used
                    today's allowance.
                    {{ $this->premium ? 'Come back after the reset for more checks.' : 'Upgrade for more daily checks, or come back after the reset.' }}
                </p>
            @endif
        </form>
        <div wire:loading wire:target="analyze" role="status" aria-live="polite" class="sr-only">Reading your page and checking its SEO signals…</div>
    </div>

    @if ($report = $this->report)
        <x-seo.report :report="$report" :premium="$this->premium" :upgrade-url="$this->upgradeUrl" />
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-6">
            @foreach (['Meta data' => 'Titles, descriptions, languages and canonical URLs', 'Page quality' => 'Content, images, markup and mobile signals', 'Others' => 'H1 and complete heading outline', 'Link structure' => 'Internal and external link evidence', 'Server configuration' => 'Headers, redirects and response performance', 'External factors' => 'Optional backlink data integration'] as $label => $description)
                @continue($label === 'External factors' && !filled(config('seo.integrations.backlinks.endpoint')))
                <article
                    class="rounded-2xl border border-white/10 bg-slate-900 p-5 backdrop-blur-2xl transition hover:border-cyan-300/30">
                    <span class="material-symbols-outlined text-cyan-300"
                        aria-hidden="true">{{ ['Meta data' => 'title', 'Page quality' => 'article', 'Others' => 'format_h1', 'Link structure' => 'account_tree', 'Server configuration' => 'settings_suggest', 'External factors' => 'hub'][$label] }}</span>
                    <h3 class="mt-3 text-sm font-bold text-white">{{ $label }}</h3>
                    <p class="leading-7 text-blue-100/60 mt-1.5 text-xs">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    @endif
    @if ($this->premium)
        <section
            class="rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7 [&_h2]:text-lg [&_h2]:font-extrabold mt-6">
            <h2>Your recent reports</h2>
            <p class="text-sm leading-7 text-blue-100/60 mt-1.5">Your latest {{ config('seo.history_limit') }} reports,
                kept for {{ config('seo.retention_days') }} days.</p>
            @forelse($this->history as $saved)
                <article class="flex items-start gap-3 border-b border-white/10 py-4 last:border-0"
                    wire:key="seo-history-{{ $saved->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="my-2 text-sm leading-7 wrap-anywhere text-cyan-200">{{ $saved->final_url }}</p>
                        <p class="leading-7 text-blue-100/60 text-xs">{{ $saved->created_at->format('M j, Y H:i') }} ·
                            Score {{ $saved->score ?? '—' }}/100</p>
                        <div class="mt-3 flex flex-wrap gap-4"><button type="button"
                                class="cursor-pointer text-xs font-bold text-cyan-300 transition hover:text-cyan-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300"
                                wire:click="viewReport({{ $saved->id }})">Open report</button><a
                                class="cursor-pointer text-xs font-bold text-cyan-300 transition hover:text-cyan-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300"
                                href="{{ route('client.tools.seo-report.pdf', $saved) }}">PDF ↓</a><button
                                type="button"
                                class="cursor-pointer text-xs font-bold text-cyan-300 transition hover:text-cyan-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300"
                                wire:click="deleteReport({{ $saved->id }})"
                                wire:confirm="Delete this saved report?">Delete</button></div>
                    </div>
            </article>@empty<p class="text-sm leading-7 text-blue-100/60 mt-5">Your first completed check will
                    appear here.</p>
            @endforelse
            <div class="mt-5">{{ $this->history->links() }}</div>
        </section>
    @endif
    <section
        class="rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-7 [&_h2]:text-lg [&_h2]:font-extrabold mt-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-300">Room to go further</p>
                <h2 class="mt-2">Choose how deep you want to look.</h2>
            </div>
            @if (!$this->premium)
                <a href="{{ $this->upgradeUrl }}" wire:navigate
                    class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-linear-to-r from-cyan-400 to-blue-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300 disabled:cursor-not-allowed disabled:opacity-50">Explore
                    Premium ↗</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table
                class="mt-4 w-full min-w-115 border-collapse text-sm [&_th]:px-3 [&_th]:py-3 [&_th]:text-left [&_th]:font-semibold [&_th]:text-blue-100 [&_td]:px-3 [&_td]:py-3 [&_td]:text-blue-100/60 [&_tr]:border-b [&_tr]:border-white/10">
                <thead>
                    <tr>
                        <th scope="col">Included</th>
                        <th scope="col">Free</th>
                        <th scope="col">Premium</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['Daily page checks' => [$this->freeDailyLimit, $this->premiumDailyLimit ?? 'Set by plan'], 'SEO score & basic on-page checks' => ['Included', 'Included'], 'Link structure' => ['Score only', 'Full details'], 'Server configuration' => ['Full details', 'Full details'], 'Fix recommendations' => ['Top 5', 'All'], 'Saved history' => ['—', config('seo.retention_days') . ' days'], 'Download PDF' => ['—', 'Included']] as $feature => $tiers)
                        <tr>
                            <th scope="row">{{ $feature }}</th>
                            <td>{{ $tiers[0] }}</td>
                            <td>{{ $tiers[1] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</section>
