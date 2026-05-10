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

<!-- sidebar -->
<aside
    :class="sidebarOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0 lg:translate-x-0 lg:opacity-100'"
    class="fixed left-4 top-4 bottom-4 z-50 w-71.25 rounded-[28px] border border-white/10 bg-slate-950/35 p-5 backdrop-blur-2xl transition-all duration-300 lg:static lg:w-70 lg:translate-x-0 lg:rounded-none lg:border-0 lg:border-r ">

    <div class="flex h-full flex-col">
        <!-- brand -->
        <div class="flex items-center justify-between">
            <button @click="sidebarOpen = false"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
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
            <a href="{{ route('account.dashboard') }}" wire:navigate class="client-dash-link client-dash-link-active">
                <span class="client-dash-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 3.75h7.5v7.5h-7.5zm9 0h7.5v4.5h-7.5zm0 6h7.5v10.5h-7.5zm-9 9h7.5v-4.5h-7.5z" />
                    </svg>
                </span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('account.profile') }}" wire:navigate class="client-dash-link">
                <span class="client-dash-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 19.5a7.5 7.5 0 0115 0" />
                    </svg>
                </span>
                <span>Profile</span>
            </a>

            <a href="{{ route('account.services') }}" wire:navigate class="client-dash-link">
                <span class="client-dash-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                    </svg>
                </span>
                <span>Services</span>
            </a>

            <a href="{{ route('client.tickets.index') }}" wire:navigate class="client-dash-link">
                <span class="client-dash-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 6.75v10.5m-9-10.5v10.5M3.75 7.5h16.5M3.75 16.5h16.5" />
                    </svg>
                </span>
                <span>Tickets</span>
            </a>

            <a href="{{ route('account.proposals') }}" wire:navigate class="client-dash-link">
                <span class="client-dash-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h7.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 21l4.5-4.5-4.5-4.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 16.5h-9" />
                    </svg>
                </span>
                <span>Proposal</span>
            </a>
        </nav>

        <!-- logout -->
        <div class="mt-6 border-t border-white/10 pt-4">
            <button type="button" wire:click="logout" wire:loading.attr="disabled"
                class="client-dash-link w-full text-left text-red-200 hover:bg-red-500/10 hover:text-white">
                <span class="client-dash-icon bg-red-500/10 text-red-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m0 0 3-3m-3 3 3 3" />
                    </svg>
                </span>

                <span wire:loading.remove wire:target="logout">Logout</span>
                <span wire:loading wire:target="logout">Signing out...</span>
            </button>
        </div>
    </div>
</aside>
