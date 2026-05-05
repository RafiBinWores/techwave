<?php

use App\Mail\ProposalInvoiceMail;
use App\Models\Proposal;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Proposals')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function proposals()
    {
        $search = trim($this->search);

        return Proposal::query()
            ->with('items')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('proposal_no', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('subject', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function markAsSent(int $proposalId): void
    {
        $proposal = Proposal::with('items')->findOrFail($proposalId);

        $proposal->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $proposal->load('items');

        if ($proposal->customer_email) {
            Mail::to($proposal->customer_email)->send(new ProposalInvoiceMail($proposal));
        }

        // session()->flash('toast', [
        //     'type' => 'success',
        //     'message' => $proposal->customer_email ? 'Proposal invoice created and sent to customer successfully.' : 'Proposal invoice created, but customer email was empty.',
        // ]);

        $this->dispatch('toast', message: $proposal->customer_email ? 'Proposal marked as sent and email sent successfully.' : 'Proposal marked as sent.', type: 'success');
    }

    public function delete(int $proposalId): void
    {
        Proposal::findOrFail($proposalId)->delete();

        $this->dispatch('toast', message: 'Proposal deleted successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Proposals
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Create and send service proposals with discounts, custom services, and selected plans.
                </p>
            </div>

            <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search proposal..."
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>

                    <div class="relative">
                        <select wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <span
                            class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            expand_more
                        </span>
                    </div>
                </div>

                <a href="{{ route('admin.proposals.create') }}" wire:navigate
                    class="flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] sm:w-auto">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Create New
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Proposal</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Customer</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Items</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Total</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status</th>
                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->proposals() as $proposal)
                            <tr wire:key="proposal-{{ $proposal->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $proposal->subject }}
                                        </span>

                                        <span class="block font-mono text-[11px] text-slate-400">
                                            {{ $proposal->proposal_no }}
                                        </span>

                                        @if ($proposal->valid_until)
                                            <span class="mt-1 block text-xs text-secondary">
                                                Valid until {{ $proposal->valid_until->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $proposal->customer_name }}
                                    </span>

                                    @if ($proposal->company_name)
                                        <span class="block text-xs text-secondary">
                                            {{ $proposal->company_name }}
                                        </span>
                                    @endif

                                    @if ($proposal->customer_email)
                                        <span class="block text-xs text-slate-400">
                                            {{ $proposal->customer_email }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                                    {{ $proposal->items->count() }}
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-on-surface">
                                    {{ number_format($proposal->total(), 2) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-slate-100 text-slate-600' => $proposal->status === 'draft',
                                        'bg-blue-100 text-blue-700' => $proposal->status === 'sent',
                                        'bg-emerald-100 text-emerald-700' => $proposal->status === 'accepted',
                                        'bg-red-100 text-red-700' => $proposal->status === 'rejected',
                                    ])>
                                        {{ ucfirst($proposal->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                            <a href="{{ route('admin.proposals.edit', $proposal) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                                Edit
                                            </a>

                                            <button type="button" wire:click="markAsSent({{ $proposal->id }})"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 cursor-pointer">
                                                <span class="material-symbols-outlined text-[18px]">send</span>
                                                Mark Sent
                                            </button>

                                            <button type="button" wire:click="delete({{ $proposal->id }})"
                                                wire:confirm="Are you sure you want to delete this proposal?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">request_quote</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No proposals found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Create your first customer proposal.
                                        </p>

                                        <a href="{{ route('admin.proposals.create') }}" wire:navigate
                                            class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90">
                                            Create Proposal
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">Per page</span>

                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->proposals()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
