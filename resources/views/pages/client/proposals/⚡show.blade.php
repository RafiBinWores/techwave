<?php

use App\Models\Proposal;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('View Proposal')] class extends Component
{
    public Proposal $proposal;

    public function mount(Proposal $proposal): void
    {
        abort_if($proposal->user_id !== auth()->id(), 403);

        $this->proposal = $proposal->load('items');
    }

    public function acceptProposal(): void
    {
        abort_if($this->proposal->user_id !== auth()->id(), 403);

        if ($this->proposal->status !== 'sent') {
            return;
        }

        $this->proposal->update([
            'status' => 'accepted',
        ]);

        $this->proposal->refresh()->load('items');

        $this->dispatch('toast', message: 'Proposal accepted successfully.', type: 'success');
    }

    public function rejectProposal(): void
    {
        abort_if($this->proposal->user_id !== auth()->id(), 403);

        if ($this->proposal->status !== 'sent') {
            return;
        }

        $this->proposal->update([
            'status' => 'rejected',
        ]);

        $this->proposal->refresh()->load('items');

        $this->dispatch('toast', message: 'Proposal rejected successfully.', type: 'success');
    }

    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function statusClass(?string $status): string
    {
        return match ($status) {
            'sent' => 'border-blue-300/20 bg-blue-400/10 text-blue-200',
            'accepted' => 'border-emerald-300/20 bg-emerald-400/10 text-emerald-200',
            'rejected' => 'border-red-300/20 bg-red-400/10 text-red-200',
            default => 'border-cyan-300/20 bg-cyan-400/10 text-cyan-200',
        };
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                <livewire:shared.user-sidebar />

                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button
                                @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Client Dashboard
                                </p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                                    Proposal Details
                                </h1>
                            </div>
                        </div>

                        <a href="{{ route('client.proposals.index') }}" wire:navigate
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            Back to Proposals
                        </a>
                    </div>

                    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">

                        {{-- Proposal Body --}}
                        <div class="space-y-6">

                            {{-- Main Card --}}
                            <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                            Proposal
                                        </p>

                                        <h2 class="mt-2 text-2xl font-bold text-white">
                                            {{ $proposal->subject ?: 'Service Proposal' }}
                                        </h2>

                                        <p class="mt-2 font-mono text-sm text-blue-100/45">
                                            {{ $proposal->proposal_no }}
                                        </p>
                                    </div>

                                    <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-semibold {{ $this->statusClass($proposal->status) }}">
                                        {{ ucfirst($proposal->status) }}
                                    </span>
                                </div>

                                @if ($proposal->note)
                                    <div class="mt-6 rounded-2xl border border-white/10 bg-white/6 p-5">
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                            Note
                                        </p>

                                        <p class="mt-3 text-sm leading-7 text-blue-50/80">
                                            {{ $proposal->note }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Items --}}
                            <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="mb-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Proposal Items
                                    </p>

                                    <h2 class="mt-2 text-2xl font-bold text-white">
                                        Services & pricing
                                    </h2>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-left">
                                        <thead>
                                            <tr class="border-b border-white/10 text-sm text-blue-100/45">
                                                <th class="px-3 py-3 font-medium">Item</th>
                                                <th class="px-3 py-3 font-medium">Qty</th>
                                                <th class="px-3 py-3 font-medium">Unit Price</th>
                                                <th class="px-3 py-3 text-right font-medium">Total</th>
                                            </tr>
                                        </thead>

                                        <tbody class="text-sm text-blue-50/90">
                                            @forelse ($proposal->items as $item)
                                                <tr class="border-b border-white/10 last:border-b-0">
                                                    <td class="px-3 py-4">
                                                        <p class="font-semibold text-white">
                                                            {{ $item->title ?? $item->name ?? 'Proposal Item' }}
                                                        </p>

                                                        @if (!empty($item->description))
                                                            <p class="mt-1 text-xs leading-5 text-blue-100/50">
                                                                {{ $item->description }}
                                                            </p>
                                                        @endif
                                                    </td>

                                                    <td class="px-3 py-4">
                                                        {{ number_format((float) $item->quantity, 2) }}
                                                    </td>

                                                    <td class="px-3 py-4">
                                                        ৳{{ number_format((float) $item->unit_price, 2) }}
                                                    </td>

                                                    <td class="px-3 py-4 text-right font-bold text-white">
                                                        ৳{{ number_format((float) $item->quantity * (float) $item->unit_price, 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-3 py-10 text-center text-blue-100/55">
                                                        No items found for this proposal.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Sidebar Summary --}}
                        <div class="space-y-6">

                            {{-- Summary --}}
                            <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Summary
                                </p>

                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Proposal total
                                </h2>

                                <div class="mt-6 space-y-4 text-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Subtotal</span>
                                        <span class="font-semibold text-white">
                                            ৳{{ number_format($proposal->subtotal(), 2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Discount</span>
                                        <span class="font-semibold text-amber-300">
                                            - ৳{{ number_format($proposal->discountAmount(), 2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 text-lg">
                                        <span class="font-bold text-white">Total</span>
                                        <span class="font-bold text-cyan-200">
                                            ৳{{ number_format($proposal->total(), 2) }}
                                        </span>
                                    </div>
                                </div>

                                @if ($proposal->status === 'sent')
                                    <div class="mt-6 grid gap-3">
                                        <button
                                            type="button"
                                            wire:click="acceptProposal"
                                            wire:confirm="Are you sure you want to accept this proposal?"
                                            class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-emerald-500 to-green-400 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                            <span class="material-symbols-outlined text-lg">check_circle</span>
                                            Accept Proposal
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="rejectProposal"
                                            wire:confirm="Are you sure you want to reject this proposal?"
                                            class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-300/20 bg-red-400/10 px-5 py-3 text-sm font-bold text-red-100 transition hover:bg-red-400/15">
                                            <span class="material-symbols-outlined text-lg">block</span>
                                            Reject Proposal
                                        </button>
                                    </div>
                                @endif
                            </div>

                            {{-- Customer --}}
                            <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Customer
                                </p>

                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Information
                                </h2>

                                <div class="mt-6 space-y-4 text-sm">
                                    <div class="border-b border-white/10 pb-3">
                                        <span class="block text-blue-100/55">Name</span>
                                        <span class="mt-1 block font-semibold text-white">
                                            {{ $proposal->customer_name ?: 'N/A' }}
                                        </span>
                                    </div>

                                    <div class="border-b border-white/10 pb-3">
                                        <span class="block text-blue-100/55">Email</span>
                                        <span class="mt-1 block font-semibold text-white">
                                            {{ $proposal->customer_email ?: 'N/A' }}
                                        </span>
                                    </div>

                                    <div class="border-b border-white/10 pb-3">
                                        <span class="block text-blue-100/55">Phone</span>
                                        <span class="mt-1 block font-semibold text-white">
                                            {{ $proposal->customer_phone ?: 'N/A' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="block text-blue-100/55">Company</span>
                                        <span class="mt-1 block font-semibold text-white">
                                            {{ $proposal->company_name ?: 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Dates --}}
                            <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Dates
                                </p>

                                <div class="mt-6 space-y-4 text-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                                        <span class="text-blue-100/55">Sent At</span>
                                        <span class="font-semibold text-white">
                                            {{ $this->formatDate($proposal->sent_at) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-blue-100/55">Valid Until</span>
                                        <span class="font-semibold text-white">
                                            {{ $this->formatDate($proposal->valid_until) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>