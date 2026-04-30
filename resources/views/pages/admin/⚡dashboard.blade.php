<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new 
#[Layout('layouts.admin-app')] 
#[Title('Admin Dashboard')]
class extends Component {
    //
};
?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div class="basis-2/3">
            <h2 class="font-h1 text-xl font-bold md:text-h1 text-on-background">System Overview</h2>
            <p class="text-xs md:text-body-md text-on-surface-variant">
                Real-time infrastructure performance and operations status.
            </p>
        </div>

        <div class="flex gap-3 basis-1/3 justify-end">
            <button
                class="flex items-center gap-2 px-4 py-2 shadow bg-white text-xs md:text-base text-on-surface font-label-md rounded hover:bg-surface-container transition-all">
                <span class="material-symbols-outlined">calendar_today</span>
                Last 30 Days
            </button>
        </div>
    </div>

    <!-- Bento Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter mb-stack-lg">

        <div
            class="bg-surface-container-lowest p-stack-md shadow rounded-xl flex flex-col justify-between h-32 transition-all hover:border-primary">
            <div class="flex justify-between items-start">
                <p class="font-label-sm text-secondary uppercase tracking-wider">Total Users</p>
                <span class="material-symbols-outlined text-primary-container">person</span>
            </div>

            <div class="flex items-end justify-between">
                <h3 class="font-h1 text-h1 text-on-surface">12,842</h3>
                <div class="flex items-center text-xs font-semibold text-emerald-600">
                    <span class="material-symbols-outlined text-sm">trending_up</span>
                    <span>+4.2%</span>
                </div>
            </div>
        </div>

        <div
            class="bg-surface-container-lowest p-stack-md shadow rounded-xl flex flex-col justify-between h-32 transition-all hover:border-error">
            <div class="flex justify-between items-start">
                <p class="font-label-sm text-secondary uppercase tracking-wider">Active Tickets</p>
                <span class="material-symbols-outlined text-error">confirmation_number</span>
            </div>

            <div class="flex items-end justify-between">
                <h3 class="font-h1 text-h1 text-on-surface">43</h3>
                <div class="flex items-center text-error text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">priority_high</span>
                    <span>8 Critical</span>
                </div>
            </div>
        </div>

        <div
            class="bg-surface-container-lowest p-stack-md shadow rounded-xl flex flex-col justify-between h-32 transition-all hover:border-primary">
            <div class="flex justify-between items-start">
                <p class="font-label-sm text-secondary uppercase tracking-wider">Revenue (MTD)</p>
                <span class="material-symbols-outlined text-tertiary-container">payments</span>
            </div>

            <div class="flex items-end justify-between">
                <h3 class="font-h1 text-h1 text-on-surface">$84,200</h3>
                <div class="flex items-center text-emerald-600 text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">trending_up</span>
                    <span>+12%</span>
                </div>
            </div>
        </div>

        <div
            class="bg-surface-container-lowest p-stack-md shadow rounded-xl flex flex-col justify-between h-32 transition-all hover:border-primary">
            <div class="flex justify-between items-start">
                <p class="font-label-sm text-secondary uppercase tracking-wider">Active Projects</p>
                <span class="material-symbols-outlined text-primary">account_tree</span>
            </div>

            <div class="flex items-end justify-between">
                <h3 class="font-h1 text-h1 text-on-surface">24</h3>
                <div class="flex items-center text-slate-500 text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    <span>3 Near Deadline</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-12 gap-gutter">

        <!-- Chart Area -->
        <div
            class="col-span-12 lg:col-span-8 bg-surface-container-lowest shadow rounded-xl p-stack-lg">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h4 class="font-h3 text-h3 text-on-surface">Ticket Volume Trends</h4>
                    <p class="text-body-sm text-on-surface-variant">
                        Daily ticket submission vs resolution rates
                    </p>
                </div>

                <div class="flex items-center flex-col md:flex-row gap-4">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-primary-container"></span>
                        <span class="text-body-sm text-on-surface-variant">Created</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-tertiary-fixed-dim"></span>
                        <span class="text-body-sm text-on-surface-variant">Resolved</span>
                    </div>
                </div>
            </div>

            <div class="h-72 w-full flex items-end justify-between gap-2 px-4">
                @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $day)
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full flex items-end gap-1 h-48">
                            <div class="bg-primary-container/20 w-full h-3/4 rounded-t-sm"></div>
                            <div class="bg-primary-container w-full h-full rounded-t-sm"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 font-bold">{{ $day }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Secondary Metric Card -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-gutter">
            <div
                class="bg-primary-container text-on-primary-container rounded-xl p-stack-lg h-full relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="font-label-sm uppercase opacity-80 mb-2">Service Availability</p>
                    <h4 class="font-h1 text-h1 mb-6">99.98%</h4>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-body-sm">
                            <span>Cloud Infrastructure</span>
                            <span class="font-bold">UP</span>
                        </div>

                        <div class="w-full bg-white/20 h-1.5 rounded-full">
                            <div class="bg-white h-full w-[99.9%] rounded-full"></div>
                        </div>

                        <div class="flex items-center justify-between text-body-sm pt-2">
                            <span>Internal Database</span>
                            <span class="font-bold">UP</span>
                        </div>

                        <div class="w-full bg-white/20 h-1.5 rounded-full">
                            <div class="bg-white h-full w-[98.2%] rounded-full"></div>
                        </div>
                    </div>
                </div>

                <span class="material-symbols-outlined absolute -bottom-8 -right-8 text-[160px] opacity-10 rotate-12">
                    cloud_done
                </span>
            </div>
        </div>

        <!-- Recent Tickets Table -->
        <div class="col-span-12 bg-surface-container-lowest shadow rounded-xl overflow-hidden">
            <div
                class="px-stack-lg py-stack-md border-b border-outline-variant flex items-center justify-between bg-surface-container-lowest">
                <h4 class="font-h3 text-h3 text-on-surface">Recent Support Tickets</h4>
                <button class="text-primary font-label-md hover:underline">View All</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">TICKET ID</th>
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">SUBJECT</th>
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">REQUESTER</th>
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">STATUS</th>
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">PRIORITY</th>
                            <th class="px-stack-lg py-3 font-label-sm text-secondary">ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-outline-variant">
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-stack-lg py-4 font-mono text-primary font-bold">#TK-8421</td>
                            <td class="px-stack-lg py-4">
                                <p class="font-label-md text-on-surface">Cloud storage quota exceeded</p>
                                <p class="text-body-sm text-secondary">Reporting: Database Sync failure</p>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-6 w-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold">
                                        AM
                                    </div>
                                    <span class="text-body-md">Alex Murphy</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-tight">
                                    In Progress
                                </span>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-1.5 text-error">
                                    <span class="h-2 w-2 rounded-full bg-error"></span>
                                    <span class="font-label-sm">High</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <button class="p-1 hover:bg-surface-variant rounded">
                                    <span class="material-symbols-outlined text-body-lg">more_vert</span>
                                </button>
                            </td>
                        </tr>

                        <tr class="bg-slate-50/50 hover:bg-slate-100 transition-colors">
                            <td class="px-stack-lg py-4 font-mono text-primary font-bold">#TK-8420</td>
                            <td class="px-stack-lg py-4">
                                <p class="font-label-md text-on-surface">VPN Access Reset</p>
                                <p class="text-body-sm text-secondary">Standard request from HR</p>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-6 w-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold">
                                        SJ
                                    </div>
                                    <span class="text-body-md">Sarah Jenkins</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-tight">
                                    Resolved
                                </span>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-1.5 text-secondary">
                                    <span class="h-2 w-2 rounded-full bg-secondary"></span>
                                    <span class="font-label-sm">Low</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <button class="p-1 hover:bg-surface-variant rounded">
                                    <span class="material-symbols-outlined text-body-lg">more_vert</span>
                                </button>
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-stack-lg py-4 font-mono text-primary font-bold">#TK-8419</td>
                            <td class="px-stack-lg py-4">
                                <p class="font-label-md text-on-surface">Critical: Server 04 Unresponsive</p>
                                <p class="text-body-sm text-secondary">Automated Monitoring Alert</p>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-6 w-6 rounded-full bg-primary-container text-white flex items-center justify-center text-[10px] font-bold">
                                        SYS
                                    </div>
                                    <span class="text-body-md">System Bot</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-tight">
                                    Open
                                </span>
                            </td>
                            <td class="px-stack-lg py-4">
                                <div class="flex items-center gap-1.5 text-error">
                                    <span class="h-2 w-2 rounded-full bg-error animate-pulse"></span>
                                    <span class="font-label-sm">Critical</span>
                                </div>
                            </td>
                            <td class="px-stack-lg py-4">
                                <button class="p-1 hover:bg-surface-variant rounded">
                                    <span class="material-symbols-outlined text-body-lg">more_vert</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
