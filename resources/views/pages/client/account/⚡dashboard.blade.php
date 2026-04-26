<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public function logout()
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirectRoute('home', navigate: true);
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/[0.06] shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">
                <!-- mobile overlay -->
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;"></div>

                <!-- sidebar -->
                <aside
                    :class="sidebarOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0 lg:translate-x-0 lg:opacity-100'"
                    class="fixed left-4 top-4 bottom-4 z-50 w-71.25 rounded-[28px] border border-white/10 bg-slate-950/35 p-5 backdrop-blur-2xl transition-all duration-300 lg:static lg:w-70 lg:translate-x-0 lg:rounded-none lg:border-0 lg:border-r ">

                    <div class="flex h-full flex-col">
                        <!-- brand -->
                        <div class="flex items-center justify-between">
                            <button @click="sidebarOpen = false"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- profile card -->
                        {{-- <div class="mt-8 rounded-[24px] border border-white/10 bg-white/[0.05] p-4 backdrop-blur-xl">
                            <div class="flex items-center gap-4">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full border border-white/10 bg-blue-500/15 text-lg font-bold text-cyan-200">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-white">
                                        {{ auth()->user()->name ?? 'Client User' }}
                                    </h3>
                                    <p class="truncate text-sm text-blue-100/55">
                                        {{ auth()->user()->email ?? 'client@email.com' }}
                                    </p>
                                </div>
                            </div>
                        </div> --}}

                        <!-- nav -->
                        <nav class="mt-8 flex-1 space-y-2">
                            <a href="{{ route('account.dashboard') }}" wire:navigate
                                class="client-dash-link client-dash-link-active">
                                <span class="client-dash-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 3.75h7.5v7.5h-7.5zm9 0h7.5v4.5h-7.5zm0 6h7.5v10.5h-7.5zm-9 9h7.5v-4.5h-7.5z" />
                                    </svg>
                                </span>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('account.profile') }}" wire:navigate class="client-dash-link">
                                <span class="client-dash-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 19.5a7.5 7.5 0 0115 0" />
                                    </svg>
                                </span>
                                <span>Profile</span>
                            </a>

                            <a href="{{ route('account.services') }}" wire:navigate class="client-dash-link">
                                <span class="client-dash-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                                    </svg>
                                </span>
                                <span>Services</span>
                            </a>

                            <a href="{{ route('account.tickets') }}" wire:navigate class="client-dash-link">
                                <span class="client-dash-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 6.75v10.5m-9-10.5v10.5M3.75 7.5h16.5M3.75 16.5h16.5" />
                                    </svg>
                                </span>
                                <span>Tickets</span>
                            </a>

                            <a href="{{ route('account.proposals') }}" wire:navigate class="client-dash-link">
                                <span class="client-dash-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h7.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 21l4.5-4.5-4.5-4.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 16.5h-9" />
                                    </svg>
                                </span>
                                <span>Proposal</span>
                            </a>
                        </nav>

                        <!-- logout -->
                        <div class="mt-6 border-t border-white/10 pt-4">
                            <button type="button"
                                wire:click="logout"
                                wire:loading.attr="disabled"
                                class="client-dash-link w-full text-left text-red-200 hover:bg-red-500/10 hover:text-white">
                                <span class="client-dash-icon bg-red-500/10 text-red-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 12H9m0 0 3-3m-3 3 3 3" />
                                    </svg>
                                </span>

                                <span wire:loading.remove wire:target="logout">Logout</span>
                                <span wire:loading wire:target="logout">Signing out...</span>
                            </button>
                        </div>
                    </div>
                </aside>

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
                                    <a href="{{ route('account.tickets') }}" wire:navigate class="client-shortcut">Open Ticket</a>
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