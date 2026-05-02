<?php

use Livewire\Component;

new class extends Component {
    //
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
    class="fixed left-0 top-0 h-screen w-64 border-r bg-slate-50 border-slate-200 flex flex-col z-50 transition-all duration-300 lg:translate-x-0">
    <!-- Logo -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="h-10 w-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined">dns</span>
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

        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-500">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Desktop Collapse Button -->
    <div class="hidden lg:flex px-3 py-3">
        <button @click="sidebarCollapsed = !sidebarCollapsed"
            class="w-full flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white py-2 text-slate-600 hover:bg-slate-100 transition cursor-pointer">
            <span class="material-symbols-outlined text-[20px]"
                x-text="sidebarCollapsed ? 'chevron_right' : 'chevron_left'"></span>

            <span x-show="!sidebarCollapsed" class="text-sm font-medium">
                Collapse
            </span>
        </button>
    </div>

    <!-- Nav -->
    <nav class="flex flex-col h-full overflow-y-auto px-2 pb-6 space-y-1">

        <a href="{{ route('admin.dashboard') }}" wire:navigate
            wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">dashboard</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Dashboard</span>
        </a>

        <a href="{{ route('admin.services.index') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">handyman</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Services</span>
        </a>
        <a href="{{ route('admin.pricing.index') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">payments</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Pricing</span>
        </a>

        <a href="#"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">confirmation_number</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Tickets</span>
        </a>

        <a href="#"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">account_tree</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Projects</span>
        </a>

        <a href="{{ route('admin.users.index') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">group</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Users</span>
        </a>
        <a href="{{ route('admin.departments.index') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-150">
            <span class="material-symbols-outlined shrink-0">business</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">Departments</span>
        </a>

        <div class="mt-auto px-2 py-6">
            <button
                class="w-full bg-primary-container text-white py-2.5 rounded-lg flex items-center justify-center gap-2 font-manrope text-sm font-semibold hover:opacity-90 active:opacity-80 transition-all">
                <span class="material-symbols-outlined shrink-0">logout</span>
                <span x-show="!sidebarCollapsed">Logout</span>
            </button>
        </div>
    </nav>
</aside>
