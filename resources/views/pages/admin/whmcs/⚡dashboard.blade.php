<?php

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts.admin-app')]
    #[Title('WHMCS Dashboard')]
    class extends Component
    {
        public string $apiError = '';

        public string $chartPeriod = '12m';

        /** @var array<int, string> */
        protected array $cacheKeys = [
            'whmcs-admin-stats',
            'whmcs-admin-client-counts',
            'whmcs-admin-clients',
            'whmcs-admin-invoices',
            'whmcs-admin-trend',
            'whmcs-admin-all-invoices',
        ];

        #[Computed]
        public function whmcsAdminUrl(): string
        {
            return app(WhmcsApi::class)->adminUrl();
        }

        #[Computed]
        public function stats(): array
        {
            $cacheKey = 'whmcs-admin-stats';
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }

            try {
                $stats = app(WhmcsApi::class)->getStats();
            } catch (WhmcsApiException $exception) {
                $this->apiError = $exception->getMessage();

                return [];
            }

            $normalized = [
                'income_today' => $this->statsMoney($stats['income_today'] ?? 0),
                'income_thismonth' => $this->statsMoney($stats['income_thismonth'] ?? 0),
                'income_thisyear' => $this->statsMoney($stats['income_thisyear'] ?? 0),
                'income_alltime' => $this->statsMoney($stats['income_alltime'] ?? 0),
                'orders_pending' => (int) data_get($stats, 'orders_pending', 0),
                'tickets_allactive' => (int) data_get($stats, 'tickets_allactive', 0),
                'tickets_open' => (int) data_get($stats, 'tickets_open', 0),
                'tickets_answered' => (int) data_get($stats, 'tickets_answered', 0),
                'tickets_awaiting_reply' => (int) (data_get($stats, 'tickets_awaitingreply', 0) ?: data_get($stats, 'tickets_awaiting_reply', 0)),
                'tickets_inprogress' => (int) data_get($stats, 'tickets_inprogress', 0),
                'tickets_onhold' => (int) data_get($stats, 'tickets_onhold', 0),
                'tickets_closed' => (int) data_get($stats, 'tickets_closed', 0),
            ];

            Cache::put($cacheKey, $normalized, now()->addMinutes(5));

            return $normalized;
        }

        #[Computed]
        public function clientCounts(): array
        {
            $cacheKey = 'whmcs-admin-client-counts';
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }

            try {
                $api = app(WhmcsApi::class);

                $payload = [
                    'active' => (int) data_get($api->request('GetClients', ['limitnum' => 1, 'status' => 'Active']), 'totalresults', 0),
                    'inactive' => (int) data_get($api->request('GetClients', ['limitnum' => 1, 'status' => 'Inactive']), 'totalresults', 0),
                ];
            } catch (WhmcsApiException) {
                $payload = ['active' => 0, 'inactive' => 0];
            }

            Cache::put($cacheKey, $payload, now()->addMinutes(5));

            return $payload;
        }

        #[Computed]
        public function recentClients(): array
        {
            return $this->cachedPayload('whmcs-admin-clients', fn(WhmcsApi $api) => $api->getRecentClients(6));
        }

        #[Computed]
        public function recentInvoices(): array
        {
            return $this->cachedPayload('whmcs-admin-invoices', fn(WhmcsApi $api) => $api->getRecentInvoices(6));
        }

        /**
         * Billing trend for the chart, sliced to the selected period.
         *
         * Daily series powers "Today" / "Last 30 Days"; monthly series powers
         * "Last 6 Months" / "Last 12 Months".
         *
         * @return array{labels: array<int, string>, revenue: array<int, float>, invoices: array<int, int>}
         */
        #[Computed]
        public function billingTrend(): array
        {
            $cached = Cache::get('whmcs-admin-trend');
            $trend = is_array($cached)
                && isset(
                    $cached['daily']['labels'],
                    $cached['daily']['revenue'],
                    $cached['daily']['invoices'],
                    $cached['monthly']['labels'],
                    $cached['monthly']['revenue'],
                    $cached['monthly']['invoices'],
                ) ? $cached : null;

            if ($trend === null) {
                try {
                    $trend = app(WhmcsApi::class)->getBillingTrend();

                    Cache::put('whmcs-admin-trend', $trend, now()->addMinutes(5));
                } catch (WhmcsApiException $exception) {
                    $this->apiError = $exception->getMessage();
                    $trend = [
                        'daily' => ['labels' => [], 'revenue' => [], 'invoices' => []],
                        'monthly' => ['labels' => [], 'revenue' => [], 'invoices' => []],
                    ];
                }
            }

            return match ($this->chartPeriod) {
                'today' => [
                    'labels' => array_slice($trend['daily']['labels'], -1),
                    'revenue' => array_slice($trend['daily']['revenue'], -1),
                    'invoices' => array_slice($trend['daily']['invoices'], -1),
                ],
                '30d' => $trend['daily'],
                '6m' => [
                    'labels' => array_slice($trend['monthly']['labels'], -6),
                    'revenue' => array_slice($trend['monthly']['revenue'], -6),
                    'invoices' => array_slice($trend['monthly']['invoices'], -6),
                ],
                default => $trend['monthly'],
            };
        }

        /**
         * Top WHMCS clients by paid revenue for the leaderboard card.
         *
         * Shares the whmcs-admin-all-invoices cache with the invoices list page.
         *
         * @return array<int, array{name: string, revenue: float, invoices: int}>
         */
        #[Computed]
        public function topClientsByRevenue(): array
        {
            $cached = Cache::get('whmcs-admin-all-invoices');

            if (! (is_array($cached) && isset($cached['items'], $cached['total']))) {
                try {
                    $cached = app(WhmcsApi::class)->getAllInvoices();
                    Cache::put('whmcs-admin-all-invoices', $cached, now()->addMinutes(5));
                } catch (WhmcsApiException $exception) {
                    $this->apiError = $exception->getMessage();

                    return [];
                }
            }

            $clients = [];

            foreach ($cached['items'] as $invoice) {
                if (strcasecmp((string) data_get($invoice, 'status', ''), 'Paid') !== 0) {
                    continue;
                }

                $id = (string) data_get($invoice, 'userid', '');

                if ($id === '') {
                    continue;
                }

                $name = trim((string) data_get($invoice, 'companyname', ''))
                    ?: trim((string) data_get($invoice, 'firstname', '') . ' ' . (string) data_get($invoice, 'lastname', ''));

                $amount = (string) data_get($invoice, 'total', '0');

                if (preg_match('/([0-9][0-9.,]*)/', $amount, $matches) === 1) {
                    $amount = $matches[1];
                }

                $amount = (float) str_replace(',', '', $amount);

                if (! isset($clients[$id])) {
                    $clients[$id] = [
                        'name' => $name !== '' ? $name : 'Client #' . $id,
                        'revenue' => 0.0,
                        'invoices' => 0,
                    ];
                }

                if ($name !== '' && str_starts_with($clients[$id]['name'], 'Client #')) {
                    $clients[$id]['name'] = $name;
                }

                $clients[$id]['revenue'] += $amount;
                $clients[$id]['invoices']++;
            }

            usort($clients, fn(array $a, array $b) => $b['revenue'] <=> $a['revenue']);

            return array_slice($clients, 0, 6);
        }

        public function updatedChartPeriod(): void
        {
            $this->dispatch('whmcs-billing-chart-updated', trend: $this->billingTrend());
        }

        public function refresh(): void
        {
            foreach ($this->cacheKeys as $key) {
                Cache::forget($key);
            }

            $this->apiError = '';
            $this->dispatch('whmcs-billing-chart-updated', trend: $this->billingTrend());
            $this->dispatch('toast', message: 'WHMCS dashboard data has been refreshed.', type: 'success');
        }

        public function statsMoney(null|string|int|float $amount): float
        {
            if ($amount === null || $amount === '') {
                return 0.0;
            }

            if (is_numeric($amount)) {
                return (float) $amount;
            }

            preg_match('/[\d.,]+/', (string) $amount, $matches);

            return (float) str_replace(',', '', $matches[0] ?? '0');
        }

        public function formatMoney(null|string|int|float $amount): string
        {
            if ($amount === null || $amount === '') {
                return '-';
            }

            return number_format((float) $amount, 2) . ' ' . (string) config('app.currency_code', 'BDT');
        }

        public function formatDate(?string $date): string
        {
            if (! $date || $date === '0000-00-00') {
                return '-';
            }

            return Carbon::parse($date)->format('d M Y');
        }

        /**
         * @param  callable(WhmcsApi): array{items: array<int, array<string, mixed>>, total: int}  $callback
         * @return array{items: array<int, array<string, mixed>>, total: int}
         */
        private function cachedPayload(string $key, callable $callback): array
        {
            $cached = Cache::get($key);

            if (is_array($cached)) {
                return $cached;
            }

            try {
                $payload = $callback(app(WhmcsApi::class));
            } catch (WhmcsApiException) {
                $payload = ['items' => [], 'total' => 0];
            }

            Cache::put($key, $payload, now()->addMinutes(5));

            return $payload;
        }
    };
