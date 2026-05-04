<?php

use Livewire\Component;

new class extends Component {
    public function logout(): void
    {
        auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('login', navigate: true);
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
    class="fixed left-0 top-0 z-50 flex h-screen w-64 flex-col border-r border-slate-200 bg-slate-50 transition-all duration-300 lg:translate-x-0">
    <!-- Logo -->
    <div class="h-16 shrink-0 border-b border-slate-200 px-4 flex items-center justify-between">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="h-12 w-12 rounded-xl bg-primary text-white flex items-center justify-center shrink-0">
                <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo" class="p-1">
            </div>

            <div x-show="!sidebarCollapsed" class="min-w-0">
                <h1 class="text-lg font-extrabold tracking-tight text-blue-700 font-manrope truncate">
                    Techwave
                </h1>

                <p class="text-slate-500 font-manrope text-xs font-medium truncate">
                    Infrastructure Management
                </p>
            </div>
        </div>

        {{-- <div class="h-16 shrink-0 border-b border-slate-200 px-4 flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden">
                <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo"
                    class="p-1 w-full object-contain lg:h-14">
            </div>

            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-500">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div> --}}

        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-500">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>



    <!-- Scrollable Nav -->
    <nav class="sidebar-scroll flex-1 overflow-y-auto overflow-x-hidden px-2 pb-4">

        <!-- Main -->
        <div class="space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Main
            </p>

            <a href="{{ route('admin.dashboard') }}" wire:navigate
                wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">dashboard</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Dashboard
                </span>
            </a>
        </div>

        <!-- Website Content -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Website Content
            </p>

            <a href="{{ route('admin.services.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">handyman</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Services
                </span>
            </a>

            <a href="{{ route('admin.pricing.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">payments</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Pricing
                </span>
            </a>

            <a href="{{ route('admin.categories.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">category</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Categories
                </span>
            </a>

            <a href="{{ route('admin.company-logos.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">handshake</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Company Logos
                </span>
            </a>
        </div>

        <!-- Portfolio & Blog -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Portfolio & Blog
            </p>

            <a href="{{ route('admin.projects.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">account_tree</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Projects
                </span>
            </a>

            <a href="{{ route('admin.blogs.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">article</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Blogs
                </span>
            </a>
        </div>

        <!-- Support -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Support
            </p>

            <a href="#"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">confirmation_number</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tickets
                </span>
            </a>
        </div>

        <!-- System Management -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                System Management
            </p>

            <a href="{{ route('admin.users.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">group</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Users
                </span>
            </a>

            <a href="{{ route('admin.departments.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">business</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Departments
                </span>
            </a>
        </div>
    </nav>

    <!-- Fixed Bottom Logout -->
    <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-3 py-4">
        <button type="button" wire:click="logout"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container py-2.5 font-manrope text-sm font-semibold text-white transition-all hover:opacity-90 active:opacity-80">
            <span class="material-symbols-outlined shrink-0">logout</span>
            <span x-show="!sidebarCollapsed">Logout</span>
        </button>
    </div>
</aside>
