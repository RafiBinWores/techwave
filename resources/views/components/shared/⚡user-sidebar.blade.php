<?php

use App\Models\SiteSetting;
use App\Models\ToolCategory;
use Livewire\Component;

new class extends Component {
    public function logout()
    {
        auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('home', navigate: true);
    }

    public function getIsToolsPremiumProperty(): bool
    {
        return ToolCategory::query()->where('slug', 'image-tools')->whereHas('toolSubscriptions', fn($q) => $q->where('user_id', auth()->id())->active())->exists();
    }

    public function getSiteSettingProperty()
    {
        return SiteSetting::current();
    }
};
?>

<!-- Sidebar -->
<aside
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': !sidebarCollapsed
    }"
    class="glass-panel fixed left-0 top-0 z-50 flex h-screen w-64 flex-col transition-all duration-300 lg:translate-x-0">
    <!-- Logo -->
    <div class="h-16 shrink-0 border-b border-white/10 px-4 flex items-center justify-between">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 overflow-hidden">
            <div class="h-12 w-12 rounded-xl text-white flex items-center justify-center shrink-0">

                @php
                    $logo = $this->siteSetting->logo
                        ? asset('storage/' . $this->siteSetting->logo)
                        : asset('assets/images/logo/logo.png');
                @endphp
                <img src="{{ $logo }}" alt="Logo" class="">
            </div>

            <div x-show="!sidebarCollapsed" class="min-w-0">
                <h1 class="text-lg font-extrabold tracking-tight text-cyan-300 font-manrope truncate">
                    Techwave
                </h1>

                <p class="text-blue-100/55 font-manrope text-xs font-medium truncate">
                    Client Portal
                </p>
            </div>
        </a>

        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-white/10 text-white/70">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Scrollable Nav -->
    <nav class="sidebar-scroll flex-1 overflow-y-auto overflow-x-hidden px-2 pb-4">

        <!-- Main -->
        <div class="space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xs font-semibold uppercase tracking-wider text-blue-100/45">
                Main
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-blue-100/45 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('account.dashboard') }}" wire:navigate
                wire:current.exact="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">dashboard</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Dashboard
                </span>
            </a>
        </div>

        <!-- Account -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-blue-100/45">
                Account
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-blue-100/45 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('account.profile') }}" wire:navigate
                wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">person</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Profile
                </span>
            </a>

            <a href="{{ route('account.services') }}" wire:navigate
                wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">design_services</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Services / Plans
                </span>
            </a>

            <a href="{{ route('client.tickets.index') }}" wire:navigate
                wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">confirmation_number</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tickets
                </span>
            </a>

            <a href="{{ route('client.proposals.index') }}" wire:navigate
                wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">receipt_long</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Proposals
                </span>
            </a>

            <a href="{{ route('account.change-password') }}" wire:navigate
                wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                class="client-dash-link">
                <span class="material-symbols-outlined shrink-0">lock</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Change Password
                </span>
            </a>

            {{-- AI Tools --}}
            {{-- <div x-data="{ open: false }" class="space-y-1">
                <button type="button" @click="open = !open" class="client-dash-link w-full">
                    <span class="material-symbols-outlined shrink-0">auto_awesome</span>
                    <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                        AI Tools
                    </span>
                    <span x-show="!sidebarCollapsed"
                        class="material-symbols-outlined ml-auto text-lg transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">
                        expand_more
                    </span>
                </button>

                <div x-show="open && !sidebarCollapsed" x-collapse class="ml-4 space-y-1 border-l border-white/10 pl-3">
                    <a href="{{ route('client.tools.ai-text') }}" wire:navigate
                        wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                        class="client-dash-link">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">text_fields</span>
                        <span class="font-manrope text-sm font-medium">Text Generator</span>
                    </a>

                    <a href="{{ route('client.tools.ai-image') }}" wire:navigate
                        wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                        class="client-dash-link">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">image</span>
                        <span class="font-manrope text-sm font-medium">Image Generator</span>
                    </a>

                    <a href="{{ route('client.tools.ai-video') }}" wire:navigate
                        wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                        class="client-dash-link">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">videocam</span>
                        <span class="font-manrope text-sm font-medium">Video Generator</span>
                    </a>

                    <a href="{{ route('client.tools.ai-audio') }}" wire:navigate
                        wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                        class="client-dash-link">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">audiotrack</span>
                        <span class="font-manrope text-sm font-medium">Audio Generator</span>
                    </a>

                    <div class="my-2 border-t border-white/10"></div>

                    <a href="{{ route('account.ai-generations') }}" wire:navigate
                        wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                        class="client-dash-link">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">history</span>
                        <span class="font-manrope text-sm font-medium">Generation History</span>
                    </a>
                </div>
            </div> --}}

            @if ($this->is_tools_premium)
                <div x-data="{ open: false }" class="space-y-1">
                    <button type="button" @click="open = !open" class="client-dash-link w-full">
                        <span class="material-symbols-outlined shrink-0">backup</span>
                        <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                            Tools Backup
                        </span>
                        <span x-show="!sidebarCollapsed"
                            class="material-symbols-outlined ml-auto text-lg transition-transform duration-200"
                            :class="open ? 'rotate-180' : ''">
                            expand_more
                        </span>
                    </button>

                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-4 space-y-1 border-l border-white/10 pl-3">
                        <a href="{{ route('account.compressed-images') }}" wire:navigate
                            wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                            class="client-dash-link">
                            <span class="material-symbols-outlined shrink-0 text-[20px]">compress</span>
                            <span class="font-manrope text-sm font-medium">Compressed Images</span>
                        </a>

                        <a href="{{ route('account.bg-removed-images') }}" wire:navigate
                            wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                            class="client-dash-link">
                            <span class="material-symbols-outlined shrink-0 text-[20px]">magic_exchange</span>
                            <span class="font-manrope text-sm font-medium">BG Removed Images</span>
                        </a>

                        <a href="{{ route('account.resized-images') }}" wire:navigate
                            wire:current="bg-white/10 text-cyan-300 border-l-4 border-cyan-400 font-semibold shadow-sm"
                            class="client-dash-link">
                            <span class="material-symbols-outlined shrink-0 text-[20px]">photo_size_select_large</span>
                            <span class="font-manrope text-sm font-medium">Resized Images</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </nav>

    <!-- Fixed Bottom Logout -->
    <div class="shrink-0 border-t border-white/10 bg-slate-950/40 px-3 py-4">
        <button type="button" wire:click="logout"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-500/20 py-2.5 font-manrope text-sm font-semibold text-red-200 transition hover:bg-red-500/30 hover:text-white active:opacity-80">
            <span class="material-symbols-outlined shrink-0">logout</span>
            <span x-show="!sidebarCollapsed">Logout</span>
        </button>
    </div>
</aside>
