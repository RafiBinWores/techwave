<?php

use App\Models\Category;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\SupportTicket;
use App\Models\ToolCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public int $notificationRefreshKey = 0;
    public bool $liveTvEnabled = false;

    public int $unreadCount = 0;

    public array $notifications = [];

    public array $serviceCategories = [];

    public array $featuredServices = [];

    public array $toolCategories = [];

    public function mount(): void
    {
        $this->loadClientNotifications();
        $this->liveTvEnabled = SiteSetting::current()->live_tv_enabled ?? false;
        $this->loadServicesForMegaMenu();
        $this->loadToolsForMegaMenu();
    }

    private function loadToolsForMegaMenu(): void
    {
        $categories = ToolCategory::query()
            ->where('is_active', true)
            ->with(['tools' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $this->toolCategories = $categories
            ->filter(fn($cat) => $cat->tools->isNotEmpty())
            ->values()
            ->map(fn($cat) => [
                'name' => $cat->name,
                'icon' => $cat->icon ?? 'build',
                'tools' => $cat->tools->map(fn($tool) => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'icon' => $tool->icon ?? 'construction',
                    'route' => $tool->route,
                ])->toArray(),
            ])
            ->toArray();
    }

    private function loadServicesForMegaMenu(): void
    {
        $this->featuredServices = Service::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(2)
            ->get()
            ->map(fn($s) => [
                'name' => $s->card_title,
                'description' => $s->short_description,
                'slug' => $s->slug,
                'icon' => $s->icon ?? 'design_services',
                'image' => $s->image,
            ])
            ->toArray();

        $services = Service::query()
            ->where('is_active', true)
            ->with(['serviceOptions' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('card_title')
            ->get(['id', 'card_title', 'short_description', 'slug', 'icon', 'category_id']);

        $this->serviceCategories = $services->groupBy('category_id')
            ->map(function ($items, $categoryId) {
                $category = Category::find($categoryId);
                return [
                    'name' => $category?->name ?? 'Other',
                    'icon' => $category?->icon ?? 'category',
                    'services' => $items->map(fn($s) => [
                        'name' => $s->card_title,
                        'description' => $s->short_description,
                        'slug' => $s->slug,
                        'icon' => $s->icon ?? 'design_services',
                        'options' => $s->serviceOptions->map(fn($o) => [
                            'name' => $o->card_title,
                            'slug' => $o->slug,
                        ])->take(4)->values()->toArray(),
                    ])->take(5)->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getListeners(): array
    {
        if (!Auth::check()) {
            return [];
        }

        return [
            'echo-private:user.' . Auth::id() . '.tickets,.ticket.updated' => 'refreshClientNotifications',
        ];
    }

    public function refreshClientNotifications(): void
    {
        $this->loadClientNotifications();
        $this->notificationRefreshKey++;
    }

    private function loadClientNotifications(): void
    {
        if (!Auth::check()) {
            $this->unreadCount = 0;
            $this->notifications = [];

            return;
        }

        $userId = Auth::id();

        $this->unreadCount = SupportTicket::query()->where('user_id', $userId)->whereNull('client_read_at')->count();

        $this->notifications = SupportTicket::query()
            ->where('user_id', $userId)
            ->whereNull('client_read_at')
            ->latest('last_reply_at')
            ->latest()
            ->limit(5)
            ->get(['id', 'subject', 'priority', 'status', 'last_reply_at', 'updated_at'])
            ->map(
                fn($ticket) => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'time' => $ticket->last_reply_at?->diffForHumans() ?? $ticket->updated_at?->diffForHumans(),
                ],
            )
            ->toArray();
    }

    public function markAllClientNotificationsRead(): void
    {
        if (!Auth::check()) {
            return;
        }

        SupportTicket::query()
            ->where('user_id', Auth::id())
            ->whereNull('client_read_at')
            ->update([
                'client_read_at' => now(),
            ]);

        $this->loadClientNotifications();
        $this->notificationRefreshKey++;

        $this->dispatch('toast', message: 'All notifications marked as read.', type: 'success');
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('home'), navigate: true);
    }
};
?>

<div class="relative" x-data="{ mobileMenu: false, userMenu: false, notificationOpen: false, servicesMega: false, toolsMega: false, mobileServicesOpen: false, mobileToolsOpen: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })">
    <nav :class="scrolled ? 'bg-white/10 backdrop-blur-md border border-white/12' : 'border border-white/8 bg-white/5 backdrop-blur-sm'"
        class="rounded-2xl px-4 py-4 sm:px-6 transition-all duration-300">

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                @if ($siteLogo)
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" width="140" height="40"
                    class="h-10 rounded-xl object-contain">
                @else
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-r from-blue-500 to-sky-400 text-sm font-bold text-white shadow-lg shadow-blue-500/25">
                    {{ strtoupper(substr($siteName, 0, 1)) }}
                </div>

                <span class="text-sm font-bold text-white">
                    {{ $siteName }}
                </span>
                @endif
            </a>

            <div class="hidden items-center gap-8 text-sm font-medium text-blue-50/85 lg:flex">
                <a href="{{ route('home') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                    <span class="relative z-10">Home</span>
                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                {{-- Services Mega Menu --}}
                <div @mouseenter="servicesMega = true; toolsMega = false"
                    @mouseleave="servicesMega = false">
                    <button type="button" @click="servicesMega = !servicesMega"
                        class="group cursor-pointer relative flex items-center gap-1 px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                        <span class="relative z-10">Services</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': servicesMega }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100"></span>
                    </button>

                    {{-- Services Mega Panel --}}
                    <div x-show="servicesMega"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                        @click.outside="servicesMega = false"
                        style="display: none;"
                        class="absolute left-1/2 top-full z-[9999] mt-2 w-[820px] -translate-x-1/2 overflow-hidden rounded-2xl border border-white/16 bg-slate-950/98 backdrop-blur-2xl shadow-2xl shadow-blue-950/40">

                        <div class="grid grid-cols-12 gap-0">
                            {{-- Left: Featured --}}
                            <div class="col-span-5 border-r border-white/8 p-6">
                                <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-cyan-400/80">Featured</p>

                                <div class="flex flex-col gap-3">
                                    @forelse ($featuredServices as $featured)
                                    <a href="{{ route('client.services.details', $featured['slug']) }}" wire:navigate
                                        @click="servicesMega = false"
                                        class="group flex items-start gap-3.5 rounded-xl border border-white/5 bg-white/5 p-3.5 transition duration-200 hover:border-cyan-400/20 hover:bg-white/8">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500/20 to-blue-500/20 text-cyan-300 border border-cyan-500/10">
                                            <span class="material-symbols-outlined text-[22px]">{{ $featured['icon'] }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-white group-hover:text-cyan-200">{{ $featured['name'] }}</p>
                                            <p class="mt-0.5 line-clamp-2 text-xs text-blue-100/45">{{ $featured['description'] }}</p>
                                        </div>
                                    </a>
                                    @empty
                                    <a href="{{ route('client.services') }}" wire:navigate
                                        @click="servicesMega = false"
                                        class="group flex items-start gap-3.5 rounded-xl border border-white/5 bg-white/5 p-3.5 transition duration-200 hover:border-cyan-400/20 hover:bg-white/8">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500/20 to-blue-500/20 text-cyan-300 border border-cyan-500/10">
                                            <span class="material-symbols-outlined text-[22px]">design_services</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-white group-hover:text-cyan-200">Our Services</p>
                                            <p class="mt-0.5 text-xs text-blue-100/45">Explore all available services</p>
                                        </div>
                                    </a>
                                    @endforelse
                                </div>

                                <a href="{{ route('client.services') }}" wire:navigate
                                    @click="servicesMega = false"
                                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 py-2.5 text-xs font-semibold text-white transition hover:bg-white/10">
                                    View All Services
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>

                            {{-- Right: Categories --}}
                            <div class="col-span-7 p-6">
                                <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-blue-100/40">Categories</p>

                                <div class="grid grid-cols-2 gap-x-6 gap-y-5">
                                    @forelse ($serviceCategories as $category)
                                    <div>
                                        <div class="mb-2.5 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-cyan-400">{{ $category['icon'] }}</span>
                                            <p class="text-xs font-bold text-white">{{ $category['name'] }}</p>
                                        </div>

                                        <div class="flex flex-col gap-0.5">
                                            @foreach ($category['services'] as $service)
                                            <div>
                                                <a href="{{ route('client.services.details', $service['slug']) }}" wire:navigate
                                                    @click="servicesMega = false"
                                                    class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] text-blue-100/75 transition hover:bg-white/8 hover:text-white">
                                                    <span class="h-1 w-1 shrink-0 rounded-full bg-white/20 group-hover:bg-cyan-400"></span>
                                                    {{ $service['name'] }}
                                                </a>
                                                @if (!empty($service['options']))
                                                <div class="ml-5 flex flex-col gap-0.5">
                                                    @foreach ($service['options'] as $option)
                                                    <a href="{{ route('client.services.options', $service['slug']) }}" wire:navigate
                                                        @click="servicesMega = false"
                                                        class="group flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-[11px] text-blue-100/55 transition hover:bg-white/5 hover:text-blue-100/80">
                                                        <span class="h-0.5 w-0.5 shrink-0 rounded-full bg-white/15 group-hover:bg-cyan-400/60"></span>
                                                        {{ $option['name'] }}
                                                    </a>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-span-2 text-center text-sm text-blue-100/40">
                                        No services available yet.
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tools Mega Menu --}}
                <div @mouseenter="toolsMega = true; servicesMega = false"
                    @mouseleave="toolsMega = false">
                    <button type="button" @click="toolsMega = !toolsMega"
                        class="group cursor-pointer relative flex items-center gap-1 px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                        <span class="relative z-10">Tools</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': toolsMega }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100"></span>
                    </button>

                    {{-- Tools Mega Panel --}}
                    <div x-show="toolsMega"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                        @click.outside="toolsMega = false"
                        style="display: none;"
                        class="absolute left-1/2 top-full z-[9999] mt-2 w-[780px] -translate-x-1/2 overflow-hidden rounded-2xl border border-white/16 bg-slate-950/98 backdrop-blur-2xl shadow-2xl shadow-blue-950/40">

                        <div class="p-6">
                            <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-blue-100/40">All Tools</p>

                            <div class="grid grid-cols-3 gap-x-6 gap-y-5">
                                @forelse ($toolCategories as $category)
                                <div>
                                    <div class="mb-2.5 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-cyan-400">{{ $category['icon'] }}</span>
                                        <p class="text-xs font-bold text-white">{{ $category['name'] }}</p>
                                    </div>

                                    <div class="flex flex-col gap-0.5">
                                        @foreach ($category['tools'] as $tool)
                                        <a href="{{ route($tool['route']) }}" wire:navigate
                                            @click="toolsMega = false"
                                            class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] text-blue-100/65 transition hover:bg-white/8 hover:text-white">
                                            <span class="h-1 w-1 shrink-0 rounded-full bg-white/20 group-hover:bg-cyan-400"></span>
                                            {{ $tool['name'] }}
                                        </a>
                                        @endforeach
                                    </div>
                                </div>
                                @empty
                                <div class="col-span-3 text-center text-sm text-blue-100/40">
                                    No tools available yet.
                                </div>
                                @endforelse
                            </div>

                            <a href="{{ route('client.tools.index') }}" wire:navigate
                                @click="toolsMega = false"
                                class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 py-2.5 text-xs font-semibold text-white transition hover:bg-white/10">
                                View All Tools
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>

                @if ($liveTvEnabled)
                <a href="{{ route('client.live-tv') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                    <span class="relative z-10">
                        <span class="flex items-center gap-1.5">
                            <span class="flex h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            Live TV
                        </span>
                    </span>
                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-red-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>
                @endif

                <a href="{{ route('client.blogs') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                    <span class="relative z-10">Blogs</span>
                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.about') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                    <span class="relative z-10">About</span>
                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.contact') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">
                    <span class="relative z-10">Contact</span>
                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>
            </div>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                {{-- Client Notification --}}
                <div class="relative" wire:key="client-notifications-desktop-{{ $notificationRefreshKey }}">
                    <button type="button" @click.stop="notificationOpen = !notificationOpen; userMenu = false"
                        class="relative flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/8 text-white shadow-lg shadow-blue-950/20 backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/14">

                        <span class="material-symbols-outlined text-[22px]">notifications</span>

                        @if ($unreadCount > 0)
                        <span
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-black text-white">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                        @endif
                    </button>

                    <div x-cloak x-show="notificationOpen" @click.outside="notificationOpen = false"
                        x-transition.origin.top.right style="display: none;"
                        class="absolute right-0 top-full z-999 mt-3 w-88 max-w-[calc(100vw-2rem)] overflow-hidden rounded-3xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-blue-950/30 backdrop-blur-2xl">

                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <div>
                                <h3 class="text-sm font-bold text-white">Notifications</h3>
                                <p class="text-xs text-blue-100/45">
                                    {{ $unreadCount }} new ticket {{ Str::plural('update', $unreadCount) }}
                                </p>
                            </div>

                            <button type="button" @click="notificationOpen = false"
                                class="rounded-xl p-1.5 text-blue-100/45 transition hover:bg-white/10 hover:text-white">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>

                        <div class="notification-scroll max-h-88 divide-y divide-white/10 overflow-y-auto">
                            @forelse ($notifications as $ticket)
                            <a href="{{ route('client.tickets.show', $ticket['id']) }}" wire:navigate
                                @click="notificationOpen = false"
                                class="group flex gap-3 px-4 py-3 transition hover:bg-white/8">

                                <div @class([ 'flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10' , 'bg-blue-400/15 text-blue-200'=> $ticket['priority'] === 'medium',
                                    'bg-slate-400/15 text-slate-200' => $ticket['priority'] === 'low',
                                    'bg-orange-400/15 text-orange-200' => $ticket['priority'] === 'high',
                                    'bg-red-400/15 text-red-200' => $ticket['priority'] === 'urgent',
                                    ])>
                                    <span class="material-symbols-outlined text-[20px]">support_agent</span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="truncate text-sm font-semibold text-white">
                                            Ticket updated
                                        </p>

                                        <span @class([ 'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase' , 'bg-blue-400/15 text-blue-200'=> $ticket['status'] === 'open',
                                            'bg-amber-400/15 text-amber-200' => $ticket['status'] === 'pending',
                                            'bg-emerald-400/15 text-emerald-200' => $ticket['status'] === 'answered',
                                            'bg-slate-400/15 text-slate-200' => $ticket['status'] === 'closed',
                                            ])>
                                            {{ $ticket['status'] }}
                                        </span>
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-blue-100/55">
                                        {{ $ticket['subject'] }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-blue-100/35">
                                        {{ $ticket['time'] }}
                                    </p>
                                </div>
                            </a>
                            @empty
                            <div class="px-4 py-10 text-center">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/8 text-blue-100/45">
                                    <span class="material-symbols-outlined">notifications_off</span>
                                </div>

                                <h4 class="mt-3 text-sm font-semibold text-white">
                                    No new notifications
                                </h4>

                                <p class="mt-1 text-xs text-blue-100/45">
                                    Ticket replies and status changes will appear here.
                                </p>
                            </div>
                            @endforelse
                        </div>

                        <div class="flex items-center gap-2 border-t border-white/10 bg-white/5 p-3">
                            <a href="{{ route('client.tickets.index') }}" wire:navigate
                                @click="notificationOpen = false"
                                class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:opacity-90">
                                View tickets
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </a>

                            @if ($unreadCount > 0)
                            <button type="button" wire:click="markAllClientNotificationsRead"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-blue-100/60 transition hover:bg-white/12 hover:text-white">
                                <span class="material-symbols-outlined text-[18px]">done_all</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- User Menu --}}
                <div class="relative">
                    <button type="button" @click="userMenu = !userMenu; notificationOpen = false"
                        class="flex cursor-pointer items-center gap-3 rounded-full px-2 py-1.5 text-white transition hover:bg-white/5">
                        @if (auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                            class="h-10 w-10 rounded-full object-cover" />
                        @else
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        @endif

                        <div class="text-sm font-semibold leading-none text-white">
                            {{ auth()->user()->name }}
                        </div>

                        <svg class="h-4 w-4 text-white/70 transition" :class="{ 'rotate-180': userMenu }"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="userMenu" x-transition @click.outside="userMenu = false" @click.stop
                        style="display: none;"
                        class="absolute right-0 top-full z-999 mt-3 w-56 overflow-hidden rounded-2xl border border-white/10 bg-slate-900/95 p-2 shadow-2xl backdrop-blur-xl">
                        <a href="{{ route('account.profile') }}" wire:navigate
                            class="block rounded-xl px-4 py-3 text-sm text-white transition hover:bg-white/10">
                            Profile
                        </a>

                        <a href="{{ route('account.dashboard') }}" wire:navigate
                            class="block rounded-xl px-4 py-3 text-sm text-white transition hover:bg-white/10">
                            Dashboard
                        </a>

                        <a href="{{ route('client.tickets.index') }}" wire:navigate
                            class="block rounded-xl px-4 py-3 text-sm text-white transition hover:bg-white/10">
                            My Tickets
                        </a>

                        <div class="my-2 border-t border-white/10"></div>

                        <form wire:submit.prevent="logout">
                            <button type="submit" wire:loading.attr="disabled"
                                class="block w-full cursor-pointer rounded-xl px-4 py-3 text-left text-sm text-red-300 transition hover:bg-red-500/15 disabled:opacity-60">
                                <span wire:loading.remove wire:target="logout">Logout</span>
                                <span wire:loading wire:target="logout">Logging out...</span>
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))"
                    class="glass-chip cursor-pointer rounded-full px-5 py-2.5 font-medium text-blue-50 transition hover:bg-white/20">
                    Sign In
                </button>

                <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'register' } }))"
                    class="cursor-pointer rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-5 py-2.5 font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:scale-[1.02]">
                    Get Started
                </button>
                @endauth
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                @auth
                {{-- Mobile Notification --}}
                <div class="relative" wire:key="client-notifications-mobile-{{ $notificationRefreshKey }}">
                    <button type="button" @click.stop="notificationOpen = !notificationOpen; mobileMenu = false"
                        class="glass-chip relative flex h-11 w-11 items-center justify-center rounded-xl text-white">
                        <span class="material-symbols-outlined">notifications</span>

                        @if ($unreadCount > 0)
                        <span
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-black text-white ring-2 ring-slate-950">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                        @endif
                    </button>

                    <div x-cloak x-show="notificationOpen" @click.outside="notificationOpen = false"
                        x-transition.origin.top.right style="display: none;"
                        class="absolute right-0 top-full z-999 mt-3 w-80 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-3xl border border-white/10 bg-slate-950/95 shadow-2xl backdrop-blur-2xl">

                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <div>
                                <h3 class="text-sm font-bold text-white">Notifications</h3>
                                <p class="text-xs text-blue-100/45">
                                    {{ $unreadCount }} new {{ Str::plural('update', $unreadCount) }}
                                </p>
                            </div>

                            <button type="button" @click="notificationOpen = false"
                                class="rounded-xl p-1.5 text-blue-100/45 transition hover:bg-white/10 hover:text-white">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>

                        <div class="notification-scroll max-h-88 divide-y divide-white/10 overflow-y-auto">
                            @forelse ($notifications as $ticket)
                            <a href="{{ route('client.tickets.show', $ticket['id']) }}" wire:navigate
                                @click="notificationOpen = false"
                                class="flex gap-3 px-4 py-3 transition hover:bg-white/8">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-blue-400/15 text-blue-200">
                                    <span class="material-symbols-outlined text-[20px]">support_agent</span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">
                                        Ticket updated
                                    </p>

                                    <p class="mt-0.5 truncate text-xs text-blue-100/55">
                                        {{ $ticket['subject'] }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-blue-100/35">
                                        {{ $ticket['time'] }}
                                    </p>
                                </div>
                            </a>
                            @empty
                            <div class="px-4 py-8 text-center text-sm text-blue-100/50">
                                No new notifications
                            </div>
                            @endforelse
                        </div>

                        <div class="flex items-center gap-2 border-t border-white/10 bg-white/5 p-3">
                            <a href="{{ route('client.tickets.index') }}" wire:navigate
                                @click="notificationOpen = false"
                                class="flex flex-1 items-center justify-center rounded-2xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-2.5 text-sm font-semibold text-white">
                                View tickets
                            </a>

                            @if ($unreadCount > 0)
                            <button type="button" wire:click="markAllClientNotificationsRead"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-blue-100/60">
                                <span class="material-symbols-outlined text-[18px]">done_all</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endauth

                <button @click="mobileMenu = !mobileMenu; notificationOpen = false"
                    class="glass-chip flex h-11 w-11 items-center justify-center rounded-xl text-white lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- Mobile Side Drawer --}}
    <div x-show="mobileMenu" x-cloak class="lg:hidden">
        {{-- Backdrop --}}
        <div x-show="mobileMenu"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenu = false"
            class="fixed inset-0 z-[9998] bg-black/60 backdrop-blur-sm">
        </div>

        {{-- Drawer Panel --}}
        <div x-show="mobileMenu"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed right-0 top-0 z-[9999] flex h-full w-[320px] max-w-[85vw] flex-col border-l border-white/16 glass-panel shadow-2xl shadow-black/40">

            {{-- Drawer Header --}}
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <a href="{{ route('home') }}" wire:navigate @click="mobileMenu = false" class="flex items-center gap-3">
                    @if ($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-8 rounded-lg object-contain">
                    @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-linear-to-r from-blue-500 to-sky-400 text-xs font-bold text-white">
                        {{ strtoupper(substr($siteName, 0, 1)) }}
                    </div>
                    <span class="text-sm font-bold text-white">{{ $siteName }}</span>
                    @endif
                </a>
                <button type="button" @click="mobileMenu = false"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/60 transition hover:bg-white/10 hover:text-white">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- Drawer Body --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">
                <div class="flex flex-col gap-1.5 text-sm text-blue-50/85">
                    <a href="{{ route('home') }}" wire:navigate @click="mobileMenu = false"
                        class="rounded-xl px-4 py-3 transition hover:bg-white/8">Home</a>

                    {{-- Mobile Services --}}
                    <div class="rounded-xl overflow-hidden">
                        <button type="button" @click="mobileServicesOpen = !mobileServicesOpen"
                            class="flex w-full items-center justify-between px-4 py-3 transition hover:bg-white/8">
                            <span>Services</span>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': mobileServicesOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="mobileServicesOpen" x-collapse style="display: none;">
                            <div class="border-t border-white/8 px-4 pb-3 pt-2">
                                @foreach ($serviceCategories as $category)
                                <p class="mb-1 mt-2 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-cyan-400/70">
                                    <span class="material-symbols-outlined text-[14px]">{{ $category['icon'] }}</span>
                                    {{ $category['name'] }}
                                </p>
                                @foreach ($category['services'] as $service)
                                <a href="{{ route('client.services.details', $service['slug']) }}" wire:navigate @click="mobileMenu = false"
                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-blue-100/60 transition hover:text-white">
                                    <span class="h-1 w-1 shrink-0 rounded-full bg-white/20"></span>
                                    {{ $service['name'] }}
                                </a>
                                @if (!empty($service['options']))
                                <div class="ml-5 flex flex-col gap-0.5">
                                    @foreach ($service['options'] as $option)
                                    <a href="{{ route('client.services.options', $service['slug']) }}" wire:navigate @click="mobileMenu = false"
                                        class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-[11px] text-blue-100/40 transition hover:text-white">
                                        <span class="h-0.5 w-0.5 shrink-0 rounded-full bg-white/15"></span>
                                        {{ $option['name'] }}
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                                @endforeach
                                @endforeach
                                <a href="{{ route('client.services') }}" wire:navigate @click="mobileMenu = false"
                                    class="mt-2 block text-center rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-white/10">
                                    View All Services
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Mobile Tools --}}
                    <div class="rounded-xl overflow-hidden">
                        <button type="button" @click="mobileToolsOpen = !mobileToolsOpen"
                            class="flex w-full items-center justify-between px-4 py-3 transition hover:bg-white/8">
                            <span>Tools</span>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': mobileToolsOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="mobileToolsOpen" x-collapse style="display: none;">
                            <div class="border-t border-white/8 px-4 pb-3 pt-2">
                                @forelse ($toolCategories as $category)
                                <p class="mb-1 mt-2 first:mt-0 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-purple-400/70">
                                    <span class="material-symbols-outlined text-[14px]">{{ $category['icon'] }}</span>
                                    {{ $category['name'] }}
                                </p>
                                @foreach ($category['tools'] as $tool)
                                <a href="{{ route($tool['route']) }}" wire:navigate @click="mobileMenu = false"
                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-blue-100/60 transition hover:text-white">
                                    <span class="h-1 w-1 shrink-0 rounded-full bg-white/20"></span>
                                    {{ $tool['name'] }}
                                </a>
                                @endforeach
                                @empty
                                <p class="text-xs text-blue-100/40">No tools available yet.</p>
                                @endforelse
                                <a href="{{ route('client.tools.index') }}" wire:navigate @click="mobileMenu = false"
                                    class="mt-2 block text-center rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-white/10">
                                    View All Tools
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('client.blogs') }}" wire:navigate @click="mobileMenu = false"
                        class="rounded-xl px-4 py-3 transition hover:bg-white/8">Blogs</a>

                    <a href="{{ route('client.about') }}" wire:navigate @click="mobileMenu = false"
                        class="rounded-xl px-4 py-3 transition hover:bg-white/8">About</a>

                    <a href="{{ route('client.contact') }}" wire:navigate @click="mobileMenu = false"
                        class="rounded-xl px-4 py-3 transition hover:bg-white/8">Contact</a>

                    @auth
                    <div class="mt-3 border-t border-white/10 pt-3">
                        <div class="flex items-center gap-3 rounded-xl px-2 py-2 text-white">
                            @if (auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                                class="h-10 w-10 rounded-full object-cover" />
                            @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            @endif
                            <div class="text-sm font-semibold">{{ auth()->user()->name }}</div>
                        </div>

                        <div class="mt-2 flex flex-col gap-1">
                            <a href="{{ route('account.profile') }}" wire:navigate @click="mobileMenu = false"
                                class="rounded-xl px-4 py-3 transition hover:bg-white/8">Profile</a>
                            <a href="{{ route('account.dashboard') }}" wire:navigate @click="mobileMenu = false"
                                class="rounded-xl px-4 py-3 transition hover:bg-white/8">Dashboard</a>
                            <a href="{{ route('client.tickets.index') }}" wire:navigate @click="mobileMenu = false"
                                class="rounded-xl px-4 py-3 transition hover:bg-white/8">My Tickets</a>

                            <form wire:submit.prevent="logout">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="w-full rounded-xl px-4 py-3 text-left text-red-300 transition hover:bg-red-500/15 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="logout">Logout</span>
                                    <span wire:loading wire:target="logout">Logging out...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <div class="mt-3 flex flex-col gap-2 border-t border-white/10 pt-3">
                        <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } })); mobileMenu = false"
                            class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center font-medium transition hover:bg-white/10">
                            Sign In
                        </button>
                        <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'register' } })); mobileMenu = false"
                            class="rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-3 text-center font-semibold">
                            Get Started
                        </button>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>