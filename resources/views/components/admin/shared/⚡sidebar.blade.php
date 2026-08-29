<?php

use App\Models\AdminChatMessage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public int $unreadChatCount = 0;

    public function mount(): void
    {
        $this->unreadChatCount = $this->countUnreadChats();
    }

    public function getListeners(): array
    {
        $authId = Auth::id();

        if (!$authId) {
            return [];
        }

        return [
            "echo-private:user.{$authId}.chat,.chat.message" => 'refreshUnreadChats',
            'admin-chat-unread-changed' => 'refreshUnreadChats',
        ];
    }

    public function refreshUnreadChats(): void
    {
        $this->unreadChatCount = $this->countUnreadChats();
    }

    private function countUnreadChats(): int
    {
        $authId = Auth::id();

        if (!$authId) {
            return 0;
        }

        return AdminChatMessage::query()->where('receiver_id', $authId)->whereNull('read_at')->count();
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
    class="fixed left-0 top-0 z-50 flex h-screen w-64 flex-col border-r border-slate-200 bg-slate-50 transition-all duration-300 lg:translate-x-0">
    <!-- Logo -->
    <div class="h-16 shrink-0 border-b border-slate-200 px-4 flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 overflow-hidden">
            <div class="h-12 w-12 rounded-xl text-white flex items-center justify-center shrink-0">

                @php
                    $logo = $this->siteSetting->logo
                        ? asset('storage/' . $this->siteSetting->logo)
                        : asset('assets/images/logo/logo.png');
                @endphp
                <img src="{{ $logo }}" alt="Logo" class="">
            </div>

            <div x-show="!sidebarCollapsed" class="min-w-0">
                <h1 class="text-lg font-extrabold tracking-tight text-blue-700 font-manrope truncate">
                    Techwave
                </h1>

                <p class="text-slate-500 font-manrope text-xs font-medium truncate">
                    Infrastructure Management
                </p>
            </div>
        </a>

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
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.dashboard') }}" wire:navigate
                wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">dashboard</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Dashboard
                </span>
            </a>

            {{-- Visitors Dropdown --}}
            <div x-data="{ open: {{ request()->routeIs('admin.visitors.*') ? 'true' : 'false' }} }" class="space-y-1">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900 cursor-pointer">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined shrink-0">visibility</span>

                        <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                            Visitors
                        </span>
                    </div>

                    <span x-show="!sidebarCollapsed"
                        class="material-symbols-outlined text-lg transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">
                        expand_more
                    </span>
                </button>

                <div x-show="open && !sidebarCollapsed" x-collapse
                    class="ml-4 space-y-1 border-l border-slate-200 pl-3">
                    <a href="{{ route('admin.visitors.index') }}" wire:navigate
                        wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">analytics</span>
                        <span class="font-manrope text-sm font-medium">Page Analytics</span>
                    </a>

                    <a href="{{ route('admin.visitors.logs') }}" wire:navigate
                        wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">list_alt</span>
                        <span class="font-manrope text-sm font-medium">Visitor List</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Management -->
        <div class="space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Order Management
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.orders.index') }}" wire:navigate
                wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">shopping_cart</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Orders
                </span>
            </a>

            <a href="{{ route('admin.bookings.index') }}" wire:navigate
                wire:current.exact="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">developer_board</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Bookings
                </span>
            </a>

            <a href="{{ route('admin.tool-subscriptions.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">subscriptions</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Subscriptions
                </span>
            </a>
        </div>

        <!-- Services & Plans -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Services & Plans
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.categories.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">category</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Categories
                </span>
            </a>

            <a href="{{ route('admin.subcategories.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">account_tree</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Sub-categories
                </span>
            </a>

            <a href="{{ route('admin.services.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">handyman</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Services
                </span>
            </a>

            <a href="{{ route('admin.service-options.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">tune</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Service Options
                </span>
            </a>

            <a href="{{ route('admin.service-plans.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">inventory_2</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Service Plans
                </span>
            </a>

            <a href="{{ route('admin.plan-addons.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">extension</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Plan Addons
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
        </div>

        <!-- Tools -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Tools
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.tool-categories.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">build</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tool Categories
                </span>
            </a>


            <a href="{{ route('admin.tools.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">handyman</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tools
                </span>
            </a>

            <a href="{{ route('admin.tool-plans.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">workspace_premium</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tool Plans
                </span>
            </a>

            <a href="{{ route('admin.invoice-themes.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">receipt_long</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Invoice Themes
                </span>
            </a>

            {{-- Image Tools Dropdown --}}
            <div x-data="{ open: false }" class="space-y-1">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined shrink-0">image</span>
                        <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                            Image
                        </span>
                    </div>
                    <span x-show="!sidebarCollapsed"
                        class="material-symbols-outlined text-lg transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">
                        expand_more
                    </span>
                </button>

                <div x-show="open && !sidebarCollapsed" x-collapse
                    class="ml-4 space-y-1 border-l border-slate-200 pl-3">
                    <a href="{{ route('client.tools.image-compressor') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">compress</span>
                        <span class="font-manrope text-sm font-medium">Image Compressor</span>
                    </a>

                    <a href="{{ route('admin.compressed-images.index') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">photo_library</span>
                        <span class="font-manrope text-sm font-medium">Compressed Images</span>
                    </a>

                    <a href="{{ route('client.tools.image-resizer') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">photo_size_select_large</span>
                        <span class="font-manrope text-sm font-medium">Image Resizer</span>
                    </a>
                    <a href="{{ route('client.tools.bg-remover') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">magic_exchange</span>
                        <span class="font-manrope text-sm font-medium">BG Remover</span>
                    </a>

                    {{-- <a href="{{ route('admin.bg-removed-images.index') }}" wire:navigate
                    wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                    <span class="material-symbols-outlined shrink-0 text-[20px]">photo_library</span>
                    <span class="font-manrope text-sm font-medium">BG Removed Images</span>
                    </a>

                    <a href="{{ route('admin.resized-images.index') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">photo_library</span>
                        <span class="font-manrope text-sm font-medium">Resized Images</span>
                    </a> --}}
                </div>
            </div>

            {{-- PDF Tools --}}
            {{-- <a href="{{ route('admin.tools.pdf-tools') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
            <span class="material-symbols-outlined shrink-0">picture_as_pdf</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                PDF Tools
            </span>
            </a> --}}
        </div>

        <!-- Portfolio & Blog -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Portfolio & Blog
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
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
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.proposals.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">receipt_long</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Proposals
                </span>
            </a>

            <a href="{{ route('admin.contact-messages.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">mail</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Contact Messages
                </span>
            </a>

            <a href="{{ route('admin.tickets.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">confirmation_number</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Tickets
                </span>
            </a>

            <a href="{{ route('admin.chats.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="relative flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">forum</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Team Chat
                </span>
                @if ($this->unreadChatCount > 0)
                    <span x-show="!sidebarCollapsed"
                        class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 font-manrope text-[11px] font-semibold text-white">
                        {{ $this->unreadChatCount > 99 ? '99+' : $this->unreadChatCount }}
                    </span>
                    <span x-show="sidebarCollapsed"
                        class="absolute right-2.5 top-2 h-2.5 w-2.5 rounded-full border-2 border-white bg-red-500">
                    </span>
                @endif
            </a>
        </div>

        <!-- System Management -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                System Management
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.users.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">group</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Users
                </span>
            </a>

            <a href="{{ route('admin.whmcs-accounts.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">account_balance</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    WHMCS Accounts
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

        <!-- Site Management -->
        <div class="mt-4 space-y-1">
            <p x-show="!sidebarCollapsed"
                class="px-4 pb-2 pt-2 font-manrope text-xs font-semibold uppercase tracking-wider text-slate-500">
                Site Management
            </p>
            <p x-show="sidebarCollapsed"
                class="px-4 pb-2 pt-4 font-manrope text-xl font-semibold uppercase tracking-wider text-slate-500 text-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                        fill="currentColor" />
                </svg>
            </p>

            <a href="{{ route('admin.company-logos.index') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">handshake</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Our Clients
                </span>
            </a>

            <a href="{{ route('admin.settings.site-setting') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">settings</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Site Settings
                </span>
            </a>

            {{-- Pages Dropdown --}}
            <div x-data="{ open: {{ request()->routeIs('admin.pages.*') ? 'true' : 'false' }} }" class="space-y-1">
                {{-- Dropdown Button --}}
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900 cursor-pointer">

                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined shrink-0">web</span>

                        <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                            Pages
                        </span>
                    </div>

                    <span x-show="!sidebarCollapsed"
                        class="material-symbols-outlined text-lg transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">
                        expand_more
                    </span>
                </button>

                {{-- Dropdown Items --}}
                <div x-show="open && !sidebarCollapsed" x-collapse
                    class="ml-4 space-y-1 border-l border-slate-200 pl-3">

                    <a href="{{ route('admin.pages.home-settings') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                        <span class="material-symbols-outlined shrink-0 text-[20px]">
                            home
                        </span>

                        <span class="font-manrope text-sm font-medium">
                            Home
                        </span>
                    </a>

                    <a href="{{ route('admin.pages.about-settings') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                        <span class="material-symbols-outlined shrink-0 text-[20px]">
                            info
                        </span>

                        <span class="font-manrope text-sm font-medium">
                            About
                        </span>
                    </a>

                    <a href="{{ route('admin.pages.services-settings') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                        <span class="material-symbols-outlined shrink-0 text-[20px]">
                            handyman
                        </span>

                        <span class="font-manrope text-sm font-medium">
                            Services
                        </span>
                    </a>
                </div>
            </div>

            {{-- Mail Templates Dropdown --}}
            <div x-data="{ open: {{ request()->routeIs('admin.settings.invoice-templates') ? 'true' : 'false' }} }" class="space-y-1">
                {{-- Dropdown Button --}}
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined shrink-0">mail</span>

                        <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                            Mail Templates
                        </span>
                    </div>

                    <span x-show="!sidebarCollapsed"
                        class="material-symbols-outlined text-lg transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">
                        expand_more
                    </span>
                </button>

                {{-- Dropdown Items --}}
                <div x-show="open && !sidebarCollapsed" x-collapse
                    class="ml-4 space-y-1 border-l border-slate-200 pl-3">

                    <a href="{{ route('admin.settings.proposal-templates') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                        <span class="material-symbols-outlined shrink-0 text-[20px]">
                            receipt_long
                        </span>

                        <span class="font-manrope text-sm font-medium">
                            Proposal Template
                        </span>
                    </a>

                    {{-- Future link example --}}

                    <a href="{{ route('admin.settings.invoice-templates') }}" wire:navigate
                        wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">

                        <span class="material-symbols-outlined shrink-0 text-[20px]">
                            receipt
                        </span>

                        <span class="font-manrope text-sm font-medium">
                            Invoice Template
                        </span>
                    </a>

                </div>
            </div>

            {{-- <a href="{{ route('admin.live-tv-channels.index') }}" wire:navigate
            wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
            <span class="material-symbols-outlined shrink-0">live_tv</span>
            <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                Live TV
            </span>
            </a> --}}

            {{-- icons --}}
            <a href="{{ route('admin.icons.material-icons') }}" wire:navigate
                wire:current="bg-white text-blue-700 border-l-4 border-blue-700 font-semibold shadow-sm"
                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-slate-600 transition-all duration-150 hover:bg-slate-100 hover:text-slate-900">
                <span class="material-symbols-outlined shrink-0">insert_emoticon</span>
                <span x-show="!sidebarCollapsed" class="font-manrope text-sm font-medium">
                    Icons
                </span>
            </a>
        </div>
    </nav>
</aside>
