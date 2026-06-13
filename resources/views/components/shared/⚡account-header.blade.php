<?php

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public int $notificationRefreshKey = 0;

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
        $this->notificationRefreshKey++;
    }

    public function unreadClientTicketCount(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        return SupportTicket::query()->where('user_id', Auth::id())->whereNull('client_read_at')->count();
    }

    public function latestClientTicketNotifications()
    {
        if (!Auth::check()) {
            return collect();
        }

        return SupportTicket::query()->where('user_id', Auth::id())->whereNull('client_read_at')->latest('last_reply_at')->latest()->limit(5)->get();
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

        $this->notificationRefreshKey++;

        $this->dispatch('toast', message: 'All notifications marked as read.', type: 'success');
    }
};
?>

<header
    class="glass-panel flex items-center justify-between gap-3 h-16 px-4 sm:px-6 sticky top-0 z-30 font-manrope text-sm">

    <!-- Left -->
    <div class="flex items-center gap-3 flex-1 min-w-0">
        <button type="button" @click="sidebarOpen = true"
            class="lg:hidden p-2 rounded-lg text-white/70 hover:bg-white/10">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- Desktop Collapse Button -->
        <div class="hidden shrink-0 lg:flex">
            <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="flex cursor-pointer items-center justify-center px-2 rounded-lg border border-white/10 bg-white/8 py-1.5 text-white/70 transition hover:bg-white/12">
                <span class="material-symbols-outlined text-[20px]"
                    x-text="sidebarCollapsed ? 'chevron_right' : 'chevron_left'"></span>
            </button>
        </div>

        <!-- Nav Links -->
        <nav class="hidden md:flex items-center gap-1 ml-4">
            <a href="{{ route('home') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                Home
            </a>
            <a href="{{ route('client.services') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                Services
            </a>
            <a href="{{ route('client.tools.index') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                Tools
            </a>
            <a href="{{ route('client.blogs') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                Blog
            </a>
            <a href="{{ route('client.about') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                About
            </a>
            <a href="{{ route('client.contact') }}" wire:navigate
                wire:current.exact="bg-white/12 text-cyan-300"
                class="px-3 py-1.5 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 transition">
                Contact
            </a>
        </nav>
    </div>

    <!-- Right -->
    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

         @auth
            @php
                $unreadCount = $this->unreadClientTicketCount();
                $notifications = $this->latestClientTicketNotifications();
            @endphp

            {{-- Notification --}}
            <div x-data="{ notificationOpen: false }" class="relative" wire:key="client-notifications-{{ $notificationRefreshKey }}">
                <button type="button" @click.stop="notificationOpen = !notificationOpen"
                    class="relative flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white shadow-lg shadow-blue-950/20 backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/14 cursor-pointer">

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
                    class="absolute right-0 top-full z-999 mt-2 w-88 max-w-[calc(100vw-2rem)] overflow-hidden rounded-3xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-blue-950/30 backdrop-blur-2xl">

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
                            <a href="{{ route('client.tickets.show', $ticket) }}" wire:navigate
                                @click="notificationOpen = false"
                                class="group flex gap-3 px-4 py-3 transition hover:bg-white/8">

                                <div @class([
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10',
                                    'bg-blue-400/15 text-blue-200' => $ticket->priority === 'medium',
                                    'bg-slate-400/15 text-slate-200' => $ticket->priority === 'low',
                                    'bg-orange-400/15 text-orange-200' => $ticket->priority === 'high',
                                    'bg-red-400/15 text-red-200' => $ticket->priority === 'urgent',
                                ])>
                                    <span class="material-symbols-outlined text-[20px]">support_agent</span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="truncate text-sm font-semibold text-white">
                                            Ticket updated
                                        </p>

                                        <span @class([
                                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                            'bg-blue-400/15 text-blue-200' => $ticket->status === 'open',
                                            'bg-amber-400/15 text-amber-200' => $ticket->status === 'pending',
                                            'bg-emerald-400/15 text-emerald-200' => $ticket->status === 'answered',
                                            'bg-slate-400/15 text-slate-200' => $ticket->status === 'closed',
                                        ])>
                                            {{ $ticket->status }}
                                        </span>
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-blue-100/55">
                                        {{ $ticket->subject }}
                                    </p>

                                    <p class="mt-1 text-[11px] text-blue-100/35">
                                        {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->updated_at?->diffForHumans() }}
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
        @endauth


        <!-- User -->
        <div class="flex items-center gap-2">
            @if (auth()->user()->avatar)
                <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                    class="h-10 w-10 object-cover rounded-full border border-white/10" />
            @else
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-cyan-400 to-blue-500 text-sm font-bold text-white shadow-lg shadow-cyan-500/20">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            @endif

            <div class="hidden md:block text-left">
                <p class="text-white font-semibold text-sm leading-tight capitalize">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-blue-100/55 text-xs capitalize">
                    {{ auth()->user()->role ?? 'Client' }}
                </p>
            </div>
        </div>
    </div>
</header>
