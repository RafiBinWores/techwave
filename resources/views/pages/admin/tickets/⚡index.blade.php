<?php

use App\Events\SupportTicketUpdated;
use App\Models\SupportTicket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Support Tickets')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $priority = 'all';
    public int $perPage = 10;

    public int $refreshKey = 0;

    #[On('echo-private:admin.tickets,.ticket.updated')]
    public function refreshTicketsFromBroadcast(): void
    {
        $this->refreshKey++;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function tickets()
    {
        $search = trim($this->search);

        return SupportTicket::query()
            ->with(['user'])
            ->withCount(['replies', 'attachments'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_no', 'like', '%' . $search . '%')
                        ->orWhere('subject', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('department', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->priority !== 'all', function ($query) {
                $query->where('priority', $this->priority);
            })
            ->orderByRaw('CASE WHEN last_reply_at IS NULL THEN 1 ELSE 0 END')
            ->latest('last_reply_at')
            ->latest()
            ->paginate($this->perPage);
    }

    public function closeTicket(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
            'client_read_at' => null,
        ]);

        SupportTicketUpdated::dispatch($ticket, 'closed');

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Ticket closed successfully.', type: 'success');
    }

    public function reopenTicket(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);

        $ticket->update([
            'status' => 'open',
            'closed_at' => null,
            'client_read_at' => null,
        ]);

        SupportTicketUpdated::dispatch($ticket, 'reopened');

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Ticket reopened successfully.', type: 'success');
    }

    public function markAsPending(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);

        $ticket->update([
            'status' => 'pending',
            'closed_at' => null,
            'client_read_at' => null,
        ]);

        SupportTicketUpdated::dispatch($ticket, 'pending');

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Ticket marked as pending.', type: 'success');
    }

    public function delete(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);

        $ticket->delete();

        SupportTicketUpdated::dispatch($ticket, 'deleted');

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Ticket deleted successfully.', type: 'success');
    }
};
?>

<div wire:key="admin-ticket-index-{{ $refreshKey }}">
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Support Tickets
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage customer support requests, priorities, replies, images, and ticket statuses.
                </p>
            </div>

            <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 lg:max-w-3xl">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search ticket..."
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>

                    <div class="relative">
                        <select wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Status</option>
                            <option value="open">Open</option>
                            <option value="pending">Pending</option>
                            <option value="answered">Answered</option>
                            <option value="closed">Closed</option>
                        </select>

                        <span
                            class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            expand_more
                        </span>
                    </div>

                    <div class="relative">
                        <select wire:model.live="priority"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>

                        <span
                            class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            expand_more
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Ticket
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Customer
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Department
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Priority
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Activity
                            </th>

                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->tickets() as $ticket)
                            <tr wire:key="support-ticket-{{ $ticket->id }}-{{ $ticket->status }}-{{ $ticket->replies_count }}-{{ $refreshKey }}"
                                class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $ticket->subject }}
                                        </span>

                                        <span class="block font-mono text-[11px] text-slate-400">
                                            {{ $ticket->ticket_no }}
                                        </span>

                                        <span class="mt-1 block text-xs text-secondary">
                                            Created {{ $ticket->created_at?->format('M d, Y h:i A') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $ticket->customer_name ?? ($ticket->user?->name ?? 'Guest Customer') }}
                                    </span>

                                    @if ($ticket->customer_email ?? $ticket->user?->email)
                                        <span class="block text-xs text-slate-400">
                                            {{ $ticket->customer_email ?? $ticket->user?->email }}
                                        </span>
                                    @endif

                                    @if ($ticket->customer_phone)
                                        <span class="block text-xs text-secondary">
                                            {{ $ticket->customer_phone }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-body-sm text-secondary">
                                    {{ $ticket->department }}
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-slate-100 text-slate-600' => $ticket->priority === 'low',
                                        'bg-blue-100 text-blue-700' => $ticket->priority === 'medium',
                                        'bg-orange-100 text-orange-700' => $ticket->priority === 'high',
                                        'bg-red-100 text-red-700' => $ticket->priority === 'urgent',
                                    ])>
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-blue-100 text-blue-700' => $ticket->status === 'open',
                                        'bg-amber-100 text-amber-700' => $ticket->status === 'pending',
                                        'bg-emerald-100 text-emerald-700' => $ticket->status === 'answered',
                                        'bg-slate-100 text-slate-600' => $ticket->status === 'closed',
                                    ])>
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center gap-1 text-xs text-secondary">
                                            <span class="material-symbols-outlined text-[16px]">chat_bubble</span>
                                            {{ $ticket->replies_count }} Replies
                                        </span>

                                        <span class="inline-flex items-center gap-1 text-xs text-secondary">
                                            <span class="material-symbols-outlined text-[16px]">image</span>
                                            {{ $ticket->attachments_count }} Images
                                        </span>

                                        @if ($ticket->last_reply_at)
                                            <span class="text-[11px] text-slate-400">
                                                Last reply {{ $ticket->last_reply_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-slate-400">
                                                No reply yet
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                            <a href="{{ route('admin.tickets.show', $ticket) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                View & Reply
                                            </a>

                                            @if ($ticket->status !== 'pending' && $ticket->status !== 'closed')
                                                <button type="button" wire:click="markAsPending({{ $ticket->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">pending</span>
                                                    Mark Pending
                                                </button>
                                            @endif

                                            @if ($ticket->status !== 'closed')
                                                <button type="button" wire:click="closeTicket({{ $ticket->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">lock</span>
                                                    Close Ticket
                                                </button>
                                            @else
                                                <button type="button" wire:click="reopenTicket({{ $ticket->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">lock_open</span>
                                                    Reopen Ticket
                                                </button>
                                            @endif

                                            <button type="button" wire:click="delete({{ $ticket->id }})"
                                                wire:confirm="Are you sure you want to delete this ticket?"
                                                @click="open = false"
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
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">support_agent</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No support tickets found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Customer support tickets will appear here.
                                        </p>
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
                    {{ $this->tickets()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