?>

<div class="mx-auto w-full space-y-stack-lg">

    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                WHMCS Dashboard
            </h2>

            <p class="text-xs font-body-md text-secondary md:text-body-md">
                Monitor your WHMCS billing system and live business metrics.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold @if ($apiError) bg-rose-50 text-rose-700 @else bg-emerald-50 text-emerald-700 @endif">
                @if ($apiError)
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                Connection issue
                @else
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Connected
                @endif
            </span>

            <a href="{{ $this->whmcsAdminUrl }}" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50">
                <span class="material-symbols-outlined text-lg">open_in_new</span>
                Open WHMCS Admin
            </a>

            <button type="button" wire:click="refresh" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                <span wire:loading.remove wire:target="refresh" class="material-symbols-outlined text-lg">refresh</span>
                <span wire:loading wire:target="refresh" class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
                Refresh Data
            </button>
        </div>
    </div>

    {{-- Connection error banner --}}
    @if ($apiError)
    <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4">
        <span class="material-symbols-outlined mt-0.5 text-rose-500">error</span>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-rose-800">Could not reach the WHMCS billing system</p>
            <p class="mt-0.5 text-sm text-rose-600">{{ $apiError }}</p>
        </div>
    </div>
    @endif

    {{-- Billing chart + top clients --}}
    <div class="grid gap-stack-lg lg:grid-cols-3">

        {{-- Billing analytics chart --}}
        <div class="flex h-[480px] min-h-[480px] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-2">
            <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <span class="material-symbols-outlined text-lg">ssid_chart</span>
                    </span>
                    <div>
                        <h3 class="text-label-md font-label-md text-on-surface">Billing Analytics</h3>
                        <p class="text-body-sm font-body-sm text-secondary">Paid revenue vs invoice volume over time</p>
                    </div>
                </div>

                <select wire:model.live="chartPeriod"
                    class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="today">Today</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="6m">Last 6 Months</option>
                    <option value="12m">Last 12 Months</option>
                </select>
            </div>

            <div class="flex shrink-0 items-center gap-4 px-6 pt-4">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background-color:#6366f1"></span>
                    <span class="text-body-sm font-body-sm text-secondary">Revenue</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background-color:#10b981"></span>
                    <span class="text-body-sm font-body-sm text-secondary">Paid Invoices</span>
                </div>
            </div>

            <div class="min-h-0 min-w-0 flex-1 p-4">
                <div id="whmcsBillingChart" wire:ignore class="h-full w-full min-w-0 max-w-full"></div>
            </div>
        </div>

        {{-- Top clients leaderboard --}}
        <div class="flex h-[480px] min-h-[480px] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-lg">leaderboard</span>
                </span>
                <div>
                    <h3 class="text-label-md font-label-md text-on-surface">Top Clients</h3>
                    <p class="text-body-sm font-body-sm text-secondary">Ranked by paid revenue</p>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @php
                $topClients = $this->topClientsByRevenue();
                @endphp

                @if (count($topClients))
                <ul class="divide-y divide-slate-100">
                    @foreach ($topClients as $index => $client)
                    <li class="flex items-center gap-3 px-6 py-3.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $index < 3 ? 'bg-primary text-on-primary' : 'bg-slate-100 text-slate-500' }}">
                            {{ $index + 1 }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body-sm font-label-sm text-on-surface">{{ $client['name'] }}</p>
                            <p class="text-body-sm font-body-sm text-secondary">
                                {{ $client['invoices'] }} paid {{ $client['invoices'] === 1 ? 'invoice' : 'invoices' }}
                            </p>
                        </div>

                        <p class="shrink-0 text-body-sm font-label-sm font-semibold text-emerald-600">
                            {{ $this->formatMoney($client['revenue']) }}
                        </p>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="px-6 py-10 text-center text-sm text-secondary">No paid revenue yet.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid gap-stack-lg lg:grid-cols-2">

        {{-- Revenue --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <span class="material-symbols-outlined text-lg">payments</span>
                </span>
                <div>
                    <h3 class="text-label-md font-label-md text-on-surface">Revenue</h3>
                    <p class="text-body-sm font-body-sm text-secondary">Income captured by the billing system</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
                @php
                $revenueRows = [
                ['label' => 'Today', 'value' => $this->formatMoney($this->stats['income_today'] ?? 0)],
                ['label' => 'This Month', 'value' => $this->formatMoney($this->stats['income_thismonth'] ?? 0)],
                ['label' => 'This Year', 'value' => $this->formatMoney($this->stats['income_thisyear'] ?? 0)],
                ['label' => 'All Time', 'value' => $this->formatMoney($this->stats['income_alltime'] ?? 0)],
                ];
                @endphp

                @foreach ($revenueRows as $row)
                <div class="bg-white px-6 py-5">
                    <p class="text-body-sm font-body-sm text-secondary">{{ $row['label'] }}</p>
                    <p class="mt-1.5 text-xl font-bold text-emerald-600">{{ $row['value'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Activity --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-lg">monitoring</span>
                </span>
                <div>
                    <h3 class="text-label-md font-label-md text-on-surface">System Activity</h3>
                    <p class="text-body-sm font-body-sm text-secondary">Current load across clients, orders and support</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-px bg-slate-100 sm:grid-cols-3">
                @php
                $activityRows = [
                ['label' => 'Active Clients', 'value' => $this->clientCounts['active'] ?? 0, 'icon' => 'group'],
                ['label' => 'Inactive Clients', 'value' => $this->clientCounts['inactive'] ?? 0, 'icon' => 'person_off'],
                ['label' => 'Pending Orders', 'value' => $this->stats['orders_pending'] ?? 0, 'icon' => 'receipt_long'],
                ['label' => 'Tickets Open', 'value' => $this->stats['tickets_open'] ?? 0, 'icon' => 'support_agent'],
                ['label' => 'Tickets Answered', 'value' => $this->stats['tickets_answered'] ?? 0, 'icon' => 'mark_email_read'],
                ['label' => 'Awaiting Reply', 'value' => $this->stats['tickets_awaiting_reply'] ?? 0, 'icon' => 'hourglass_top'],
                ];
                @endphp

                @foreach ($activityRows as $row)
                <div class="bg-white px-5 py-5">
                    <div class="flex items-center gap-1.5 text-body-sm font-body-sm text-secondary">
                        <span class="material-symbols-outlined text-base">{{ $row['icon'] }}</span>
                        {{ $row['label'] }}
                    </div>
                    <p class="mt-1.5 text-xl font-bold text-on-surface">{{ $row['value'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Recent clients --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h3 class="text-label-md font-label-md text-on-surface">Recent Clients</h3>
                <span class="text-body-sm font-body-sm text-secondary">{{ $this->recentClients['total'] }} total</span>
            </div>

            @if (count($this->recentClients['items']))
            <ul class="divide-y divide-slate-100">
                @foreach ($this->recentClients['items'] as $client)
                @php
                $clientName = data_get($client, 'companyname') ?: trim((string) data_get($client, 'firstname', '').' '.(string) data_get($client, 'lastname', ''));
                $clientStatus = (string) data_get($client, 'status', '');
                @endphp

                <li class="flex items-center gap-3 px-6 py-3.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold uppercase text-primary">
                        {{ str($clientName ?: 'C')->substr(0, 1) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-body-sm font-label-sm text-on-surface">{{ $clientName ?: 'Unknown' }}</p>
                        <p class="truncate text-body-sm font-body-sm text-secondary">{{ data_get($client, 'email', '-') }}</p>
                    </div>

                    @if ($clientStatus)
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold @if (strtolower($clientStatus) === 'active') bg-emerald-50 text-emerald-700 @else bg-slate-100 text-slate-600 @endif">
                        {{ ucfirst(strtolower($clientStatus)) }}
                    </span>
                    @endif
                </li>
                @endforeach
            </ul>
            @else
            <div class="px-6 py-10 text-center text-sm text-secondary">No clients found.</div>
            @endif
        </div>

        {{-- Recent invoices --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h3 class="text-label-md font-label-md text-on-surface">Recent Invoices</h3>
                <span class="text-body-sm font-body-sm text-secondary">{{ $this->recentInvoices['total'] }} total</span>
            </div>

            @if (count($this->recentInvoices['items']))
            <ul class="divide-y divide-slate-100">
                @foreach ($this->recentInvoices['items'] as $invoice)
                @php
                $invoiceStatus = strtolower((string) data_get($invoice, 'status', ''));
                $invoiceCurrency = (string) data_get($invoice, 'currencyprefix', '');
                @endphp

                <li class="flex items-center gap-3 px-6 py-3.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <span class="material-symbols-outlined text-lg">receipt</span>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-body-sm font-label-sm text-on-surface">
                            Invoice #{{ data_get($invoice, 'id') }}
                        </p>
                        <p class="truncate text-body-sm font-body-sm text-secondary">
                            {{ $this->formatDate(data_get($invoice, 'date')) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-body-sm font-label-sm text-on-surface">
                            {{ $this->formatMoney(data_get($invoice, 'total')) }}
                        </p>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold @if ($invoiceStatus === 'paid') bg-emerald-50 text-emerald-700 @elseif (in_array($invoiceStatus, ['unpaid', 'pending'])) bg-amber-50 text-amber-700 @else bg-slate-100 text-slate-600 @endif">
                            {{ ucfirst($invoiceStatus) ?: 'N/A' }}
                        </span>
                    </div>
                </li>
                @endforeach
            </ul>
            @else
            <div class="px-6 py-10 text-center text-sm text-secondary">No invoices found.</div>
            @endif
        </div>

        {{-- Support overview --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h3 class="text-label-md font-label-md text-on-surface">Support Overview</h3>
                <span class="text-body-sm font-body-sm text-secondary">Ticket workload</span>
            </div>

            <div class="grid grid-cols-2 gap-px bg-slate-100">
                @php
                $ticketRows = [
                ['label' => 'All Active', 'value' => $this->stats['tickets_allactive'] ?? 0, 'icon' => 'support_agent', 'class' => 'text-blue-600'],
                ['label' => 'Open', 'value' => $this->stats['tickets_open'] ?? 0, 'icon' => 'markunread_mailbox', 'class' => 'text-slate-700'],
                ['label' => 'Awaiting Reply', 'value' => $this->stats['tickets_awaiting_reply'] ?? 0, 'icon' => 'hourglass_top', 'class' => 'text-amber-600'],
                ['label' => 'In Progress', 'value' => $this->stats['tickets_inprogress'] ?? 0, 'icon' => 'pending_actions', 'class' => 'text-indigo-600'],
                ['label' => 'On Hold', 'value' => $this->stats['tickets_onhold'] ?? 0, 'icon' => 'pause_circle', 'class' => 'text-slate-500'],
                ['label' => 'Closed', 'value' => $this->stats['tickets_closed'] ?? 0, 'icon' => 'check_circle', 'class' => 'text-emerald-600'],
                ];
                @endphp

                @foreach ($ticketRows as $row)
                <div class="bg-white px-5 py-5">
                    <div class="flex items-center gap-1.5 text-body-sm font-body-sm text-secondary">
                        <span class="material-symbols-outlined text-base {{ $row['class'] }}">{{ $row['icon'] }}</span>
                        {{ $row['label'] }}
                    </div>
                    <p class="mt-1.5 text-xl font-bold text-on-surface">{{ $row['value'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Initial chart data --}}
    @php
    $initialBillingTrend = $this->billingTrend();
    @endphp

    {{-- ApexCharts --}}
    @script
    <script>
        let billingChart = null;
        let billingChartInitialized = false;

        const billingCurrencyCode = @js(config('app.currency_code', 'BDT'));

        const initialBillingTrend = @json($initialBillingTrend);

        const billingChartOptions = function(trend) {
            return {
                chart: {
                    id: 'whmcs-billing-chart',
                    type: 'area',
                    width: '100%',
                    height: '100%',
                    parentHeightOffset: 0,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },
                    redrawOnParentResize: true,
                    redrawOnWindowResize: true,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 450,
                        animateGradually: {
                            enabled: true,
                            delay: 40
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        },
                    },
                },

                series: [{
                        name: 'Revenue',
                        data: trend.revenue
                    },
                    {
                        name: 'Paid Invoices',
                        data: trend.invoices
                    },
                ],

                colors: ['#6366f1', '#10b981'],

                stroke: {
                    curve: 'smooth',
                    width: 2.5
                },

                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.30,
                        opacityTo: 0.03,
                        stops: [0, 90, 100],
                    },
                },

                dataLabels: {
                    enabled: false
                },

                markers: {
                    size: trend.labels.length <= 1 ? 5 : 0,
                    hover: {
                        size: 5
                    }
                },

                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                    padding: {
                        left: 10,
                        right: 15,
                        top: 5,
                        bottom: 0
                    },
                },

                xaxis: {
                    categories: trend.labels,
                    tickPlacement: 'between',
                    labels: {
                        show: true,
                        rotate: 0,
                        trim: true,
                        hideOverlappingLabels: true,
                        style: {
                            colors: '#64748b',
                            fontSize: '11px',
                            fontFamily: 'Inter, sans-serif',
                        },
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    tooltip: {
                        enabled: false
                    },
                },

                yaxis: [{
                        seriesName: 'Revenue',
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontFamily: 'Inter, sans-serif',
                            },
                            formatter: function(value) {
                                return Number(value).toLocaleString();
                            },
                        },
                    },
                    {
                        seriesName: 'Paid Invoices',
                        opposite: true,
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontFamily: 'Inter, sans-serif',
                            },
                            formatter: function(value) {
                                return Math.round(value);
                            },
                        },
                    },
                ],

                legend: {
                    show: false
                },

                tooltip: {
                    enabled: true,
                    shared: true,
                    intersect: false,
                    followCursor: false,
                    theme: 'light',
                    fixed: {
                        enabled: false
                    },
                    y: {
                        formatter: function(value, context) {
                            const seriesIndex = context && typeof context.seriesIndex === 'number' ?
                                context.seriesIndex :
                                0;

                            if (seriesIndex === 1) {
                                return Number(value).toLocaleString() + ' invoices';
                            }

                            return Number(value).toLocaleString() + ' ' + billingCurrencyCode;
                        },
                    },
                },

                noData: {
                    text: 'No billing data available',
                    align: 'center',
                    verticalAlign: 'middle',
                },
            };
        };

        const refreshBillingChartSize = function() {
            window.dispatchEvent(new Event('resize'));
        };

        const createOrUpdateBillingChart = async function(trend) {
            if (!window.ApexCharts) {
                console.error('[WHMCS Dashboard] ApexCharts is not loaded.');
                return;
            }

            const element = $wire.$el.querySelector('#whmcsBillingChart');

            if (!element) {
                return;
            }

            if (!billingChart) {
                billingChart = new window.ApexCharts(element, billingChartOptions(trend));

                await billingChart.render();
            } else {
                await billingChart.updateOptions({
                    xaxis: {
                        categories: trend.labels
                    },
                    markers: {
                        size: trend.labels.length <= 1 ? 5 : 0,
                        hover: {
                            size: 5
                        }
                    },
                }, false, false);

                await billingChart.updateSeries([{
                        name: 'Revenue',
                        data: trend.revenue
                    },
                    {
                        name: 'Paid Invoices',
                        data: trend.invoices
                    },
                ], true);
            }
        };

        const initializeBillingChart = async function() {
            if (billingChartInitialized) {
                return;
            }

            billingChartInitialized = true;

            await new Promise(function(resolve) {
                requestAnimationFrame(function() {
                    requestAnimationFrame(resolve);
                });
            });

            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (error) {
                    console.warn('[WHMCS Dashboard] Font loading wait failed.', error);
                }
            }

            await new Promise(function(resolve) {
                setTimeout(resolve, 60);
            });

            await createOrUpdateBillingChart(initialBillingTrend);

            requestAnimationFrame(function() {
                refreshBillingChartSize();
            });

            setTimeout(refreshBillingChartSize, 120);
            setTimeout(refreshBillingChartSize, 350);
        };

        initializeBillingChart().catch(function(error) {
            console.error('[WHMCS Dashboard] Chart initialization failed.', error);
        });

        $wire.$on('whmcs-billing-chart-updated', function(event) {
            const payload = event && event.detail ? event.detail : event;

            if (!payload || !payload.trend) {
                console.error('[WHMCS Dashboard] Invalid chart payload:', payload);
                return;
            }

            createOrUpdateBillingChart(payload.trend).then(function() {
                requestAnimationFrame(function() {
                    refreshBillingChartSize();
                });
            });
        });
    </script>
    @endscript

    {{-- Chart CSS --}}
    <style>
        #whmcsBillingChart {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        #whmcsBillingChart .apexcharts-canvas {
            max-width: 100%;
        }

        #whmcsBillingChart svg {
            max-width: 100%;
        }
    </style>
</div>