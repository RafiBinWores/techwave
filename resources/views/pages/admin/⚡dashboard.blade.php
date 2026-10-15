<?php

use App\Models\SupportTicket;
use App\Models\Visit;
use App\Support\Admin\DashboardOverview;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts.admin-app')]
    #[Title('Admin Dashboard')]
    class extends Component
    {
        public string $period = '30d';

        #[Computed]
        public function periodLabel(): string
        {
            return DashboardOverview::PERIODS[$this->period] ?? DashboardOverview::PERIODS['30d'];
        }

        #[Computed]
        public function overview(): array
        {
            return app(DashboardOverview::class)->metrics($this->period);
        }

        #[Computed]
        public function revenueTrend(): array
        {
            return app(DashboardOverview::class)->revenueTrend($this->period, $this->overview['monthlyRevenue']);
        }

        #[Computed]
        public function recentTickets()
        {
            return app(DashboardOverview::class)->applyRange(SupportTicket::query(), $this->period)
                ->with('user')->latest()->orderByDesc('id')->limit(5)->get();
        }

        private function visitQuery(): Builder
        {
            return app(DashboardOverview::class)->applyRange(Visit::query(), $this->period);
        }

        #[Computed]
        public function visits(): int
        {
            return $this->visitQuery()->count();
        }

        #[Computed]
        public function uniqueVisitors(): int
        {
            return (int) $this->visitQuery()->whereNotNull('ip_address')->distinct()->count('ip_address');
        }

        #[Computed]
        public function visitChange(): array
        {
            return app(DashboardOverview::class)->visitorTrend($this->period, $this->visits);
        }

        #[Computed]
        public function visitsTrend(): array
        {
            [$start, $end] = app(DashboardOverview::class)->range($this->period);
            $query = $this->visitQuery();
            $start ??= CarbonImmutable::parse((clone $query)->min('created_at') ?? today())->startOfDay();
            $monthly = $start->diffInDays($end) > 90;
            $dateExpression = $monthly ? 'SUBSTR(created_at, 1, 7)' : 'DATE(created_at)';
            $rows = $query
                ->selectRaw($dateExpression.' as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors')
                ->groupBy('date')->orderBy('date')->get();

            $buckets = [];
            foreach ($rows as $row) {
                $buckets[$row->date] = ['visits' => (int) $row->visits, 'unique' => (int) $row->unique_visitors];
            }

            $labels = [];
            $visits = [];
            $unique = [];
            for ($date = $monthly ? $start->startOfMonth() : $start; $date <= $end; $date = $monthly ? $date->addMonth() : $date->addDay()) {
                $key = $date->format($monthly ? 'Y-m' : 'Y-m-d');
                $labels[] = $date->format($monthly ? 'M Y' : 'M d');
                $visits[] = $buckets[$key]['visits'] ?? 0;
                $unique[] = $buckets[$key]['unique'] ?? 0;
            }

            return ['labels' => $labels, 'visits' => $visits, 'unique' => $unique];
        }

        #[Computed]
        public function deviceDistribution(): array
        {
            return $this->visitQuery()
                ->selectRaw("COALESCE(NULLIF(device, ''), 'Other') as name, COUNT(*) as total")
                ->groupBy('name')->orderByDesc('total')->get()
                ->map(fn ($row) => ['name' => $row->name, 'total' => (int) $row->total])
                ->values()->toArray();
        }

        public function updatedPeriod(): void
        {
            if (! array_key_exists($this->period, DashboardOverview::PERIODS)) {
                $this->period = '30d';
            }

            unset($this->periodLabel, $this->overview, $this->revenueTrend, $this->visitChange, $this->recentTickets, $this->visits, $this->uniqueVisitors, $this->visitsTrend, $this->deviceDistribution);
            $this->dispatch('visit-chart-updated', trend: $this->visitsTrend, device: $this->deviceDistribution);
        }
    };
?>

