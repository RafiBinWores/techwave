<?php

use App\Models\Visit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts.admin-app')]
    #[Title('Admin Dashboard')]
    class extends Component
    {
        public string $chartPeriod = '14d';

        #[Computed]
        public function visits(): int
        {
            return Visit::count();
        }

        #[Computed]
        public function uniqueVisitors(): int
        {
            return (int) Visit::query()
                ->whereNotNull('ip_address')
                ->distinct()
                ->count('ip_address');
        }

        #[Computed]
        public function visitsToday(): int
        {
            return Visit::query()
                ->whereDate('created_at', today())
                ->count();
        }

        #[Computed]
        public function visitsTrend(): array
        {
            $days = $this->chartPeriod === '30d' ? 29 : 13;

            $rows = Visit::query()
                ->where(
                    'created_at',
                    '>=',
                    now()->subDays($days)->startOfDay()
                )
                ->selectRaw(
                    '
                        DATE(created_at) as date,
                        COUNT(*) as visits,
                        COUNT(DISTINCT ip_address) as unique_visitors
                    '
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $labels = [];
            $visits = [];
            $unique = [];

            for ($i = $days; $i >= 0; $i--) {

                $date = today()->subDays($i);

                $key = $date->toDateString();

                $labels[] = $date->format('M d');

                $visits[] = (int) (
                    $rows[$key]->visits ?? 0
                );

                $unique[] = (int) (
                    $rows[$key]->unique_visitors ?? 0
                );
            }

            return [
                'labels' => $labels,
                'visits' => $visits,
                'unique' => $unique,
            ];
        }

        #[Computed]
        public function deviceDistribution(): array
        {
            return Visit::query()
                ->selectRaw(
                    "
                        COALESCE(NULLIF(device, ''), 'Other') as name,
                        COUNT(*) as total
                    "
                )
                ->groupBy('name')
                ->orderByDesc('total')
                ->get()
                ->map(fn($row) => [
                    'name' => $row->name,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->toArray();
        }

        public function updatedChartPeriod(): void
        {
            $this->dispatch(
                'visit-chart-updated',
                trend: $this->visitsTrend(),
                device: $this->deviceDistribution(),
            );
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
                Real-time infrastructure performance and operations status.
            </p>

        </div>


        <div class="flex gap-3 basis-1/3 justify-end">

            <button
                type="button"
                class="
                    flex items-center gap-2
                    px-4 py-2
                    shadow
                    bg-white
                    text-xs md:text-base
                    text-on-surface
                    font-label-md
                    rounded
                    hover:bg-surface-container
                    transition-all
                ">

                <span class="material-symbols-outlined">
                    calendar_today
                </span>

                Last 30 Days

            </button>

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
                    12,842
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
                        +4.2%
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
                    43
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
                        8 Critical
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
                    Revenue (MTD)
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
                        text-h1
                        text-on-surface
                    ">
                    $84,200
                </h3>


                <div
                    class="
                        flex items-center
                        text-emerald-600
                        text-xs
                        font-semibold
                    ">

                    <span
                        class="material-symbols-outlined text-sm">
                        trending_up
                    </span>

                    <span>
                        +12%
                    </span>

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
                    24
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
                        3 Near Deadline
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
                        {{ number_format($this->visitsToday) }} today
                    </span>

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
                        all time
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


                <select
                    wire:model.live="chartPeriod"
                    class="
                        shrink-0

                        rounded-lg

                        border
                        border-outline-variant

                        bg-white

                        px-3
                        py-1.5

                        text-sm
                        text-slate-600

                        focus:outline-none
                        focus:ring-2
                        focus:ring-primary/20
                    ">

                    <option value="14d">
                        Last 14 Days
                    </option>

                    <option value="30d">
                        Last 30 Days
                    </option>

                </select>

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


                <button
                    type="button"
                    class="
                        text-primary
                        font-label-md
                        hover:underline
                    ">
                    View All
                </button>

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

                        {{-- Ticket 1 --}}
                        <tr
                            class="
                                hover:bg-slate-50
                                transition-colors
                            ">

                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                    font-mono
                                    text-primary
                                    font-bold
                                ">
                                #TK-8421
                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <p
                                    class="
                                        font-label-md
                                        text-on-surface
                                    ">
                                    Cloud storage quota exceeded
                                </p>

                                <p
                                    class="
                                        text-body-sm
                                        text-secondary
                                    ">
                                    Reporting: Database Sync failure
                                </p>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    ">

                                    <div
                                        class="
                                            h-6
                                            w-6

                                            rounded-full

                                            bg-slate-200

                                            flex
                                            items-center
                                            justify-center

                                            text-[10px]
                                            font-bold
                                        ">
                                        AM
                                    </div>

                                    <span class="text-body-md">
                                        Alex Murphy
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <span
                                    class="
                                        inline-flex
                                        items-center

                                        px-2
                                        py-1

                                        rounded

                                        bg-amber-100
                                        text-amber-700

                                        text-[10px]
                                        font-bold
                                        uppercase
                                        tracking-tight
                                    ">
                                    In Progress
                                </span>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-1.5
                                        text-error
                                    ">

                                    <span
                                        class="
                                            h-2
                                            w-2
                                            rounded-full
                                            bg-error
                                        "></span>

                                    <span class="font-label-sm">
                                        High
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <button
                                    type="button"
                                    class="
                                        p-1
                                        hover:bg-surface-variant
                                        rounded
                                    ">

                                    <span
                                        class="
                                            material-symbols-outlined
                                            text-body-lg
                                        ">
                                        more_vert
                                    </span>

                                </button>

                            </td>

                        </tr>



                        {{-- Ticket 2 --}}
                        <tr
                            class="
                                bg-slate-50/50
                                hover:bg-slate-100
                                transition-colors
                            ">

                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                    font-mono
                                    text-primary
                                    font-bold
                                ">
                                #TK-8420
                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <p
                                    class="
                                        font-label-md
                                        text-on-surface
                                    ">
                                    VPN Access Reset
                                </p>

                                <p
                                    class="
                                        text-body-sm
                                        text-secondary
                                    ">
                                    Standard request from HR
                                </p>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    ">

                                    <div
                                        class="
                                            h-6
                                            w-6

                                            rounded-full

                                            bg-slate-200

                                            flex
                                            items-center
                                            justify-center

                                            text-[10px]
                                            font-bold
                                        ">
                                        SJ
                                    </div>

                                    <span class="text-body-md">
                                        Sarah Jenkins
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <span
                                    class="
                                        inline-flex
                                        items-center

                                        px-2
                                        py-1

                                        rounded

                                        bg-emerald-100
                                        text-emerald-700

                                        text-[10px]
                                        font-bold
                                        uppercase
                                        tracking-tight
                                    ">
                                    Resolved
                                </span>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-1.5
                                        text-secondary
                                    ">

                                    <span
                                        class="
                                            h-2
                                            w-2
                                            rounded-full
                                            bg-secondary
                                        "></span>

                                    <span class="font-label-sm">
                                        Low
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <button
                                    type="button"
                                    class="
                                        p-1
                                        hover:bg-surface-variant
                                        rounded
                                    ">

                                    <span
                                        class="
                                            material-symbols-outlined
                                            text-body-lg
                                        ">
                                        more_vert
                                    </span>

                                </button>

                            </td>

                        </tr>



                        {{-- Ticket 3 --}}
                        <tr
                            class="
                                hover:bg-slate-50
                                transition-colors
                            ">

                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                    font-mono
                                    text-primary
                                    font-bold
                                ">
                                #TK-8419
                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <p
                                    class="
                                        font-label-md
                                        text-on-surface
                                    ">
                                    Critical: Server 04 Unresponsive
                                </p>

                                <p
                                    class="
                                        text-body-sm
                                        text-secondary
                                    ">
                                    Automated Monitoring Alert
                                </p>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    ">

                                    <div
                                        class="
                                            h-6
                                            w-6

                                            rounded-full

                                            bg-primary-container
                                            text-white

                                            flex
                                            items-center
                                            justify-center

                                            text-[10px]
                                            font-bold
                                        ">
                                        SYS
                                    </div>

                                    <span class="text-body-md">
                                        System Bot
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <span
                                    class="
                                        inline-flex
                                        items-center

                                        px-2
                                        py-1

                                        rounded

                                        bg-red-100
                                        text-red-700

                                        text-[10px]
                                        font-bold
                                        uppercase
                                        tracking-tight
                                    ">
                                    Open
                                </span>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-1.5
                                        text-error
                                    ">

                                    <span
                                        class="
                                            h-2
                                            w-2
                                            rounded-full
                                            bg-error
                                            animate-pulse
                                        "></span>

                                    <span class="font-label-sm">
                                        Critical
                                    </span>

                                </div>

                            </td>


                            <td
                                class="
                                    px-stack-lg
                                    py-4
                                ">

                                <button
                                    type="button"
                                    class="
                                        p-1
                                        hover:bg-surface-variant
                                        rounded
                                    ">

                                    <span
                                        class="
                                            material-symbols-outlined
                                            text-body-lg
                                        ">
                                        more_vert
                                    </span>

                                </button>

                            </td>

                        </tr>

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