<?php

use App\Models\Proposal;
use App\Models\SupportTicket;
use App\Models\UserService;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
{
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

    public function serviceStatusClass(?string $status): string
    {
        return match ($status) {
            'active' => 'client-badge client-badge-green',
            'ongoing' => 'client-badge client-badge-blue',
            'pending' => 'client-badge client-badge-yellow',
            'inactive' => 'client-badge',
            'expired' => 'client-badge client-badge-red',
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

        $recentServices = UserService::query()
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

        $activeServices = UserService::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        $openTickets = SupportTicket::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'pending', 'in_progress', 'answered'])
            ->count();

        return [
            'sentProposals' => $sentProposals,
            'sentProposalCount' => $sentProposals->count(),

            'recentServices' => $recentServices,
            'latestTickets' => $latestTickets,

            'activeServices' => $activeServices,
            'openTickets' => $openTickets,
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

                    {{-- Top Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button
                                @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Client Dashboard
                                </p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                                    Overview
                                </h1>
                            </div>
                        </div>
                    </div>

                    {{-- Sent Proposals Alert --}}
                    @if ($sentProposals->count())
                        <div class="mb-6 space-y-4">
                            @foreach ($sentProposals as $proposal)
                                <div
                                    wire:key="dashboard-proposal-alert-{{ $proposal->id }}"
                                    x-data="{ visible: true }"
                                    x-show="visible"
                                    x-transition
                                    class="overflow-hidden rounded-[28px] border border-cyan-300/20 bg-cyan-400/10 shadow-[0_18px_60px_rgba(0,0,0,0.22)] backdrop-blur-2xl">

                                    <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex min-w-0 items-start gap-4">
                                            <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/25 bg-cyan-300/15 text-cyan-100">
                                                <span class="material-symbols-outlined">request_quote</span>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200">
                                                        New Proposal Ready
                                                    </p>

                                                    @if ($proposal->valid_until)
                                                        <span class="rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-amber-200">
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
                                            <a href="{{ route('client.proposals.index', ['proposal' => $proposal->id]) }}"
                                                wire:navigate
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-cyan-400 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                                View Proposal
                                            </a>

                                            <button
                                                type="button"
                                                @click="visible = false"
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
                    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">

                        {{-- Left Content --}}
                        <div class="space-y-6">

                            {{-- Stats --}}
                            <div class="grid gap-5 md:grid-cols-3">
                                <div class="client-card p-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Active Services
                                    </p>
                                    <h3 class="mt-5 text-4xl font-bold text-white">
                                        {{ str_pad($activeServices, 2, '0', STR_PAD_LEFT) }}
                                    </h3>
                                    <p class="mt-2 text-sm text-blue-100/60">
                                        Currently running services
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

                            {{-- Service Activity --}}
                            <div class="client-card p-6">
                                <div class="mb-5 flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                            Recent Services
                                        </p>
                                        <h2 class="mt-2 text-2xl font-bold text-white">
                                            Service activity
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
                                                <th class="px-3 py-3 font-medium">Service</th>
                                                <th class="px-3 py-3 font-medium">Status</th>
                                                <th class="px-3 py-3 font-medium">Started</th>
                                                <th class="px-3 py-3 font-medium">Last Update</th>
                                            </tr>
                                        </thead>

                                        <tbody class="text-sm text-blue-50/90">
                                            @forelse ($recentServices as $userService)
                                                @php
                                                    $serviceName = $userService->service?->card_title
                                                        ?? $userService->service?->detail_title
                                                        ?? $userService->service?->name
                                                        ?? $userService->service?->title
                                                        ?? 'Service';
                                                @endphp

                                                <tr wire:key="dashboard-service-{{ $userService->id }}"
                                                    class="border-b border-white/10 last:border-b-0">
                                                    <td class="px-3 py-4 font-semibold">
                                                        {{ $serviceName }}
                                                    </td>

                                                    <td class="px-3 py-4">
                                                        <span class="{{ $this->serviceStatusClass($userService->status) }}">
                                                            {{ ucfirst($userService->status ?? 'active') }}
                                                        </span>
                                                    </td>

                                                    <td class="px-3 py-4">
                                                        {{ $this->formatDate($userService->start_date ?? $userService->created_at) }}
                                                    </td>

                                                    <td class="px-3 py-4">
                                                        {{ $this->timeAgo($userService->updated_at) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-3 py-10 text-center text-blue-100/55">
                                                        No service activity found yet.
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
                                        <a href="{{ route('client.tickets.index', ['ticket' => $ticket->id]) }}"
                                            wire:navigate
                                            wire:key="dashboard-ticket-{{ $ticket->id }}"
                                            class="block rounded-2xl border border-white/10 bg-white/6 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="truncate text-sm font-bold text-white">
                                                            {{ $ticket->subject ?? $ticket->title ?? 'Support Ticket' }}
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
                                        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 px-6 py-10 text-center">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
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
                        </div>

                        {{-- Right --}}
                        <div class="space-y-6">
                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Quick Actions
                                </p>
                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Shortcuts
                                </h2>

                                <div class="mt-6 space-y-3">
                                    <a href="{{ route('client.tickets.index') }}" wire:navigate class="client-shortcut">
                                        Open Ticket
                                    </a>

                                    <a href="{{ route('client.proposals.index') }}" wire:navigate class="client-shortcut">
                                        View Proposals
                                    </a>

                                    <a href="{{ route('account.profile') }}" wire:navigate class="client-shortcut">
                                        Update Profile
                                    </a>

                                    <a href="{{ route('account.services') }}" wire:navigate class="client-shortcut">
                                        My Services
                                    </a>
                                </div>
                            </div>

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Account Summary
                                </p>
                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Profile snapshot
                                </h2>

                                <div class="mt-6 space-y-4 text-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Account Type</span>
                                        <span class="font-semibold text-white">
                                            {{ auth()->user()->type ?? 'personal' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Role</span>
                                        <span class="font-semibold text-white">
                                            {{ auth()->user()->role->value ?? 'client' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-blue-100/55">Status</span>
                                        <span class="font-semibold text-emerald-300">Active</span>
                                    </div>
                                </div>
                            </div>

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Support
                                </p>
                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Need help?
                                </h2>

                                <p class="mt-4 text-sm leading-7 text-blue-100/68">
                                    Contact support for service issues, account help, or proposal clarification.
                                </p>

                                <div class="mt-6 space-y-3">
                                    <a href="tel:+8801000000000"
                                        class="inline-flex w-full items-center justify-center rounded-full border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                        Call Support
                                    </a>

                                    <a href="https://wa.me/8801000000000"
                                        class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-green-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                        WhatsApp Support
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
     </div>
</div>