<div>

    {{-- ============================================================ --}}
    {{-- Page Header --}}
    {{-- ============================================================ --}}

    <div class="flex items-center justify-between mb-8">

        <div class="basis-2/3">

            <h2
                class="font-h1 text-xl font-bold md:text-h1 text-on-background">
                System Overview
            </h2>

            <p
                class="text-xs md:text-body-md text-on-surface-variant">
                Users, support tickets, billing, projects, and site activity.
            </p>

        </div>


        <div class="flex basis-1/3 justify-end">
            <div class="relative flex items-center rounded bg-white shadow text-on-surface">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 text-lg" aria-hidden="true">calendar_today</span>
                <label for="dashboard-period" class="sr-only">Dashboard date range</label>
                <select id="dashboard-period" wire:model.live="period"
                    class="cursor-pointer appearance-none rounded border-0 bg-transparent py-2 pl-10 pr-9 text-xs md:text-base font-label-md focus:ring-2 focus:ring-primary/20">
                    @foreach (\App\Support\Admin\DashboardOverview::PERIODS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-2 text-lg" aria-hidden="true">expand_more</span>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- Metric Cards --}}
    {{-- ============================================================ --}}

    <div
        class="
            grid
            grid-cols-1
            md:grid-cols-2
            lg:grid-cols-4
            gap-gutter
            mb-stack-lg
        ">

        {{-- Total Users --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-primary
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Total Users
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-primary-container
                    ">
                    person
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1
                        text-on-surface
                    ">
                    {{ number_format($this->overview['totalUsers']) }}
                </h3>


                <div
                    class="
                        flex items-center
                        text-xs
                        font-semibold
                        text-emerald-600
                    ">

                    <span
                        class="material-symbols-outlined text-sm">
                        trending_up
                    </span>

                    <span>
                        {{ number_format($this->overview['newUsers']) }} new
                    </span>

                </div>

            </div>

        </div>



        {{-- Active Tickets --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-error
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Active Tickets
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-error
                    ">
                    confirmation_number
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1
                        text-on-surface
                    ">
                    {{ number_format($this->overview['activeTickets']) }}
                </h3>


                <div
                    class="
                        flex items-center
                        text-error
                        text-xs
                        font-semibold
                    ">

                    <span
                        class="material-symbols-outlined text-sm">
                        priority_high
                    </span>

                    <span>
                        {{ number_format($this->overview['highPriorityTickets']) }} High / Urgent
                    </span>

                </div>

            </div>

        </div>



        {{-- Revenue --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-primary
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Revenue{{ $this->period === 'month' ? ' (MTD)' : '' }}
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-tertiary-container
                    ">
                    payments
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1 inline-flex items-center gap-1
                        text-on-surface
                    ">
                    @if (config('app.currency_code', 'BDT') === 'BDT')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bangladeshi-taka preview-icon shrink-0" role="img" aria-label="Bangladeshi Taka"><path d="M6 5a2 2 0 0 1 4 0v12a4 4 0 0 0 8 0 2 2 0 0 0-4 0"/><path d="M6 9h12"/></svg>
                    @else
                        <span class="text-base">{{ config('app.currency_code') }}</span>
                    @endif
                    {{ number_format($this->overview['monthlyRevenue']) }}
                </h3>


                <div
                    @class([
                        'flex items-center text-xs font-semibold',
                        'text-emerald-600' => $this->revenueTrend['direction'] === 'up',
                        'text-red-600' => $this->revenueTrend['direction'] === 'down',
                        'text-slate-500' => $this->revenueTrend['direction'] === 'flat',
                    ])
                    title="{{ $this->revenueTrend['description'] }}">

                    <span class="material-symbols-outlined text-sm" aria-hidden="true">
                        {{ match ($this->revenueTrend['direction']) { 'up' => 'trending_up', 'down' => 'trending_down', default => 'trending_flat' } }}
                    </span>

                    <span>{{ $this->revenueTrend['label'] }}</span>

                </div>

            </div>

        </div>



        {{-- Active Projects --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-primary
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Active Projects
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-primary
                    ">
                    account_tree
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1
                        text-on-surface
                    ">
                    {{ number_format($this->overview['activeProjects']) }}
                </h3>


                <div
                    class="
                        flex items-center
                        text-slate-500
                        text-xs
                        font-semibold
                    ">

                    <span
                        class="material-symbols-outlined text-sm">
                        schedule
                    </span>

                    <span>
                        {{ number_format($this->overview['nearDeadlineProjects']) }} {{ match ($this->period) { 'today' => 'due today', '7d' => 'due in last 7 days', 'month' => 'due month to date', 'all' => 'due through today', default => 'due in last 30 days' } }}
                    </span>

                </div>

            </div>

        </div>



        {{-- Total Visits --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-primary
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Total Visits
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-primary-container
                    ">
                    visibility
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1
                        text-on-surface
                    ">
                    {{ number_format($this->visits) }}
                </h3>


                <div
                    @class([
                        'flex items-center text-xs font-semibold',
                        'text-emerald-600' => $this->visitChange['direction'] === 'up',
                        'text-red-600' => $this->visitChange['direction'] === 'down',
                        'text-slate-500' => $this->visitChange['direction'] === 'flat',
                    ])
                    title="{{ $this->visitChange['description'] }}">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">
                        {{ match ($this->visitChange['direction']) { 'up' => 'trending_up', 'down' => 'trending_down', default => 'trending_flat' } }}
                    </span>
                    <span>{{ $this->visitChange['label'] }}</span>
                </div>

            </div>

        </div>



        {{-- Unique Visitors --}}
        <div
            class="
                bg-surface-container-lowest
                p-stack-md
                shadow
                rounded-xl
                flex flex-col
                justify-between
                h-32
                transition-all
                hover:border-primary
            ">

            <div class="flex justify-between items-start">

                <p
                    class="
                        font-label-sm
                        text-secondary
                        uppercase
                        tracking-wider
                    ">
                    Unique Visitors
                </p>

                <span
                    class="
                        material-symbols-outlined
                        text-primary-container
                    ">
                    group
                </span>

            </div>


            <div class="flex items-end justify-between">

                <h3
                    class="
                        font-h1
                        text-h1
                        text-on-surface
                    ">
                    {{ number_format($this->uniqueVisitors) }}
                </h3>


                <div
                    class="
                        flex items-center
                        text-xs
                        font-semibold
                        text-slate-500
                    ">

                    <span
                        class="material-symbols-outlined text-sm">
                        ads_click
                    </span>

                    <span>
                        {{ $this->periodLabel }}
                    </span>

                </div>

            </div>

        </div>

    </div>



    {{-- ============================================================ --}}
    {{-- Main Dashboard Grid --}}
    {{-- ============================================================ --}}

    <div
        class="
            grid
            grid-cols-12
            gap-gutter
            items-stretch
        ">

        {{-- ======================================================== --}}
        {{-- Visit Analytics Chart --}}
        {{-- ======================================================== --}}

        <div
            class="
                col-span-12
                lg:col-span-8

                bg-surface-container-lowest
                shadow
                rounded-xl

                p-stack-lg

                h-[520px]

                min-w-0
                max-w-full

                flex
                flex-col
            ">

            {{-- Chart Header --}}
            <div
                class="
                    flex
                    items-center
                    justify-between
                    gap-4
                    mb-6
                    shrink-0
                ">

                <div class="min-w-0">

                    <h4
                        class="
                            font-h3
                            text-h3
                            text-on-surface
                        ">
                        Site Visit Analytics
                    </h4>


                    <p
                        class="
                            text-body-sm
                            text-on-surface-variant
                        ">
                        Total visits vs unique visitors over time
                    </p>

                </div>


                <span class="shrink-0 rounded-lg bg-surface-container-low px-3 py-1.5 text-xs text-secondary">{{ $this->periodLabel }}</span>

            </div>



            {{-- Custom Legend --}}
            <div
                class="
                    flex
                    items-center
                    flex-wrap
                    gap-4

                    mb-3

                    shrink-0
                ">

                <div class="flex items-center gap-2">

                    <span
                        class="
                            h-3
                            w-3
                            rounded-full
                        "
                        style="background-color:#6366f1"></span>

                    <span
                        class="
                            text-body-sm
                            text-on-surface-variant
                        ">
                        Visits
                    </span>

                </div>



                <div class="flex items-center gap-2">

                    <span
                        class="
                            h-3
                            w-3
                            rounded-full
                        "
                        style="background-color:#10b981"></span>

                    <span
                        class="
                            text-body-sm
                            text-on-surface-variant
                        ">
                        Unique Visitors
                    </span>

                </div>

            </div>



            {{-- Chart Wrapper --}}
            <div
                class="
                    relative
                    flex-1
                    min-h-0
                    min-w-0
                    max-w-full
                ">

                <div
                    id="visitTrendChart"
                    wire:ignore
                    class="
                        absolute
                        inset-0

                        w-full
                        h-full

                        min-w-0
                        max-w-full
                    "></div>

            </div>

        </div>



        {{-- ======================================================== --}}
        {{-- Device Distribution --}}
        {{-- ======================================================== --}}

        <div
            class="
                col-span-12
                lg:col-span-4

                bg-surface-container-lowest
                shadow
                rounded-xl

                p-stack-lg

                h-[520px]

                min-w-0
                max-w-full

                flex
                flex-col
            ">

            <div class="mb-4 shrink-0">

                <p
                    class="
                        font-label-sm
                        uppercase
                        text-secondary
                    ">
                    Visits by Device
                </p>

                <p
                    class="
                        text-xs
                        text-on-surface-variant
                        mt-1
                    ">
                    Visitor device distribution
                </p>

            </div>



            <div
                class="
                    flex-1
                    min-h-0
                    min-w-0

                    flex
                    items-center
                    justify-center
                ">

                <div
                    id="deviceChart"
                    wire:ignore
                    class="
                        w-full
                        min-w-0
                        max-w-full
                    "></div>

            </div>

        </div>



        {{-- ======================================================== --}}
        {{-- Recent Support Tickets --}}
        {{-- ======================================================== --}}

        <div
            class="
                col-span-12

                bg-surface-container-lowest
                shadow
                rounded-xl

                overflow-hidden
            ">

            <div
                class="
                    px-stack-lg
                    py-stack-md

                    border-b
                    border-outline-variant

                    flex
                    items-center
                    justify-between

                    bg-surface-container-lowest
                ">

                <h4
                    class="
                        font-h3
                        text-h3
                        text-on-surface
                    ">
                    Recent Support Tickets
                </h4>


                <a href="{{ route('admin.tickets.index') }}" wire:navigate class="text-primary font-label-md hover:underline">View All</a>

            </div>



            <div class="overflow-x-auto">

                <table
                    class="
                        w-full
                        text-left
                        border-collapse
                    ">

                    <thead>

                        <tr
                            class="
                                bg-surface-container-low
                            ">

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                TICKET ID
                            </th>

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                SUBJECT
                            </th>

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                REQUESTER
                            </th>

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                STATUS
                            </th>

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                PRIORITY
                            </th>

                            <th
                                class="
                                    px-stack-lg
                                    py-3
                                    font-label-sm
                                    text-secondary
                                ">
                                ACTIONS
                            </th>

                        </tr>

                    </thead>



                    <tbody
                        class="
                            divide-y
                            divide-outline-variant
                        ">

                        @forelse ($this->recentTickets as $ticket)
                        <tr wire:key="dashboard-ticket-{{ $ticket->id }}" class="hover:bg-slate-50 transition-colors">
                            <td class="px-stack-lg py-4 font-mono text-primary font-bold">{{ $ticket->ticket_no }}</td>
                            <td class="px-stack-lg py-4">
                                <p class="font-label-md text-on-surface">{{ $ticket->subject }}</p>
                                <p class="text-body-sm text-secondary">{{ $ticket->department }}</p>
                            </td>
                            <td class="px-stack-lg py-4 text-body-md">{{ $ticket->customer_name ?: ($ticket->user?->name ?: $ticket->customer_email ?: 'Guest') }}</td>
                            <td class="px-stack-lg py-4">
                                <span @class([ 'inline-flex px-2 py-1 rounded text-[10px] font-bold uppercase' , 'bg-blue-100 text-blue-700'=> $ticket->status === 'open',
                                    'bg-amber-100 text-amber-700' => $ticket->status === 'pending',
                                    'bg-emerald-100 text-emerald-700' => $ticket->status === 'answered',
                                    'bg-slate-100 text-slate-600' => $ticket->status === 'closed',
                                    ])>{{ ucfirst($ticket->status) }}</span>
                            </td>
                            <td class="px-stack-lg py-4">
                                <span @class([ 'font-label-sm' , 'text-error'=> in_array($ticket->priority, ['high', 'urgent']),
                                    'text-secondary' => !in_array($ticket->priority, ['high', 'urgent']),
                                    ])>{{ ucfirst($ticket->priority) }}</span>
                            </td>
                            <td class="px-stack-lg py-4">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" wire:navigate class="text-primary hover:underline" aria-label="View ticket {{ $ticket->ticket_no }}">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-stack-lg py-8 text-center text-secondary">No support tickets in this period.</td>
                        </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    {{-- ============================================================ --}}
    {{-- Initial Chart Data --}}
    {{-- ============================================================ --}}

    @php
    $initialTrendData = $this->visitsTrend();
    $initialDeviceData = $this->deviceDistribution();
    @endphp



    {{-- ============================================================ --}}
    {{-- ApexCharts --}}
    {{-- ============================================================ --}}

    @script
    <script>
        let trendChart = null;
        let deviceChart = null;

        let dashboardChartInitialized = false;



        /*
        |--------------------------------------------------------------------------
        | Trend Chart Options
        |--------------------------------------------------------------------------
        */

        const trendChartOptions = function(trend) {

            return {

                chart: {

                    id: 'visit-trend-chart',

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
                        }

                    }

                },


                series: [

                    {
                        name: 'Visits',
                        data: trend.visits
                    },

                    {
                        name: 'Unique Visitors',
                        data: trend.unique
                    }

                ],


                colors: [

                    '#6366f1',

                    '#10b981'

                ],


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

                        stops: [
                            0,
                            90,
                            100
                        ]

                    }

                },


                dataLabels: {
                    enabled: false
                },


                markers: {

                    size: 0,

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

                    }

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

                            fontFamily: 'Inter, sans-serif'

                        }

                    },

                    axisBorder: {
                        show: false
                    },

                    axisTicks: {
                        show: false
                    },

                    tooltip: {
                        enabled: false
                    }

                },


                yaxis: {

                    min: 0,

                    forceNiceScale: true,

                    labels: {

                        style: {

                            colors: '#64748b',

                            fontSize: '11px',

                            fontFamily: 'Inter, sans-serif'

                        },

                        formatter: function(value) {

                            return Math.round(value);

                        }

                    }

                },


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

                        formatter: function(value) {

                            return Number(value).toLocaleString();

                        }

                    }

                },


                noData: {

                    text: 'No visit data available',

                    align: 'center',

                    verticalAlign: 'middle'

                }

            };

        };



        /*
        |--------------------------------------------------------------------------
        | Device Chart Options
        |--------------------------------------------------------------------------
        */

        const deviceChartOptions = function(device) {

            const totals = device.map(function(item) {

                return Number(item.total);

            });


            const labels = device.map(function(item) {

                return item.name;

            });


            return {

                chart: {

                    id: 'device-distribution-chart',

                    type: 'donut',

                    width: '100%',

                    height: 350,

                    parentHeightOffset: 0,

                    fontFamily: 'Inter, sans-serif',

                    toolbar: {
                        show: false
                    },

                    redrawOnParentResize: true,

                    redrawOnWindowResize: true,

                    animations: {

                        enabled: true,

                        easing: 'easeinout',

                        speed: 450

                    }

                },


                series: totals,


                labels: labels,


                colors: [

                    '#6366f1',

                    '#10b981',

                    '#f59e0b',

                    '#ef4444',

                    '#8b5cf6',

                    '#06b6d4',

                    '#ec4899',

                    '#84cc16'

                ],


                stroke: {

                    show: true,

                    width: 2,

                    colors: [
                        '#ffffff'
                    ]

                },


                dataLabels: {
                    enabled: false
                },


                legend: {

                    show: true,

                    position: 'bottom',

                    horizontalAlign: 'center',

                    fontSize: '12px',

                    fontFamily: 'Inter, sans-serif',

                    markers: {
                        size: 6
                    },

                    itemMargin: {
                        horizontal: 8,
                        vertical: 5
                    }

                },


                plotOptions: {

                    pie: {

                        expandOnClick: true,

                        donut: {

                            size: '72%',

                            labels: {

                                show: true,


                                name: {

                                    show: true,

                                    fontSize: '12px',

                                    fontWeight: 500,

                                    color: '#64748b',

                                    offsetY: -2

                                },


                                value: {

                                    show: true,

                                    fontSize: '22px',

                                    fontWeight: 700,

                                    color: '#0f172a',

                                    offsetY: 2,

                                    formatter: function(value) {

                                        return Number(
                                            value
                                        ).toLocaleString();

                                    }

                                },


                                total: {

                                    show: true,

                                    showAlways: true,

                                    label: 'Total Visits',

                                    fontSize: '12px',

                                    fontWeight: 500,

                                    color: '#64748b',

                                    formatter: function(chart) {

                                        const total =
                                            chart.globals
                                            .seriesTotals
                                            .reduce(
                                                function(
                                                    sum,
                                                    value
                                                ) {

                                                    return (
                                                        sum +
                                                        value
                                                    );

                                                },
                                                0
                                            );

                                        return total.toLocaleString();

                                    }

                                }

                            }

                        }

                    }

                },


                tooltip: {

                    enabled: true,

                    y: {

                        formatter: function(value) {

                            return (
                                Number(
                                    value
                                ).toLocaleString() +
                                ' visits'
                            );

                        }

                    }

                },


                noData: {

                    text: 'No device data available',

                    align: 'center',

                    verticalAlign: 'middle'

                },


                responsive: [

                    {

                        breakpoint: 640,

                        options: {

                            chart: {
                                height: 300
                            },

                            legend: {
                                position: 'bottom'
                            }

                        }

                    }

                ]

            };

        };



        /*
        |--------------------------------------------------------------------------
        | Create Or Update Charts
        |--------------------------------------------------------------------------
        */

        const createOrUpdateCharts = async function(
            trend,
            device
        ) {

            if (!window.ApexCharts) {

                console.error(
                    '[Dashboard] ApexCharts is not loaded.'
                );

                return;

            }


            const trendElement =
                $wire.$el.querySelector(
                    '#visitTrendChart'
                );


            const deviceElement =
                $wire.$el.querySelector(
                    '#deviceChart'
                );


            if (
                !trendElement ||
                !deviceElement
            ) {

                return;

            }



            /*
            |--------------------------------------------------------------------------
            | Trend chart
            |--------------------------------------------------------------------------
            */

            if (!trendChart) {

                trendChart =
                    new window.ApexCharts(
                        trendElement,
                        trendChartOptions(
                            trend
                        )
                    );


                await trendChart.render();

            } else {

                await trendChart.updateOptions({

                        xaxis: {

                            categories: trend.labels

                        }

                    },
                    false,
                    false
                );


                await trendChart.updateSeries(
                    [

                        {
                            name: 'Visits',
                            data: trend.visits
                        },

                        {
                            name: 'Unique Visitors',
                            data: trend.unique
                        }

                    ],
                    true
                );

            }



            /*
            |--------------------------------------------------------------------------
            | Device chart
            |--------------------------------------------------------------------------
            */

            if (!deviceChart) {

                deviceChart =
                    new window.ApexCharts(
                        deviceElement,
                        deviceChartOptions(
                            device
                        )
                    );


                await deviceChart.render();

            } else {

                await deviceChart.updateOptions({

                        labels: device.map(
                            function(item) {

                                return item.name;

                            }
                        )

                    },
                    false,
                    false
                );


                await deviceChart.updateSeries(
                    device.map(
                        function(item) {

                            return Number(
                                item.total
                            );

                        }
                    ),
                    true
                );

            }

        };



        /*
        |--------------------------------------------------------------------------
        | Force ApexCharts Width Recalculation
        |--------------------------------------------------------------------------
        */

        const refreshDashboardChartSize = function() {

            window.dispatchEvent(
                new Event('resize')
            );

        };



        /*
        |--------------------------------------------------------------------------
        | Initial Data
        |--------------------------------------------------------------------------
        */

        const initialTrend =
            @json($initialTrendData);


        const initialDevice =
            @json($initialDeviceData);



        /*
        |--------------------------------------------------------------------------
        | Initial Chart Render
        |--------------------------------------------------------------------------
        |
        | Important:
        |
        | Do NOT render ApexCharts immediately.
        |
        | On initial browser load the Tailwind grid / Alpine sidebar can still
        | be calculating its final width.
        |
        | We wait for:
        |
        | 1. Browser layout
        | 2. Browser paint
        | 3. Fonts
        | 4. Sidebar / dashboard layout
        |
        */

        const initializeDashboardCharts =
            async function() {

                if (
                    dashboardChartInitialized
                ) {

                    return;

                }


                dashboardChartInitialized =
                    true;



                /*
                |--------------------------------------------------------------------------
                | Wait for browser layout
                |--------------------------------------------------------------------------
                */

                await new Promise(
                    function(resolve) {

                        requestAnimationFrame(
                            function() {

                                requestAnimationFrame(
                                    resolve
                                );

                            }
                        );

                    }
                );



                /*
                |--------------------------------------------------------------------------
                | Wait for fonts
                |--------------------------------------------------------------------------
                */

                if (
                    document.fonts &&
                    document.fonts.ready
                ) {

                    try {

                        await document.fonts.ready;

                    } catch (error) {

                        console.warn(
                            '[Dashboard] Font loading wait failed.',
                            error
                        );

                    }

                }



                /*
                |--------------------------------------------------------------------------
                | Small layout settle delay
                |--------------------------------------------------------------------------
                */

                await new Promise(
                    function(resolve) {

                        setTimeout(
                            resolve,
                            60
                        );

                    }
                );



                /*
                |--------------------------------------------------------------------------
                | Create Charts
                |--------------------------------------------------------------------------
                */

                await createOrUpdateCharts(
                    initialTrend,
                    initialDevice
                );



                /*
                |--------------------------------------------------------------------------
                | Force recalculation after first paint
                |--------------------------------------------------------------------------
                */

                requestAnimationFrame(
                    function() {

                        refreshDashboardChartSize();

                    }
                );



                /*
                |--------------------------------------------------------------------------
                | Alpine / Sidebar transition recalculation
                |--------------------------------------------------------------------------
                */

                setTimeout(
                    function() {

                        refreshDashboardChartSize();

                    },
                    120
                );


                setTimeout(
                    function() {

                        refreshDashboardChartSize();

                    },
                    350
                );

            };



        initializeDashboardCharts();



        /*
        |--------------------------------------------------------------------------
        | Livewire Period Update
        |--------------------------------------------------------------------------
        */

        $wire.$on(
            'visit-chart-updated',

            function(event) {

                const payload =
                    event &&
                    event.detail ?
                    event.detail :
                    event;


                if (
                    !payload ||
                    !payload.trend ||
                    !payload.device
                ) {

                    console.error(
                        '[Dashboard] Invalid chart payload:',
                        payload
                    );

                    return;

                }


                createOrUpdateCharts(
                    payload.trend,
                    payload.device
                ).then(
                    function() {

                        requestAnimationFrame(
                            function() {

                                refreshDashboardChartSize();

                            }
                        );

                    }
                );

            }
        );



        /*
        |--------------------------------------------------------------------------
        | Window Resize
        |--------------------------------------------------------------------------
        */

        let dashboardResizeTimer =
            null;


        window.addEventListener(
            'resize',

            function() {

                clearTimeout(
                    dashboardResizeTimer
                );


                dashboardResizeTimer =
                    setTimeout(
                        function() {

                            /*
                             * ApexCharts already responds to window resize.
                             * This timeout simply lets the CSS grid finish
                             * resizing before the next frame.
                             */

                        },
                        100
                    );

            }
        );
    </script>
    @endscript



    {{-- ============================================================ --}}
    {{-- Chart CSS --}}
    {{-- ============================================================ --}}

    <style>
        #visitTrendChart,
        #deviceChart {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }


        #visitTrendChart .apexcharts-canvas,
        #deviceChart .apexcharts-canvas {
            max-width: 100%;
        }


        #visitTrendChart svg,
        #deviceChart svg {
            max-width: 100%;
        }
    </style>

</div>