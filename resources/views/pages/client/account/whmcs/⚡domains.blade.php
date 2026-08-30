<?php

use App\Models\WhmcsAccount;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Domains')] class extends Component {
    use WithPagination;

    /** @var array<int, array<string, mixed>> */
    public array $domains = [];

    public bool $isLinked = false;

    public string $activeTab = 'active';

    public int $perPage = 10;

    public function mount(): void
    {
        $account = Auth::user()->whmcsAccount;

        if (! $account) {
            $this->isLinked = false;

            return;
        }

        $this->isLinked = true;

        $cacheKey = 'whmcs-domains:' . Auth::id();
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $this->domains = $cached;

            return;
        }

        try {
            $api = app(WhmcsApi::class);
            $domains = $api->getClientDomains($account->whmcs_user_id);
        } catch (WhmcsApiException) {
            $domains = [];
        }

        usort($domains, fn($a, $b) => strcmp((string) ($b['id'] ?? 0), (string) ($a['id'] ?? 0)));

        $this->domains = array_slice($domains, 0, 100);

        Cache::put($cacheKey, $this->domains, now()->addMinutes(10));
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['active', 'expired', 'all'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    /** @var array<int, array<string, mixed>> */
    public function filteredDomains(): array
    {
        return array_filter($this->domains, function (array $domain) {
            $status = strtolower(trim((string) data_get($domain, 'status', '')));

            return match ($this->activeTab) {
                'active' => in_array($status, ['active', 'pending', 'registered'], true),
                'expired' => in_array($status, ['expired', 'cancelled', 'deleted', 'transferred away'], true),
                default => true,
            };
        });
    }

    public function paginatedDomains(): LengthAwarePaginator
    {
        $items = collect($this->filteredDomains());
        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    public function formatDate(?string $date): string
    {
        if (! $date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return 'No Expiry';
        }

        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Exception) {
            return 'No Expiry';
        }
    }

    public function domainStatusClass(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'active', 'registered' => 'border-emerald-300/20 bg-emerald-400/10 text-emerald-200',
            'pending' => 'border-amber-300/20 bg-amber-400/10 text-amber-200',
            'expired' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
            'cancelled', 'deleted', 'transferred away' => 'border-slate-300/20 bg-slate-400/10 text-slate-200',
            default => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
        };
    }

    public function activeCount(): int
    {
        return count(array_filter($this->domains, fn(array $d) => in_array(strtolower(trim((string) data_get($d, 'status', ''))), ['active', 'pending', 'registered'], true)));
    }

    public function expiredCount(): int
    {
        return count(array_filter($this->domains, fn(array $d) => in_array(strtolower(trim((string) data_get($d, 'status', ''))), ['expired', 'cancelled', 'deleted', 'transferred away'], true)));
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Billing</p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">My Domains</h1>
                            </div>
                        </div>

                        <!-- <div
                            class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 backdrop-blur-xl">
                            <span
                                class="material-symbols-outlined {{ $isLinked ? 'text-emerald-300' : 'text-cyan-300' }}">
                                {{ $isLinked ? 'link' : 'link_off' }}
                            </span>
                            <div>
                                <p class="text-xs text-blue-100/45">Billing Account</p>
                                <p class="text-sm font-semibold {{ $isLinked ? 'text-emerald-300' : 'text-blue-100/70' }}">
                                    {{ $isLinked ? 'Linked' : 'Not linked' }}
                                </p>
                            </div>
                        </div> -->
                    </div>

                    @if (! $isLinked)
                    {{-- Not linked state --}}
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-12 text-center shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                            <span class="material-symbols-outlined text-3xl">link_off</span>
                        </div>
                        <h2 class="mt-6 text-2xl font-bold text-white">No billing account linked</h2>
                        <p class="mt-3 text-sm text-blue-100/55">
                            Link your WHMCS billing account to view domains, services, and billing details.
                        </p>
                        <a href="{{ route('account.link-whmcs') }}" wire:navigate
                            class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-cyan-500/35">
                            <span class="material-symbols-outlined text-lg">link</span>
                            Link Billing Account
                        </a>
                    </div>
                    @else

                    {{-- Tab Switcher --}}
                    <div class="mb-6 rounded-2xl border border-white/10 bg-white/8 p-2 sm:p-3 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                            <button type="button" wire:click="setTab('active')"
                                class="group flex items-center justify-between rounded-xl border px-2.5 py-2 sm:px-4 sm:py-2.5 text-left transition cursor-pointer
                                {{ $activeTab === 'active'
                                    ? 'border-emerald-300/50 bg-emerald-400/10 text-white shadow-lg shadow-emerald-500/10'
                                    : 'border-white/10 bg-white/6 text-blue-100/65 hover:border-white/20 hover:bg-white/10 hover:text-white' }}">
                                <div class="flex items-center gap-1.5 sm:gap-2.5">
                                    <div
                                        class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg border
                                        {{ $activeTab === 'active'
                                            ? 'border-emerald-300/30 bg-emerald-300/15 text-emerald-200'
                                            : 'border-white/10 bg-white/8 text-blue-100/55' }}">
                                        <span class="material-symbols-outlined text-base sm:text-lg">check_circle</span>
                                    </div>
                                    <div>
                                        <p class="text-xs sm:text-sm font-bold">Active</p>
                                        <p class="hidden sm:block text-[10px] text-blue-100/45">{{ $this->activeCount() }} domain{{ $this->activeCount() !== 1 ? 's' : '' }}</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-base sm:text-lg transition hidden sm:block {{ $activeTab === 'active' ? 'text-emerald-200' : 'text-blue-100/35 group-hover:text-white' }}">
                                    arrow_forward
                                </span>
                            </button>

                            <button type="button" wire:click="setTab('expired')"
                                class="group flex items-center justify-between rounded-xl border px-2.5 py-2 sm:px-4 sm:py-2.5 text-left transition cursor-pointer
                                {{ $activeTab === 'expired'
                                    ? 'border-rose-300/50 bg-rose-400/10 text-white shadow-lg shadow-rose-500/10'
                                    : 'border-white/10 bg-white/6 text-blue-100/65 hover:border-white/20 hover:bg-white/10 hover:text-white' }}">
                                <div class="flex items-center gap-1.5 sm:gap-2.5">
                                    <div
                                        class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg border
                                        {{ $activeTab === 'expired'
                                            ? 'border-rose-300/30 bg-rose-300/15 text-rose-200'
                                            : 'border-white/10 bg-white/8 text-blue-100/55' }}">
                                        <span class="material-symbols-outlined text-base sm:text-lg">cancel</span>
                                    </div>
                                    <div>
                                        <p class="text-xs sm:text-sm font-bold">Expired</p>
                                        <p class="hidden sm:block text-[10px] text-blue-100/45">{{ $this->expiredCount() }} domain{{ $this->expiredCount() !== 1 ? 's' : '' }}</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-base sm:text-lg transition hidden sm:block {{ $activeTab === 'expired' ? 'text-rose-200' : 'text-blue-100/35 group-hover:text-white' }}">
                                    arrow_forward
                                </span>
                            </button>

                            <button type="button" wire:click="setTab('all')"
                                class="group flex items-center justify-between rounded-xl border px-2.5 py-2 sm:px-4 sm:py-2.5 text-left transition cursor-pointer
                                {{ $activeTab === 'all'
                                    ? 'border-blue-300/50 bg-blue-400/10 text-white shadow-lg shadow-blue-500/10'
                                    : 'border-white/10 bg-white/6 text-blue-100/65 hover:border-white/20 hover:bg-white/10 hover:text-white' }}">
                                <div class="flex items-center gap-1.5 sm:gap-2.5">
                                    <div
                                        class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg border
                                        {{ $activeTab === 'all'
                                            ? 'border-blue-300/30 bg-blue-300/15 text-blue-200'
                                            : 'border-white/10 bg-white/8 text-blue-100/55' }}">
                                        <span class="material-symbols-outlined text-base sm:text-lg">dns</span>
                                    </div>
                                    <div>
                                        <p class="text-xs sm:text-sm font-bold">All</p>
                                        <p class="hidden sm:block text-[10px] text-blue-100/45">{{ count($this->domains) }} total</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-base sm:text-lg transition hidden sm:block {{ $activeTab === 'all' ? 'text-blue-200' : 'text-blue-100/35 group-hover:text-white' }}">
                                    arrow_forward
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Domains Table --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-white/10 bg-white/8 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="flex items-center justify-between p-6 pb-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Domain List
                                </p>
                                <h2 class="mt-1 text-xl font-bold text-white">
                                    @if ($activeTab === 'active') Active Domains
                                    @elseif ($activeTab === 'expired') Expired Domains
                                    @else All Domains
                                    @endif
                                </h2>
                            </div>
                            <span class="text-xs text-blue-100/45">{{ count($this->filteredDomains()) }} total</span>
                        </div>

                        @if (count($this->filteredDomains()))
                        <div class="overflow-x-auto px-2 pb-4">
                            <table class="w-full min-w-[680px] text-left text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-blue-100/40">
                                        <th class="px-4 py-3 font-semibold">Domain</th>
                                        <th class="px-4 py-3 font-semibold">Registration Date</th>
                                        <th class="px-4 py-3 font-semibold">Expiry Date</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->paginatedDomains() as $domain)
                                    @php
                                    $domainId = data_get($domain, 'id');
                                    @endphp
                                    <tr class="border-t border-white/8 hover:bg-white/4">
                                        <td class="px-4 py-4 font-semibold">
                                            <a href="{{ $domainId ? route('account.whmcs.domain-detail', $domainId) : route('account.whmcs.sso') }}" target="_blank" class="text-cyan-200 hover:text-white transition">
                                                {{ data_get($domain, 'domainname', 'N/A') }}
                                                <span class="material-symbols-outlined ml-1 inline-block align-middle text-xs text-blue-100/40">open_in_new</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-4 text-blue-100/65">
                                            {{ $this->formatDate(data_get($domain, 'regdate')) }}
                                        </td>
                                        <td class="px-4 py-4 text-blue-100/65">
                                            @php
                                            $expDate = data_get($domain, 'expirydate');
                                            $displayDate = ($expDate && $expDate !== '0000-00-00' && $expDate !== '0000-00-00 00:00:00')
                                            ? $expDate
                                            : data_get($domain, 'nextduedate');
                                            @endphp
                                            {{ $this->formatDate($displayDate) }}
                                        </td>
                                        <td class="px-4 py-4">
                                            @php
                                            $domainStatus = data_get($domain, 'status', 'Unknown');
                                            @endphp
                                            <span
                                                class="{{ $this->domainStatusClass($domainStatus) }}">
                                                {{ ucfirst(strtolower((string) $domainStatus)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($this->paginatedDomains()->hasPages())
                        <div class="px-6 pb-5">
                            {{ $this->paginatedDomains()->links() }}
                        </div>
                        @endif
                        @else
                        <div class="px-6 pb-6">
                            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 px-6 py-12 text-center">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-3xl border border-blue-300/20 bg-blue-400/10 text-blue-200">
                                    <span class="material-symbols-outlined text-4xl">dns</span>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-white">No domains found</h3>
                                <p class="mt-2 max-w-md text-sm leading-7 text-blue-100/55">
                                    @if ($activeTab === 'active')
                                    You don't have any active domains on your connected billing account.
                                    @elseif ($activeTab === 'expired')
                                    You don't have any expired domains on your connected billing account.
                                    @else
                                    No domains were found on your connected billing account.
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>

                    @endif

                </div>
            </div>
        </div>
    </div>
</div>