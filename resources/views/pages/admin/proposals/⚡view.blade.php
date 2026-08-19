<?php

use App\Events\ProposalCommentAdded;
use App\Models\Proposal;
use App\Models\ProposalComment;
use App\Models\ProposalTemplate;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Proposal Details')] class extends Component {
    public Proposal $proposal;

    public string $comment = '';

    public string $activeTab = 'summary';

    public function mount(Proposal $proposal): void
    {
        $this->proposal = $proposal->load(['items', 'comments']);

        if (request()->query('comment')) {
            $this->activeTab = 'comment';
        }

        ProposalComment::query()
            ->where('proposal_id', $this->proposal->id)
            ->whereNull('admin_read_at')
            ->update(['admin_read_at' => now()]);

        $this->proposal->update(['admin_read_at' => now()]);
    }

    #[On('echo-private:admin.proposals,.proposal.status.changed')]
    public function refreshProposalFromStatusBroadcast(array $payload = []): void
    {
        $proposalId = $payload['proposal_id'] ?? null;

        if ($proposalId && (int) $proposalId !== (int) $this->proposal->id) {
            return;
        }

        $this->proposal->refresh()->load(['items', 'comments']);

        $status = ucfirst($payload['status'] ?? 'updated');

        $this->dispatch('toast', message: "Proposal status updated to {$status}.", type: 'info');
    }

    #[On('echo-private:admin.proposals,.proposal.comment.added')]
    public function refreshCommentsFromBroadcast(array $payload = []): void
    {
        $proposalId = $payload['proposal_id'] ?? null;

        if ($proposalId && (int) $proposalId !== (int) $this->proposal->id) {
            return;
        }

        $this->proposal->load('comments');

        if (($payload['author'] ?? null) === 'admin') {
            return;
        }

        $subject = $payload['subject'] ?? 'this proposal';

        $this->dispatch('toast', message: "A client added a comment on \"{$subject}\".", type: 'info');
    }

    public function proposalHtml(): string
    {
        $template = ProposalTemplate::activeTemplate();
        $settings = SiteSetting::current();

        return view('pdf.proposal-invoice', [
            'proposal' => $this->proposal,
            'template' => $template,
            'settings' => $settings,
        ])->render();
    }

    public function saveComment(): void
    {
        $this->validate([
            'comment' => ['required', 'string'],
        ]);

        $comment = ProposalComment::create([
            'proposal_id' => $this->proposal->id,
            'author' => 'admin',
            'body' => $this->comment,
            'admin_read_at' => now(),
        ]);

        $this->proposal->refresh()->load(['items', 'comments']);

        ProposalCommentAdded::dispatch($comment);

        $this->comment = '';

        $this->dispatch('toast', message: 'Comment added.', type: 'success');
    }

    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('d M Y');
    }
};
?>

<div class="mx-auto w-full max-w-7xl space-y-stack-lg">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    {{ $proposal->subject ?: 'Service Proposal' }}
                </h2>

                <p class="font-mono text-xs text-slate-400">
                    {{ $proposal->proposal_no }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span @class([ 'inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider' , 'bg-slate-100 text-slate-600'=> $proposal->status === 'draft',
                'bg-blue-100 text-blue-700' => $proposal->status === 'sent',
                'bg-emerald-100 text-emerald-700' => $proposal->status === 'accepted',
                'bg-red-100 text-red-700' => $proposal->status === 'rejected',
                ])>
                {{ ucfirst($proposal->status) }}
            </span>

            <a href="{{ route('admin.proposals.pdf', $proposal) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20">
                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                Download PDF
            </a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <iframe
                srcdoc="{{ $this->proposalHtml() }}"
                title="Proposal preview"
                class="h-[80vh] w-full bg-white"></iframe>
        </div>

        <div class="space-y-6">
            {{-- Summary / Comment Tabs --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="flex gap-1 border-b border-slate-200 bg-slate-50/50 p-2">
                    <button type="button" wire:click="$set('activeTab', 'summary')"
                        @class([ 'flex-1 cursor-pointer rounded-lg px-4 py-2 text-label-md font-label-md transition-colors' , 'bg-white text-primary shadow-sm'=> $activeTab === 'summary',
                        'text-secondary hover:text-on-surface' => $activeTab !== 'summary',
                        ])>
                        Summary
                    </button>

                    <button type="button" wire:click="$set('activeTab', 'comment')"
                        @class([ 'flex-1 cursor-pointer rounded-lg px-4 py-2 text-label-md font-label-md transition-colors' , 'bg-white text-primary shadow-sm'=> $activeTab === 'comment',
                        'text-secondary hover:text-on-surface' => $activeTab !== 'comment',
                        ])>
                        Comment
                    </button>
                </div>

                @if ($activeTab === 'summary')
                <div class="p-6">
                    <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                        Proposal total
                    </h3>

                    <div class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-secondary">Subtotal</span>
                            <span class="font-semibold text-on-surface">
                                ৳{{ number_format($proposal->subtotal(), 2) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-secondary">Discount</span>
                            <span class="font-semibold text-amber-600">
                                - ৳{{ number_format($proposal->discountAmount(), 2) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-base">
                            <span class="font-bold text-on-surface">Total</span>
                            <span class="font-bold text-primary">
                                ৳{{ number_format($proposal->total(), 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-secondary">Sent At</span>
                            <span class="font-medium text-on-surface">
                                {{ $this->formatDate($proposal->sent_at) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-secondary">Valid Until</span>
                            <span class="font-medium text-on-surface">
                                {{ $this->formatDate($proposal->valid_until) }}
                            </span>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-6">
                    <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                        Admin comment
                    </h3>

                    <textarea wire:model.live="comment" rows="2"
                        class="mt-3 w-full rounded-lg border border-outline-variant bg-white px-4 py-2 text-label-md font-label-md text-on-surface outline-none transition placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10"
                        placeholder="Add a comment for the customer..."></textarea>

                    <button type="button" wire:click="saveComment"
                        class="mt-2 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-lg">comment</span>
                        Add Comment
                    </button>

                    <div class="mt-4 space-y-3">
                        @forelse ($proposal->comments as $comment)
                        <div @class([ 'rounded-xl border p-3' , 'border-primary/20 bg-primary/5'=> $comment->author === 'admin',
                            'border-amber-200 bg-amber-50' => $comment->author === 'customer',
                            ])>
                            <div class="flex items-center justify-between gap-3">
                                <p @class([ 'text-label-sm font-label-sm uppercase tracking-wider' , 'text-primary'=> $comment->author === 'admin',
                                    'text-amber-700' => $comment->author === 'customer',
                                    ])>
                                    {{ $comment->author === 'admin' ? 'You' : 'Customer' }}
                                </p>

                                <p class="text-[10px] text-slate-400">
                                    {{ $comment->created_at?->format('d M, g:i A') }}
                                </p>
                            </div>

                            <p @class([ 'mt-1.5 text-sm leading-5' , 'text-on-surface'=> $comment->author === 'admin',
                                'text-amber-900' => $comment->author === 'customer',
                                ])>
                                {{ $comment->body }}
                            </p>
                        </div>
                        @empty
                        <p class="text-sm text-secondary">
                            No comments yet. Start the conversation above.
                        </p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

            {{-- Customer card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                    Customer
                </h3>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Name</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $proposal->customer_name ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Email</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $proposal->customer_email ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-secondary">Phone</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $proposal->customer_phone ?: 'N/A' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-secondary">Company</span>
                        <span class="mt-1 block font-medium text-on-surface">
                            {{ $proposal->company_name ?: 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>