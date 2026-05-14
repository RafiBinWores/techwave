<?php

use App\Models\Proposal;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Proposals')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
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
            'expired' => 'border-slate-300/20 bg-slate-400/10 text-slate-200',
            default => 'border-cyan-300/20 bg-cyan-400/10 text-cyan-200',
        };
    }

    public function rejectProposal(int $proposalId): void
    {
        Proposal::query()
            ->where('id', $proposalId)
            ->where('user_id', auth()->id())
            ->where('status', 'sent')
            ->update([
                'status' => 'rejected',
            ]);

        $this->dispatch('toast', message: 'Proposal rejected successfully.', type: 'success');
    }

    public function acceptProposal(int $proposalId): void
    {
        Proposal::query()
            ->where('id', $proposalId)
            ->where('user_id', auth()->id())
            ->where('status', 'sent')
            ->update([
                'status' => 'accepted',
            ]);

        $this->dispatch('toast', message: 'Proposal accepted successfully.', type: 'success');
    }

    public function proposals()
    {
        $search = trim($this->search);

        return Proposal::query()
            ->with('items')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['sent', 'accepted', 'rejected'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('proposal_no', 'like', '%' . $search . '%')
                        ->orWhere('subject', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest('sent_at')
            ->latest()
            ->paginate(8);
    }

    public function with(): array
    {
        $totalProposals = Proposal::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['sent', 'accepted', 'rejected'])
            ->count();

        $pendingProposals = Proposal::query()
            ->where('user_id', auth()->id())
            ->where('status', 'sent')
            ->count();

        $acceptedProposals = Proposal::query()
            ->where('user_id', auth()->id())
            ->where('status', 'accepted')
            ->count();

        return [
            'totalProposals' => $totalProposals,
            'pendingProposals' => $pendingProposals,
            'acceptedProposals' => $acceptedProposals,
        ];
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
                                    My Proposals
                                </h1>
                            </div>
                        </div>

                        <a href="{{ route('account.services') }}" wire:navigate
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            Back to Services
                        </a>
                    </div>

                    {{-- Stats --}}
                    <div class="mb-6 grid gap-5 md:grid-cols-3">
                        <div class="client-card p-6">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Total Proposals</p>
                            <h3 class="mt-5 text-4xl font-bold text-white">
                                {{ str_pad($totalProposals, 2, '0', STR_PAD_LEFT) }}
                            </h3>
                            <p class="mt-2 text-sm text-blue-100/60">All received proposals</p>
                        </div>

                        <div class="client-card p-6">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Pending Review</p>
                            <h3 class="mt-5 text-4xl font-bold text-amber-300">
                                {{ str_pad($pendingProposals, 2, '0', STR_PAD_LEFT) }}
                            </h3>
                            <p class="mt-2 text-sm text-blue-100/60">Waiting for your decision</p>
                        </div>

                        <div class="client-card p-6">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Accepted</p>
                            <h3 class="mt-5 text-4xl font-bold text-emerald-300">
                                {{ str_pad($acceptedProposals, 2, '0', STR_PAD_LEFT) }}
                            </h3>
                            <p class="mt-2 text-sm text-blue-100/60">Approved proposals</p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="mb-6 rounded-[28px] border border-white/10 bg-white/8 p-5 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="grid gap-4 lg:grid-cols-[1fr_220px]">
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model.live.debounce.400ms="search"
                                    placeholder="Search proposal no, subject, company..."
                                    class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 pl-12 pr-4 text-sm text-white placeholder:text-blue-100/35 outline-none backdrop-blur-xl focus:border-cyan-300/40">

                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-blue-100/45">
                                    search
                                </span>
                            </div>

                            <select
                                wire:model.live="status"
                                class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-sm text-white outline-none backdrop-blur-xl focus:border-cyan-300/40">
                                <option value="all" class="bg-slate-900">All Status</option>
                                <option value="sent" class="bg-slate-900">Pending Review</option>
                                <option value="accepted" class="bg-slate-900">Accepted</option>
                                <option value="rejected" class="bg-slate-900">Rejected</option>
                            </select>
                        </div>
                    </div>

                    {{-- Proposal List --}}
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="mb-6">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                Received Proposals
                            </p>
                            <h2 class="mt-2 text-2xl font-bold text-white">
                                Proposal list
                            </h2>
                        </div>

                        @if ($this->proposals()->count())
                            <div class="grid gap-5 lg:grid-cols-2">
                                @foreach ($this->proposals() as $proposal)
                                    <div wire:key="proposal-card-{{ $proposal->id }}"
                                        class="group rounded-[26px] border border-white/10 bg-white/7 p-5 shadow-[0_14px_40px_rgba(0,0,0,0.16)] transition hover:-translate-y-1 hover:bg-white/10">

                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex min-w-0 items-start gap-4">
                                                <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                                    <span class="material-symbols-outlined">request_quote</span>
                                                </div>

                                                <div class="min-w-0">
                                                    <h3 class="truncate text-lg font-bold text-white">
                                                        {{ $proposal->subject ?: 'Service Proposal' }}
                                                    </h3>

                                                    <p class="mt-1 font-mono text-xs text-blue-100/45">
                                                        {{ $proposal->proposal_no }}
                                                    </p>

                                                    @if ($proposal->company_name)
                                                        <p class="mt-2 text-sm text-blue-100/60">
                                                            {{ $proposal->company_name }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold {{ $this->statusClass($proposal->status) }}">
                                                {{ ucfirst($proposal->status) }}
                                            </span>
                                        </div>

                                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Items</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ $proposal->items->count() }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Total</p>
                                                <p class="mt-1 text-sm font-bold text-cyan-100">
                                                    ৳{{ number_format($proposal->total(), 2) }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Sent At</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ $this->formatDate($proposal->sent_at ?? $proposal->created_at) }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Valid Until</p>
                                                <p class="mt-1 text-sm font-bold text-white">
                                                    {{ $this->formatDate($proposal->valid_until) }}
                                                </p>
                                            </div>
                                        </div>

                                        @if ($proposal->note)
                                            <div class="mt-4 rounded-2xl border border-white/10 bg-white/6 p-4">
                                                <p class="text-xs text-blue-100/40">Note</p>
                                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-blue-50/80">
                                                    {{ $proposal->note }}
                                                </p>
                                            </div>
                                        @endif

                                        <div class="mt-5 flex flex-wrap items-center gap-3">
                                            <a href="{{ route('client.proposals.show', $proposal) }}"
                                                wire:navigate
                                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-cyan-400 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                                View Proposal
                                            </a>

                                            @if ($proposal->status === 'sent')
                                                <button
                                                    type="button"
                                                    wire:click="acceptProposal({{ $proposal->id }})"
                                                    wire:confirm="Are you sure you want to accept this proposal?"
                                                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-4 py-3 text-sm font-bold text-emerald-100 transition hover:bg-emerald-400/15">
                                                    <span class="material-symbols-outlined text-lg">check_circle</span>
                                                    Accept
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click="rejectProposal({{ $proposal->id }})"
                                                    wire:confirm="Are you sure you want to reject this proposal?"
                                                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-300/20 bg-red-400/10 px-4 py-3 text-sm font-bold text-red-100 transition hover:bg-red-400/15">
                                                    <span class="material-symbols-outlined text-lg">block</span>
                                                    Reject
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $this->proposals()->links() }}
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center rounded-[26px] border border-dashed border-white/15 bg-white/5 px-6 py-14 text-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-3xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                    <span class="material-symbols-outlined text-4xl">request_quote</span>
                                </div>

                                <h3 class="mt-5 text-xl font-bold text-white">
                                    No proposals found
                                </h3>

                                <p class="mt-2 max-w-md text-sm leading-7 text-blue-100/55">
                                    Proposals sent by admin will appear here.
                                </p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>