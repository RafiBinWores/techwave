<?php

use App\Events\ProposalCommentAdded;
use App\Events\ProposalStatusChanged;
use App\Models\AdminNotification;
use App\Models\Proposal;
use App\Models\ProposalComment;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Respond to Proposal')] class extends Component {
    public Proposal $proposal;

    public string $comment = '';

    public string $activeTab = 'summary';

    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal->load(['items', 'comments']);
    }

    public function saveComment(): void
    {
        $this->validate([
            'comment' => ['required', 'string'],
        ]);

        $comment = ProposalComment::create([
            'proposal_id' => $this->proposal->id,
            'author' => 'customer',
            'body' => $this->comment,
        ]);

        $this->proposal->refresh()->load(['items', 'comments']);

        ProposalCommentAdded::dispatch($comment);

        $this->comment = '';

        $this->dispatch('toast', message: 'Comment saved. Thank you!', type: 'success');
    }

    public function acceptProposal(): void
    {
        if ($this->proposal->status !== 'sent') {
            return;
        }

        $this->proposal->update([
            'status' => 'accepted',
        ]);

        if ($this->comment !== '') {
            $this->proposal->comments()->create([
                'author' => 'customer',
                'body' => $this->comment,
            ]);
        }

        AdminNotification::create([
            'type' => 'proposal_status',
            'proposal_id' => $this->proposal->id,
            'title' => 'Proposal accepted',
            'body' => $this->comment ?: 'The customer accepted this proposal.',
            'url' => route('admin.proposals.view', $this->proposal),
        ]);

        ProposalStatusChanged::dispatch($this->proposal);

        $this->proposal->refresh()->load(['items', 'comments']);

        $this->dispatch('toast', message: 'Proposal accepted. Thank you!', type: 'success');
    }

    public function rejectProposal(): void
    {
        if ($this->proposal->status !== 'sent') {
            return;
        }

        $this->proposal->update([
            'status' => 'rejected',
        ]);

        if ($this->comment !== '') {
            $this->proposal->comments()->create([
                'author' => 'customer',
                'body' => $this->comment,
            ]);
        }

        AdminNotification::create([
            'type' => 'proposal_status',
            'proposal_id' => $this->proposal->id,
            'title' => 'Proposal declined',
            'body' => $this->comment ?: 'The customer declined this proposal.',
            'url' => route('admin.proposals.view', $this->proposal),
        ]);

        ProposalStatusChanged::dispatch($this->proposal);

        $this->proposal->refresh()->load(['items', 'comments']);

        $this->dispatch('toast', message: 'Proposal declined.', type: 'success');
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

<div class="relative min-h-screen text-white">
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="min-h-[calc(100vh-3rem)] p-4 sm:p-6 lg:p-8">

                {{-- Header --}}
                <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                            Proposal
                        </p>
                        <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                            {{ $proposal->subject ?: 'Service Proposal' }}
                        </h1>
                        <p class="mt-2 font-mono text-sm text-blue-100/45">
                            {{ $proposal->proposal_no }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-semibold {{ $this->statusClass($proposal->status) }}">
                        {{ ucfirst($proposal->status) }}
                    </span>
                </div>

                <div class="grid gap-6 xl:grid-cols-[1fr_360px]">

                    {{-- Proposal Body --}}
                    <div class="space-y-6">

                        {{-- Intro / Note --}}
                        <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                            <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                Message
                            </p>

                            <p class="mt-3 text-sm leading-7 text-blue-50/80">
                                {{ $proposal->note ?: 'Please review the details of this proposal below and let us know your decision.' }}
                            </p>
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
                                            <th class="px-3 py-3 font-medium">Description</th>
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
                                                </td>

                                                <td class="px-3 py-4">
                                                    <p class="text-xs leading-5 text-blue-100/50">
                                                        {{ $item->description ?: '—' }}
                                                    </p>
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
                                                <td colspan="5" class="px-3 py-10 text-center text-blue-100/55">
                                                    No items found for this proposal.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($proposal->terms)
                                <div class="mt-6 rounded-2xl border border-white/10 bg-white/6 p-5">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Terms & Conditions
                                    </p>

                                    <p class="mt-3 text-sm leading-7 text-blue-50/80">
                                        {{ $proposal->terms }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Sidebar Summary --}}
                    <div class="space-y-6">

                        {{-- Summary / Comment Tabs --}}
                        <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                            <div class="flex gap-1 rounded-2xl border border-white/10 bg-white/6 p-1">
                                <button type="button" wire:click="$set('activeTab', 'summary')"
                                    @class([
                                        'flex-1 cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold transition',
                                        'bg-white/10 text-white shadow-sm' => $activeTab === 'summary',
                                        'text-blue-100/45 hover:text-white' => $activeTab !== 'summary',
                                    ])>
                                    Summary
                                </button>

                                <button type="button" wire:click="$set('activeTab', 'comment')"
                                    @class([
                                        'flex-1 cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold transition',
                                        'bg-white/10 text-white shadow-sm' => $activeTab === 'comment',
                                        'text-blue-100/45 hover:text-white' => $activeTab !== 'comment',
                                    ])>
                                    Comment
                                </button>
                            </div>

                            @if ($activeTab === 'summary')
                                <h2 class="mt-6 text-2xl font-bold text-white">
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
                                            wire:confirm="Are you sure you want to decline this proposal?"
                                            class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-300/20 bg-red-400/10 px-5 py-3 text-sm font-bold text-red-100 transition hover:bg-red-400/15">
                                            <span class="material-symbols-outlined text-lg">block</span>
                                            Decline Proposal
                                        </button>
                                    </div>
                                @elseif ($proposal->status === 'accepted')
                                    <div class="mt-6 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 p-5 text-sm text-emerald-100">
                                        <strong>Accepted</strong> — thank you for accepting this proposal.
                                    </div>
                                @elseif ($proposal->status === 'rejected')
                                    <div class="mt-6 rounded-2xl border border-red-300/20 bg-red-400/10 p-5 text-sm text-red-100">
                                        <strong>Declined</strong> — this proposal has been declined.
                                    </div>
                                @endif
                            @else
                                <h2 class="mt-6 text-2xl font-bold text-white">
                                    Your comment
                                </h2>

                                @if ($proposal->status === 'sent')
                                    <textarea wire:model.live="comment" rows="4"
                                        class="mt-5 w-full rounded-2xl border border-white/10 bg-white/6 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-blue-300/40 focus:ring-2 focus:ring-blue-300/10"
                                        placeholder="Add a comment or question"></textarea>

                                    <button
                                        type="button"
                                        wire:click="saveComment"
                                        class="mt-3 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-blue-300/20 bg-blue-400/10 px-5 py-3 text-sm font-bold text-blue-100 transition hover:bg-blue-400/15">
                                        <span class="material-symbols-outlined text-lg">comment</span>
                                        Save Comment
                                    </button>

                                    <p class="mt-3 text-xs leading-5 text-blue-100/45">
                                        Your comment is shared with us. You can still accept or decline the proposal
                                        anytime from the Summary tab.
                                    </p>
                                @else
                                    <p class="mt-5 text-sm leading-6 text-blue-100/55">
                                        This proposal has already been {{ $proposal->status }}.
                                    </p>
                                @endif

                                @forelse ($proposal->comments as $comment)
                                        <div @class([
                                            'mt-3 rounded-2xl border p-3',
                                            'border-white/10 bg-white/6' => $comment->author === 'customer',
                                            'border-blue-300/20 bg-blue-400/10' => $comment->author === 'admin',
                                        ])>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                                    {{ $comment->author === 'admin' ? 'Techwave' : 'You' }}
                                                </p>
                                                <p class="text-[10px] text-blue-100/35">
                                                    {{ $comment->created_at?->format('d M, g:i A') }}
                                                </p>
                                            </div>

                                            <p class="mt-1.5 text-sm leading-5 text-blue-50/80">
                                                {{ $comment->body }}
                                            </p>
                                        </div>
                                    @empty
                                        <p class="mt-4 text-xs leading-5 text-blue-100/45">
                                            No comments yet. Start the conversation above.
                                        </p>
                                    @endforelse
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