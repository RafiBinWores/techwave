<?php

use App\Models\Visit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Visitor List')] class extends Component {
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

    public function visits()
    {
        $search = trim($this->search);

        return $this->scopedVisits()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', '%'.$search.'%')
                        ->orWhere('session_id', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->with('user')
            ->latest('created_at')
            ->paginate($this->perPage);
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">

        {{-- Header --}}
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Visitor List
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Every recorded page visit across the website.
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
                        placeholder="Search by URL, session, IP address, or user..."
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
                                Visitor
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Page
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Session
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Device
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Browser
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Referer
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Visited At
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->visits() as $visit)
                            <tr wire:key="visit-log-{{ $visit->id }}" class="transition-colors hover:bg-slate-50/80">

                                {{-- Visitor --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div @class([
                                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                            'bg-primary/10 text-primary' => $visit->user_id,
                                            'bg-slate-100 text-slate-500' => ! $visit->user_id,
                                        ])>
                                            {{ strtoupper(substr($visit->user?->name ?? 'G', 0, 1)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-on-surface">
                                                {{ $visit->user?->name ?? 'Guest' }}
                                            </p>

                                            <p class="truncate text-xs text-secondary">
                                                {{ $visit->ip_address ?? 'Unknown IP' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Page --}}
                                <td class="max-w-md px-6 py-4">
                                    <a href="{{ url($visit->url) }}" target="_blank"
                                        class="inline-flex items-center gap-2 max-w-xs truncate text-sm font-semibold text-primary hover:underline">
                                        {{ $visit->url }}
                                        <span class="material-symbols-outlined shrink-0 text-[15px]">open_in_new</span>
                                    </a>
                                </td>

                                {{-- Session --}}
                                <td class="px-6 py-4">
                                    <span title="{{ $visit->session_id }}"
                                        class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-600">
                                        {{ str($visit->session_id)->limit(10) }}...
                                    </span>
                                </td>

                                {{-- Device --}}
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        @switch($visit->device)
                                            @case('Mobile')
                                                <span class="material-symbols-outlined text-[15px] text-primary">smartphone</span>
                                                @break
                                            @case('Tablet')
                                                <span class="material-symbols-outlined text-[15px] text-primary">tablet</span>
                                                @break
                                            @case('Laptop')
                                                <span class="material-symbols-outlined text-[15px] text-primary">laptop_mac</span>
                                                @break
                                            @default
                                                <span class="material-symbols-outlined text-[15px] text-primary">desktop_windows</span>
                                        @endswitch
                                        {{ $visit->device }}
                                    </span>
                                </td>

                                {{-- Browser --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-700">
                                            {{ $visit->browser }}
                                        </span>

                                        @if ($visit->os)
                                            <span class="text-xs text-slate-400">
                                                {{ $visit->os }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Referer --}}
                                <td class="max-w-[220px] px-6 py-4">
                                    @if ($visit->referer)
                                        <span class="block truncate text-sm text-secondary" title="{{ $visit->referer }}">
                                            {{ parse_url($visit->referer, PHP_URL_HOST) ?: $visit->referer }}
                                        </span>
                                    @else
                                        <span class="text-sm text-slate-400">Direct</span>
                                    @endif
                                </td>

                                {{-- Time --}}
                                <td class="px-6 py-4">
                                    <p class="text-sm text-secondary">
                                        {{ $visit->created_at?->format('M d, Y') }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $visit->created_at?->format('h:i A') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">visibility_off</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No visits found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Recorded visits will appear here.
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
                    {{ $this->visits()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
