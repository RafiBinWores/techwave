<?php

use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupportTicket;
use App\Models\ToolSubscription;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
{
    public function formatDate($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function timeAgo($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        return Carbon::parse($date)->diffForHumans();
    }

    public function daysLeft($date): ?int
    {
        if (! $date) {
            return null;
        }

        return now()->startOfDay()->diffInDays(Carbon::parse($date)->startOfDay(), false);
    }

    public function expiryText($date): string
    {
        if (! $date) {
            return 'Active';
        }

        $days = $this->daysLeft($date);

        if ($days < 0) {
            return 'Expired';
        }

        if ($days === 0) {
            return 'Expires today';
        }

        if ($days === 1) {
            return 'Expires tomorrow';
        }

        return 'Expires in ' . $days . ' days';
    }

    public function orderStatusClass(?string $status): string
    {
        return match ($status) {
            'active' => 'client-badge client-badge-green',
            'paid' => 'client-badge client-badge-green',
            'pending' => 'client-badge client-badge-yellow',
            'awaiting_payment' => 'client-badge client-badge-yellow',
            'completed' => 'client-badge client-badge-blue',
            'cancelled' => 'client-badge client-badge-red',
            default => 'client-badge client-badge-blue',
        };
    }

    public function ticketStatusClass(?string $status): string
    {
        return match ($status) {
            'open' => 'client-badge client-badge-yellow',
            'pending' => 'client-badge client-badge-yellow',
            'in_progress' => 'client-badge client-badge-blue',
            'answered' => 'client-badge client-badge-blue',
            'resolved' => 'client-badge client-badge-green',
            'closed' => 'client-badge',
            default => 'client-badge client-badge-blue',
        };
    }

    public function with(): array
    {
        $userId = auth()->id();

        $sentProposals = Proposal::query()
            ->with('items')
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->latest('sent_at')
            ->latest()
            ->get();

        $recentOrders = Order::query()
            ->with('service')
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->latest()
            ->take(5)
            ->get();

        $latestTickets = SupportTicket::query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->latest()
            ->take(5)
            ->get();

        $activeOrders = Order::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'paid'])
            ->count();

        $totalOrders = Order::query()
            ->where('user_id', $userId)
            ->count();

        $openTickets = SupportTicket::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'pending', 'in_progress', 'answered'])
            ->count();

        $activeSubscriptions = ToolSubscription::query()
            ->with(['toolCategory', 'toolPlan'])
            ->where('user_id', $userId)
            ->active()
            ->latest()
            ->get();

        $activeToolSubs = $activeSubscriptions->count();

        $nextExpiringSubscription = $activeSubscriptions
            ->filter(fn ($sub) => ! empty($sub->expires_at))
            ->sortBy('expires_at')
            ->first();

        $latestOrder = $recentOrders->first();

        $latestTicket = $latestTickets->first();

        $attentionCount = $sentProposals->count() + $openTickets;

        return [
            'sentProposals' => $sentProposals,
            'sentProposalCount' => $sentProposals->count(),

            'recentOrders' => $recentOrders,
            'latestTickets' => $latestTickets,

            'activeOrders' => $activeOrders,
            'totalOrders' => $totalOrders,
            'openTickets' => $openTickets,

            'activeSubscriptions' => $activeSubscriptions,
            'activeToolSubs' => $activeToolSubs,
            'nextExpiringSubscription' => $nextExpiringSubscription,

            'latestOrder' => $latestOrder,
            'latestTicket' => $latestTicket,
            'attentionCount' => $attentionCount,
        ];
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="sidebarOpen = true"
                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Client Dashboard
                </p>

                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                    Overview
                </h1>

                <p class="mt-1 text-sm text-blue-100/50">
                    Manage your orders, tickets, proposals, and tool subscriptions.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('client.tickets.index') }}"
                wire:navigate
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                <span class="material-symbols-outlined text-base">support_agent</span>
                Support
            </a>

            <a
                href="{{ route('account.services') }}"
                wire:navigate
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5">
                <span class="material-symbols-outlined text-base">dashboard</span>
                My Services
            </a>
        </div>
    </div>

    {{-- Important Info --}}
    @if ($sentProposalCount > 0 || $openTickets > 0)
        <div class="mb-6 rounded-[28px] border border-amber-300/20 bg-amber-400/10 p-5 backdrop-blur-2xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-400/15 text-amber-200">
                        <span class="material-symbols-outlined">priority_high</span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-white">
                            Important updates
                        </h2>

                        <p class="mt-1 text-sm text-blue-100/60">
                            @if ($sentProposalCount > 0)
                                You have {{ $sentProposalCount }} proposal{{ $sentProposalCount > 1 ? 's' : '' }} waiting for review.
                            @endif

                            @if ($openTickets > 0)
                                {{ $sentProposalCount > 0 ? ' Also,' : 'You' }} have {{ $openTickets }} open support ticket{{ $openTickets > 1 ? 's' : '' }}.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($sentProposalCount > 0)
                        <a
                            href="{{ route('client.proposals.index') }}"
                            wire:navigate
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
                            View Proposals
                        </a>
                    @endif

                    @if ($openTickets > 0)
                        <a
                            href="{{ route('client.tickets.index') }}"
                            wire:navigate
                            class="rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                            View Tickets
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Stats --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="client-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.16em] text-blue-100/45">
                        Active Orders
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-white">
                        {{ str_pad($activeOrders, 2, '0', STR_PAD_LEFT) }}
                    </h3>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-400/10 text-emerald-300">
                    <span class="material-symbols-outlined">shopping_bag</span>
                </div>
            </div>
        </div>

        <div class="client-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.16em] text-blue-100/45">
                        Tool Plans
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-white">
                        {{ str_pad($activeToolSubs, 2, '0', STR_PAD_LEFT) }}
                    </h3>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-400/10 text-cyan-300">
                    <span class="material-symbols-outlined">build</span>
                </div>
            </div>
        </div>

        <div class="client-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.16em] text-blue-100/45">
                        Open Tickets
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-white">
                        {{ str_pad($openTickets, 2, '0', STR_PAD_LEFT) }}
                    </h3>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-400/10 text-blue-300">
                    <span class="material-symbols-outlined">confirmation_number</span>
                </div>
            </div>
        </div>

        <div class="client-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.16em] text-blue-100/45">
                        Proposals
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-white">
                        {{ str_pad($sentProposalCount, 2, '0', STR_PAD_LEFT) }}
                    </h3>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-400/10 text-amber-300">
                    <span class="material-symbols-outlined">request_quote</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('client.tools.image-compressor') }}" wire:navigate
            class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 transition hover:bg-white/[0.1]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-cyan-300">compress</span>
                <span class="font-semibold text-white">Compress Image</span>
            </div>
        </a>

        <a href="{{ route('client.tools.bg-remover') }}" wire:navigate
            class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 transition hover:bg-white/[0.1]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-purple-300">magic_exchange</span>
                <span class="font-semibold text-white">Remove BG</span>
            </div>
        </a>

        <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
            class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 transition hover:bg-white/[0.1]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-300">workspace_premium</span>
                <span class="font-semibold text-white">Subscriptions</span>
            </div>
        </a>

        <a href="{{ route('client.tickets.index') }}" wire:navigate
            class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 transition hover:bg-white/[0.1]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-300">support_agent</span>
                <span class="font-semibold text-white">Support</span>
            </div>
        </a>
    </div>

    {{-- Orders + Tickets --}}
    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Recent Orders --}}
        <div class="client-card p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                        Recent Orders
                    </p>

                    <h2 class="mt-2 text-xl font-bold text-white">
                        Latest services
                    </h2>
                </div>

                <a href="{{ route('account.services') }}" wire:navigate
                    class="text-sm font-semibold text-cyan-200 hover:text-white">
                    View all
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($recentOrders as $order)
                    @php
                        $orderLabel = $order->plan_name
                            ?? $order->service?->card_title
                            ?? $order->service?->detail_title
                            ?? $order->service?->name
                            ?? 'Order #'.$order->order_no;
                    @endphp

                    <div wire:key="dashboard-order-{{ $order->id }}"
                        class="rounded-2xl border border-white/10 bg-white/6 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white">
                                    {{ $orderLabel }}
                                </p>

                                <p class="mt-1 text-xs text-blue-100/45">
                                    {{ $order->order_no }} · Updated {{ $this->timeAgo($order->updated_at) }}
                                </p>
                            </div>

                            <span class="{{ $this->orderStatusClass($order->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/15 bg-white/5 p-8 text-center">
                        <p class="font-semibold text-white">No orders yet</p>
                        <p class="mt-1 text-sm text-blue-100/50">Your service orders will appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Latest Tickets --}}
        <div class="client-card p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                        Support
                    </p>

                    <h2 class="mt-2 text-xl font-bold text-white">
                        Latest tickets
                    </h2>
                </div>

                <a href="{{ route('client.tickets.index') }}" wire:navigate
                    class="text-sm font-semibold text-cyan-200 hover:text-white">
                    View all
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($latestTickets as $ticket)
                    <a href="{{ route('client.tickets.index', ['ticket' => $ticket->id]) }}"
                        wire:navigate
                        wire:key="dashboard-ticket-{{ $ticket->id }}"
                        class="block rounded-2xl border border-white/10 bg-white/6 p-4 transition hover:bg-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white">
                                    {{ $ticket->subject ?? $ticket->title ?? 'Support Ticket' }}
                                </p>

                                <p class="mt-1 text-xs text-blue-100/45">
                                    {{ $ticket->ticket_no ?? 'Ticket' }} · {{ $this->timeAgo($ticket->updated_at) }}
                                </p>
                            </div>

                            <span class="{{ $this->ticketStatusClass($ticket->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'open')) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/15 bg-white/5 p-8 text-center">
                        <p class="font-semibold text-white">No tickets yet</p>
                        <p class="mt-1 text-sm text-blue-100/50">Support updates will appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Active Subscriptions --}}
    <div class="mt-6 client-card p-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Active Subscriptions
                </p>

                <h2 class="mt-2 text-xl font-bold text-white">
                    Tool plans
                </h2>
            </div>

            <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                class="text-sm font-semibold text-cyan-200 hover:text-white">
                View all
            </a>
        </div>

        @if ($activeSubscriptions->isNotEmpty())
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($activeSubscriptions as $sub)
                    <div wire:key="dashboard-sub-{{ $sub->id }}"
                        class="rounded-2xl border border-white/10 bg-white/6 p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300">
                                <span class="material-symbols-outlined">
                                    {{ $sub->toolCategory?->icon ?: 'build' }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-white">
                                    {{ $sub->toolCategory?->name ?? 'Tool' }}
                                </p>

                                <p class="mt-0.5 text-xs text-blue-100/45">
                                    {{ $sub->toolPlan?->name ?? 'No plan' }}
                                    @if ($sub->expires_at)
                                        · Expires {{ $sub->expires_at->format('d M Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-white/15 bg-white/5 p-8 text-center">
                <p class="font-semibold text-white">No active subscriptions</p>
                <p class="mt-1 text-sm text-blue-100/50">
                    Your active tool plans will appear here.
                </p>
            </div>
        @endif
    </div>
</div>
            </div>
        </div>
    </div>
</div>