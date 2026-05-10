<?php

use Livewire\Component;

new class extends Component {
//
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">
                <!-- mobile overlay -->
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;"></div>

                    {{-- Sidebar --}}
                    <livewire:shared.user-sidebar/>

                <!-- main -->
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                    <!-- top header -->
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Client Dashboard</p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">Overview</h1>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative w-full max-w-md">
                                <input type="text" placeholder="Search..."
                                    class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 pl-12 pr-4 text-sm text-white placeholder:text-blue-100/35 outline-none backdrop-blur-xl">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-blue-100/45"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- content -->
                    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                        <!-- left content -->
                        <div class="space-y-6">
                            <!-- stats -->
                            <div class="grid gap-5 md:grid-cols-3">
                                <div class="client-card p-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Active Services</p>
                                    <h3 class="mt-5 text-4xl font-bold text-white">04</h3>
                                    <p class="mt-2 text-sm text-blue-100/60">Currently running services</p>
                                </div>

                                <div class="client-card p-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Open Tickets</p>
                                    <h3 class="mt-5 text-4xl font-bold text-white">02</h3>
                                    <p class="mt-2 text-sm text-blue-100/60">Support requests in progress</p>
                                </div>

                                <div class="client-card p-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Proposals</p>
                                    <h3 class="mt-5 text-4xl font-bold text-white">03</h3>
                                    <p class="mt-2 text-sm text-blue-100/60">Pending and reviewed proposals</p>
                                </div>
                            </div>

                            <!-- services / tickets table -->
                            <div class="client-card p-6">
                                <div class="mb-5 flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Recent Services</p>
                                        <h2 class="mt-2 text-2xl font-bold text-white">Service activity</h2>
                                    </div>

                                    <a href="{{ route('account.services') }}" wire:navigate
                                        class="text-sm font-medium text-cyan-200 hover:text-white">
                                        View all
                                    </a>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-left">
                                        <thead>
                                            <tr class="border-b border-white/10 text-sm text-blue-100/45">
                                                <th class="px-3 py-3 font-medium">Service</th>
                                                <th class="px-3 py-3 font-medium">Status</th>
                                                <th class="px-3 py-3 font-medium">Started</th>
                                                <th class="px-3 py-3 font-medium">Next Step</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-sm text-blue-50/90">
                                            <tr class="border-b border-white/10">
                                                <td class="px-3 py-4 font-semibold">Managed IT Support</td>
                                                <td class="px-3 py-4"><span class="client-badge client-badge-green">Active</span></td>
                                                <td class="px-3 py-4">12 May 2026</td>
                                                <td class="px-3 py-4">Monthly review</td>
                                            </tr>

                                            <tr class="border-b border-white/10">
                                                <td class="px-3 py-4 font-semibold">Website Maintenance</td>
                                                <td class="px-3 py-4"><span class="client-badge client-badge-blue">Ongoing</span></td>
                                                <td class="px-3 py-4">05 May 2026</td>
                                                <td class="px-3 py-4">Update content</td>
                                            </tr>

                                            <tr>
                                                <td class="px-3 py-4 font-semibold">Cloud Email Setup</td>
                                                <td class="px-3 py-4"><span class="client-badge client-badge-yellow">Pending</span></td>
                                                <td class="px-3 py-4">22 Apr 2026</td>
                                                <td class="px-3 py-4">Approval needed</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- recent activity -->
                            <div class="client-card p-6">
                                <div class="mb-5 flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Recent Activity</p>
                                        <h2 class="mt-2 text-2xl font-bold text-white">Latest updates</h2>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="client-activity">
                                        <div class="client-activity-dot"></div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">Your ticket has been updated by support</p>
                                            <p class="mt-1 text-xs text-blue-100/50">2 hours ago</p>
                                        </div>
                                    </div>

                                    <div class="client-activity">
                                        <div class="client-activity-dot"></div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">A proposal is ready for your review</p>
                                            <p class="mt-1 text-xs text-blue-100/50">Yesterday</p>
                                        </div>
                                    </div>

                                    <div class="client-activity">
                                        <div class="client-activity-dot"></div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">Profile information was updated successfully</p>
                                            <p class="mt-1 text-xs text-blue-100/50">2 days ago</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- right -->
                        <div class="space-y-6">
                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Quick Actions</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">Shortcuts</h2>

                                <div class="mt-6 space-y-3">
                                    <a href="{{ route('client.tickets.index') }}" wire:navigate class="client-shortcut">Open Ticket</a>
                                    <a href="{{ route('account.proposals') }}" wire:navigate class="client-shortcut">View Proposals</a>
                                    <a href="{{ route('account.profile') }}" wire:navigate class="client-shortcut">Update Profile</a>
                                    <a href="{{ route('account.services') }}" wire:navigate class="client-shortcut">My Services</a>
                                </div>
                            </div>

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Account Summary</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">Profile snapshot</h2>

                                <div class="mt-6 space-y-4 text-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Account Type</span>
                                        <span class="font-semibold text-white">{{ auth()->user()->type ?? 'personal' }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Role</span>
                                        <span class="font-semibold text-white">{{ auth()->user()->role->value ?? 'client' }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-blue-100/55">Status</span>
                                        <span class="font-semibold text-emerald-300">Active</span>
                                    </div>
                                </div>
                            </div>

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Support</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">Need help?</h2>

                                <p class="mt-4 text-sm leading-7 text-blue-100/68">
                                    Contact support for service issues, account help, or proposal clarification.
                                </p>

                                <div class="mt-6 space-y-3">
                                    <a href="tel:+8801000000000"
                                        class="inline-flex w-full items-center justify-center rounded-full border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                        Call Support
                                    </a>

                                    <a href="https://wa.me/8801000000000"
                                        class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-green-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                        WhatsApp Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
     </div>
</div>