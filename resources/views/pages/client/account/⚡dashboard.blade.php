<?php

use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupportTicket;
use App\Models\ToolSubscription;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.account-app')] #[Title('Dashboard')] class extends Component {
    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function timeAgo($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->diffForHumans();
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
                $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->latest('sent_at')
            ->latest()
            ->get();

        $recentOrders = Order::query()
            ->with(['service', 'servicePlan', 'pricingPlan', 'booking'])
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->latest()
            ->take(5)
            ->get();

        $latestTickets = SupportTicket::query()->where('user_id', $userId)->latest('updated_at')->latest()->take(5)->get();

        $activeOrders = Order::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'paid'])
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

        return [
            'sentProposals' => $sentProposals,
            'sentProposalCount' => $sentProposals->count(),

            'recentOrders' => $recentOrders,
            'latestTickets' => $latestTickets,

            'activeOrders' => $activeOrders,
            'openTickets' => $openTickets,
            'activeSubscriptions' => $activeSubscriptions,
            'activeToolSubs' => $activeToolSubs,
        ];
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div class="basis-2/3">
            <h2 class="text-xl font-bold md:text-h1 text-white">Client Dashboard</h2>
            <p class="text-xs md:text-body-md text-blue-100/60">
                Overview of your account activity and status.
            </p>
        </div>
    </div>

    {{-- Sent Proposals Alert --}}
    @if ($sentProposals->count())
        <div class="mb-6 space-y-4">
            @foreach ($sentProposals as $proposal)
                <div wire:key="dashboard-proposal-alert-{{ $proposal->id }}" x-data="{ visible: true }" x-show="visible"
                    x-transition
                    class="overflow-hidden rounded-[28px] border border-cyan-300/20 bg-cyan-400/10 shadow-[0_18px_60px_rgba(0,0,0,0.22)] backdrop-blur-2xl">

                    <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <div
                                class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/25 bg-cyan-300/15 text-cyan-100">
                                <span class="material-symbols-outlined">request_quote</span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200">
                                        New Proposal Ready
                                    </p>

                                    @if ($proposal->valid_until)
                                        <span
                                            class="rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-amber-200">
                                            Valid until {{ $proposal->valid_until->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>

                                <h2 class="mt-2 text-xl font-bold text-white">
                                    {{ $proposal->subject ?: 'Service Proposal' }}
                                </h2>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-blue-100/65">
                                    <span>
                                        Proposal No:
                                        <span class="font-semibold text-white">
                                            {{ $proposal->proposal_no }}
                                        </span>
                                    </span>

                                    <span>
                                        Items:
                                        <span class="font-semibold text-white">
                                            {{ $proposal->items->count() }}
                                        </span>
                                    </span>

                                    <span>
                                        Total:
                                        <span class="font-semibold text-cyan-100">
                                            ৳{{ number_format($proposal->total(), 2) }}
                                        </span>
                                    </span>
                                </div>

                                @if ($proposal->note)
                                    <p class="mt-3 line-clamp-2 max-w-3xl text-sm leading-6 text-blue-100/60">
                                        {{ $proposal->note }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-3">
                            <a href="{{ route('client.proposals.index', ['proposal' => $proposal->id]) }}" wire:navigate
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-cyan-400 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                                View Proposal
                            </a>

                            <button type="button" @click="visible = false"
                                class="inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-blue-100/60 transition hover:bg-white/12 hover:text-white"
                                title="Hide temporarily">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Content --}}
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="client-card p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Active Orders
                </p>
                <h3 class="mt-5 text-4xl font-bold text-white">
                    {{ str_pad($activeOrders, 2, '0', STR_PAD_LEFT) }}
                </h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    Currently active services & plans
                </p>
            </div>

            <div class="client-card p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Tool Subscriptions
                </p>
                <h3 class="mt-5 text-4xl font-bold text-white">
                    {{ str_pad($activeToolSubs, 2, '0', STR_PAD_LEFT) }}
                </h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    Active tool subscriptions
                </p>
            </div>

            <div class="client-card p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Open Tickets
                </p>
                <h3 class="mt-5 text-4xl font-bold text-white">
                    {{ str_pad($openTickets, 2, '0', STR_PAD_LEFT) }}
                </h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    Support requests in progress
                </p>
            </div>

            <div class="client-card p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                    Proposals
                </p>
                <h3 class="mt-5 text-4xl font-bold text-white">
                    {{ str_pad($sentProposalCount, 2, '0', STR_PAD_LEFT) }}
                </h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    Pending proposals for review
                </p>
            </div>
        </div>

        {{-- Recent Orders --}}
        <div class="client-card p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                        Recent Orders
                    </p>
                    <h2 class="mt-2 text-2xl font-bold text-white">
                        Service & plan orders
                    </h2>
                </div>

                <a href="{{ route('account.services') }}" wire:navigate
                    class="text-sm font-medium text-cyan-200 hover:text-white">
                    View all
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 text-sm text-blue-100/45">
                            <th class="px-3 py-3 font-medium">Service / Plan</th>
                            <th class="px-3 py-3 font-medium">Plan Name</th>
                            <th class="px-3 py-3 font-medium">Addons</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-3 py-3 font-medium">Started</th>
                        </tr>
                    </thead>

                    <tbody class="text-sm text-blue-50/90">
                        @forelse ($recentOrders as $order)
                            @php
                                $addons = $order->booking?->addons ?? [];
                                $serviceName = $order->service?->card_title ?? ($order->plan_name ?? 'Service');
                                $planName =
                                    $order->servicePlan?->name ??
                                    ($order->order_type === 'pricing_plan' ? $order->pricingPlan?->title : 'N/A');
                            @endphp

                            <tr wire:key="dashboard-order-{{ $order->id }}"
                                class="border-b border-white/10 last:border-b-0">
                                <td class="px-3 py-4">
                                    <div class="font-semibold text-white">{{ $serviceName }}</div>
                                    <div class="mt-0.5 text-xs text-blue-100/45">{{ $order->order_no }}
                                    </div>
                                </td>

                                <td class="px-3 py-4 text-blue-100/75">
                                    {{ $planName }}
                                </td>

                                <td class="px-3 py-4">
                                    @if (count($addons))
                                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                            @foreach ($addons as $addon)
                                                <span class="flex items-center gap-1 text-blue-100/55">
                                                    {{-- <span class="text-cyan-300/60">+</span> --}}
                                                    {{ $addon['name'] }}
                                                    @if (!empty($addon['price']))
                                                        <span
                                                            class="text-blue-100/35">(৳{{ number_format((float) $addon['price'], 2) }})</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-blue-100/35">—</span>
                                    @endif
                                </td>

                                <td class="px-3 py-4">
                                    <span class="{{ $this->orderStatusClass($order->status) }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>

                                <td class="px-3 py-4 text-blue-100/75">
                                    {{ $this->formatDate($order->start_date ?? $order->created_at) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-10 text-center text-blue-100/55">
                                    No orders found yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Latest Ticket Updates --}}
        <div class="client-card p-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                        Ticket Result
                    </p>
                    <h2 class="mt-2 text-2xl font-bold text-white">
                        Latest ticket updates
                    </h2>
                </div>

                <a href="{{ route('client.tickets.index') }}" wire:navigate
                    class="text-sm font-medium text-cyan-200 hover:text-white">
                    View all
                </a>
            </div>

            <div class="space-y-4">
                @forelse ($latestTickets as $ticket)
                    <a href="{{ route('client.tickets.index', ['ticket' => $ticket->id]) }}" wire:navigate
                        wire:key="dashboard-ticket-{{ $ticket->id }}"
                        class="block rounded-2xl border border-white/10 bg-white/6 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-bold text-white">
                                        {{ $ticket->subject ?? ($ticket->title ?? 'Support Ticket') }}
                                    </p>

                                    <span class="{{ $this->ticketStatusClass($ticket->status) }}">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'open')) }}
                                    </span>
                                </div>

                                @if (!empty($ticket->ticket_no))
                                    <p class="mt-1 text-xs text-blue-100/45">
                                        Ticket No: {{ $ticket->ticket_no }}
                                    </p>
                                @endif

                                @if (!empty($ticket->message))
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-blue-100/60">
                                        {{ $ticket->message }}
                                    </p>
                                @elseif (!empty($ticket->description))
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-blue-100/60">
                                        {{ $ticket->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="shrink-0 text-xs text-blue-100/45">
                                {{ $this->timeAgo($ticket->updated_at) }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div
                        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 px-6 py-10 text-center">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                            <span class="material-symbols-outlined">confirmation_number</span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-white">
                            No ticket updates found
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-blue-100/55">
                            Your latest ticket updates will appear here.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Tool Subscriptions --}}
        @if ($activeSubscriptions->isNotEmpty())
            <div class="client-card p-6">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                            Active Subscriptions
                        </p>
                        <h2 class="mt-2 text-2xl font-bold text-white">
                            Tool subscriptions
                        </h2>
                    </div>

                    <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                        class="text-sm font-medium text-cyan-200 hover:text-white">
                        View all
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach ($activeSubscriptions as $sub)
                        <div wire:key="dashboard-sub-{{ $sub->id }}"
                            class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/6 p-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300">
                                <span class="material-symbols-outlined">{{ $sub->category?->icon ?: 'build' }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-white">
                                    {{ $sub->category?->name ?? 'Tool' }}
                                </div>
                                <div class="mt-0.5 text-xs text-blue-100/45">
                                    {{ $sub->plan?->name ?? 'No plan' }}
                                </div>
                            </div>
                            <div class="shrink-0 text-xs text-blue-100/45">
                                @if ($sub->expires_at)
                                    Expires {{ $sub->expires_at->format('d M Y') }}
                                @else
                                    Active
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
