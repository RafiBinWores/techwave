<?php

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $notificationRefreshKey = 0;

    #[On('echo-private:admin.tickets,.ticket.updated')]
    public function refreshAdminNotifications(): void
    {
        $action = $event['action'] ?? null;
        
        $this->notificationRefreshKey++;

        if ($action !== 'client_replied' && $action !== 'user_replied') {
            return;
        }

        $this->dispatch('admin-ticket-notification-received');
        $this->dispatch('toast', message: 'New support ticket update received.', type: 'info');
    }

    public function unreadTicketCount(): int
    {
        return SupportTicket::query()->whereNull('admin_read_at')->count();
    }

    public function latestTicketNotifications()
    {
        return SupportTicket::query()->with('user')->whereNull('admin_read_at')->latest('last_reply_at')->latest()->limit(5)->get();
    }

    public function markAllTicketNotificationsRead(): void
    {
        SupportTicket::query()
            ->whereNull('admin_read_at')
            ->update([
                'admin_read_at' => now(),
            ]);

        $this->notificationRefreshKey++;

        $this->dispatch('toast', message: 'All ticket notifications marked as read.', type: 'success');
    }
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

        <!-- Desktop Collapse Button -->
        <div class="hidden shrink-0 lg:flex">
            <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="flex cursor-pointer items-center justify-center px-2 rounded-lg border border-slate-200 bg-white py-1.5 text-slate-600 transition hover:bg-slate-100">
                <span class="material-symbols-outlined text-[20px]"
                    x-text="sidebarCollapsed ? 'chevron_right' : 'chevron_left'"></span>
            </button>
        </div>

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
        <div x-data="{ notificationOpen: false }" class="relative"
            x-on:admin-ticket-notification-received.window="$nextTick(() => {})">
            @php
                $unreadTicketCount = $this->unreadTicketCount();
                $ticketNotifications = $this->latestTicketNotifications();
            @endphp

            <button type="button" @click.stop="notificationOpen = !notificationOpen"
                class="relative cursor-pointer rounded-full p-2 text-slate-500 transition-colors hover:bg-slate-100">
                <span class="material-symbols-outlined">notifications</span>

                @if ($unreadTicketCount > 0)
                    <span wire:key="admin-ticket-badge-{{ $notificationRefreshKey }}-{{ $unreadTicketCount }}"
                        class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                        {{ $unreadTicketCount > 99 ? '99+' : $unreadTicketCount }}
                    </span>
                @endif
            </button>

            <div x-cloak x-show="notificationOpen" @click.outside="notificationOpen = false"
                x-transition.origin.top.right
                class="absolute right-0 top-full z-9999 mt-3 w-88 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">

                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Notifications</h3>
                        <p class="text-xs text-slate-500">
                            {{ $unreadTicketCount }} new support {{ Str::plural('ticket', $unreadTicketCount) }}
                        </p>
                    </div>

                    <button type="button" @click="notificationOpen = false"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div wire:key="admin-ticket-notification-list-{{ $notificationRefreshKey }}"
                    class="max-h-88 divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($ticketNotifications as $ticket)
                        <a href="{{ route('admin.tickets.show', $ticket) }}" wire:navigate
                            @click="notificationOpen = false" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50">

                            <div @class([
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                'bg-blue-100 text-blue-700' => $ticket->priority === 'medium',
                                'bg-slate-100 text-slate-700' => $ticket->priority === 'low',
                                'bg-orange-100 text-orange-700' => $ticket->priority === 'high',
                                'bg-red-100 text-red-700' => $ticket->priority === 'urgent',
                            ])>
                                <span class="material-symbols-outlined text-[20px]">confirmation_number</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-slate-800">
                                        New ticket update
                                    </p>

                                    <span @class([
                                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                        'bg-blue-50 text-blue-700' => $ticket->priority === 'medium',
                                        'bg-slate-100 text-slate-600' => $ticket->priority === 'low',
                                        'bg-orange-50 text-orange-700' => $ticket->priority === 'high',
                                        'bg-red-50 text-red-700' => $ticket->priority === 'urgent',
                                    ])>
                                        {{ $ticket->priority }}
                                    </span>
                                </div>

                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ $ticket->subject }}
                                </p>

                                <p class="mt-0.5 truncate text-xs text-slate-400">
                                    By {{ $ticket->customer_name ?? ($ticket->user?->name ?? 'Customer') }}
                                </p>

                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <span class="material-symbols-outlined">notifications_off</span>
                            </div>

                            <h4 class="mt-3 text-sm font-semibold text-slate-900">
                                No new notifications
                            </h4>

                            <p class="mt-1 text-xs text-slate-500">
                                New ticket updates will appear here.
                            </p>
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50 p-3">
                    <a href="{{ route('admin.tickets.index') }}" wire:navigate @click="notificationOpen = false"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        View all tickets
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>

                    @if ($unreadTicketCount > 0)
                        <button type="button" wire:click="markAllTicketNotificationsRead"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                            <span class="material-symbols-outlined text-[18px]">done_all</span>
                        </button>
                    @endif
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
            @if (auth()->user()->avatar)
                <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                    class="h-10 w-10 object-cover rounded-full" />
            @else
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-primary to-sky-600 text-sm font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            @endif


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
