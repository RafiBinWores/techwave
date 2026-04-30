<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<header
    class="flex items-center justify-between gap-3 h-16 px-4 sm:px-6 sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 font-manrope text-sm">

    <!-- Left -->
    <div class="flex items-center gap-3 flex-1 min-w-0">
        <button type="button" @click="sidebarOpen = true"
            class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="relative hidden sm:block w-full max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                search
            </span>

            <input
                class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-body-md focus:ring-2 focus:ring-primary-container/10 focus:border-primary-container transition-all"
                placeholder="Search resources..." type="text" />
        </div>

        <button type="button" class="sm:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
            <span class="material-symbols-outlined">search</span>
        </button>
    </div>

    <!-- Right -->
    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

        <!-- Notification -->
        <div x-data="{ notificationOpen: false }" class="relative">
            <button
                type="button"
                @click.stop="notificationOpen = !notificationOpen"
                class="relative p-2 text-slate-500 hover:bg-slate-100 transition-colors rounded-full cursor-pointer"
            >
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>

            <div
                x-cloak
                x-show="notificationOpen"
                @click.outside="notificationOpen = false"
                x-transition.origin.top.right
                class="absolute right-0 top-full mt-3 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl z-[9999] overflow-hidden"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Notifications</h3>
                        <p class="text-xs text-slate-500">You have 3 new updates</p>
                    </div>

                    <button
                        type="button"
                        @click="notificationOpen = false"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                    >
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <a href="#" class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition">
                        <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">confirmation_number</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">New support ticket created</p>
                            <p class="text-xs text-slate-500 truncate">Server migration issue reported.</p>
                            <p class="text-[11px] text-slate-400 mt-1">2 minutes ago</p>
                        </div>
                    </a>

                    <a href="#" class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition">
                        <div class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Project task completed</p>
                            <p class="text-xs text-slate-500 truncate">Deployment checklist marked as done.</p>
                            <p class="text-[11px] text-slate-400 mt-1">15 minutes ago</p>
                        </div>
                    </a>

                    <a href="#" class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition">
                        <div class="h-9 w-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">warning</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">High priority alert</p>
                            <p class="text-xs text-slate-500 truncate">One critical ticket needs admin review.</p>
                            <p class="text-[11px] text-slate-400 mt-1">1 hour ago</p>
                        </div>
                    </a>
                </div>

                <div class="p-3 bg-slate-50 border-t border-slate-100">
                    <a
                        href="#"
                        class="flex items-center justify-center gap-2 rounded-xl bg-primary text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition"
                    >
                        View all notifications
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <button type="button"
            class="hidden sm:flex p-2 text-slate-500 hover:bg-slate-100 transition-colors rounded-full">
            <span class="material-symbols-outlined">settings</span>
        </button>

        <div class="hidden sm:block h-8 w-px bg-slate-200 mx-1"></div>

        <!-- User -->
        <div class="flex items-center gap-2 cursor-pointer">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-primary to-sky-600 text-sm font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>

            <div class="hidden md:block text-left">
                <p class="text-slate-900 font-semibold text-sm leading-tight capitalize">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-slate-500 text-xs capitalize">
                    {{ auth()->user()->role }}
                </p>
            </div>
        </div>
    </div>
</header>
