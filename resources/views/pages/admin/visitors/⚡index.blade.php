<?php

use App\Models\Visit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Visitor Analytics')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $period = 'all';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    private function scopedVisits()
    {
        return match ($this->period) {
            'today' => Visit::query()->whereDate('created_at', today()),
            '7days' => Visit::query()->where('created_at', '>=', now()->subDays(6)->startOfDay()),
            '30days' => Visit::query()->where('created_at', '>=', now()->subDays(29)->startOfDay()),
            default => Visit::query(),
        };
    }

    public function pages()
    {
        $search = trim($this->search);

        return $this->scopedVisits()
            ->when($search !== '', fn ($query) => $query->where('url', 'like', '%'.$search.'%'))
            ->selectRaw("url, count(*) as visits_count, count(distinct session_id) as unique_visitors, max(created_at) as last_visited_at")
            ->groupBy('url')
            ->orderByDesc('visits_count')
            ->paginate($this->perPage);
    }

    public function totalVisits(): int
    {
        return (int) $this->scopedVisits()->count();
    }

    public function uniqueVisitors(): int
    {
        return (int) $this->scopedVisits()->distinct('session_id')->count('session_id');
    }

    public function visitsToday(): int
    {
        return (int) Visit::query()->whereDate('created_at', today())->count();
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">

        {{-- Header --}}
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Visitor Analytics
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Track total visits and unique visitors for every page of the website.
                </p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[12px] font-bold uppercase tracking-wider text-slate-400">
                        Total Visits
                    </p>

                    <span class="material-symbols-outlined text-primary">visibility</span>
                </div>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($this->totalVisits()) }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-[12px] font-bold uppercase tracking-wider text-slate-400">
                        Unique Visitors
                    </p>

                    <span class="material-symbols-outlined text-primary">group</span>
                </div>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($this->uniqueVisitors()) }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <p class="text-[12px] font-bold uppercase tracking-wider text-slate-400">
                        Visits Today
                    </p>

                    <span class="material-symbols-outlined text-primary">today</span>
                </div>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($this->visitsToday()) }}
                </p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[1fr_220px]">
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Search by page URL..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                </div>

                <div class="relative">
                    <select wire:model.live="period"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                    </select>

                    <span
                        class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Page
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Total Visits
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Unique Visitors
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Last Visited
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->pages() as $page)
                            <tr wire:key="visit-page-{{ $page->url }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="max-w-md px-6 py-4">
                                    <a href="{{ url($page->url) }}" target="_blank"
                                        class="inline-flex items-center gap-2 truncate text-sm font-semibold text-primary hover:underline">
                                        {{ $page->url }}
                                        <span class="material-symbols-outlined shrink-0 text-[15px]">open_in_new</span>
                                    </a>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex min-w-10 items-center justify-center rounded-lg bg-primary/10 px-2.5 py-1 text-sm font-bold text-primary">
                                        {{ number_format((int) $page->visits_count) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex min-w-10 items-center justify-center rounded-lg bg-emerald-100 px-2.5 py-1 text-sm font-bold text-emerald-700">
                                        {{ number_format((int) $page->unique_visitors) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm text-secondary">
                                        {{ \Carbon\Carbon::parse($page->last_visited_at)->format('M d, Y') }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ \Carbon\Carbon::parse($page->last_visited_at)->format('h:i A') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">visibility_off</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No visitor data found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Page visits will appear here once your website starts receiving traffic.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div
                class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">Per page</span>

                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->pages()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
