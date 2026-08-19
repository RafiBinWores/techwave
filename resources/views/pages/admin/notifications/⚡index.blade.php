<?php

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Proposal;
use App\Models\ProposalComment;
use App\Models\SupportTicket;
use App\Services\AdminNotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Notifications')] class extends Component {
    use WithPagination;

    public string $tab = 'all';

    public int $perPage = 15;

    public function notifications(): LengthAwarePaginator
    {
        $items = AdminNotificationService::notifications(
            limit: 500,
            unreadOnly: $this->tab === 'unread',
            type: in_array($this->tab, ['ticket', 'contact', 'booking', 'proposal'], true) ? $this->tab : null,
        );

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function unreadCount(): int
    {
        return AdminNotificationService::notifications(limit: 500, unreadOnly: true)->count();
    }

    public function markAllNotificationsRead(): void
    {
        SupportTicket::query()->whereNull('admin_read_at')->update(['admin_read_at' => now()]);
        ContactMessage::query()->whereNull('admin_read_at')->update(['admin_read_at' => now()]);
        Booking::query()->whereNull('admin_read_at')->update(['admin_read_at' => now()]);
        ProposalComment::query()->whereNull('admin_read_at')->update(['admin_read_at' => now()]);
        Proposal::query()
            ->whereIn('status', ['accepted', 'rejected'])
            ->whereNull('admin_read_at')
            ->update(['admin_read_at' => now()]);

        $this->dispatch('toast', message: 'All notifications marked as read.', type: 'success');
    }

    public function notificationIcon(string $type): string
    {
        return match ($type) {
            'ticket' => 'confirmation_number',
            'contact' => 'mail',
            'booking' => 'event_note',
            'proposal' => 'rate_review',
            default => 'notifications',
        };
    }

    public function notificationColor(string $type): string
    {
        return match ($type) {
            'ticket' => 'bg-blue-100 text-blue-700',
            'contact' => 'bg-emerald-100 text-emerald-700',
            'booking' => 'bg-amber-100 text-amber-700',
            'proposal' => 'bg-violet-100 text-violet-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function notificationBadgeColor(string $type): string
    {
        return match ($type) {
            'ticket' => 'bg-blue-50 text-blue-700',
            'contact' => 'bg-emerald-50 text-emerald-700',
            'booking' => 'bg-amber-50 text-amber-700',
            'proposal' => 'bg-violet-50 text-violet-700',
            default => 'bg-slate-50 text-slate-700',
        };
    }
};
?>

<div class="mx-auto w-full max-w-7xl space-y-stack-lg">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                Notifications
            </h2>

            <p class="mt-1 text-sm text-secondary">
                {{ $this->unreadCount() }} unread notification{{ $this->unreadCount() === 1 ? '' : 's' }} across your
                admin modules.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-1 rounded-lg border border-outline-variant bg-white p-1">
                @foreach ([
                    'all' => 'All',
                    'unread' => 'Unread',
                    'ticket' => 'Tickets',
                    'contact' => 'Contacts',
                    'booking' => 'Bookings',
                    'proposal' => 'Proposals',
                ] as $key => $label)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')"
                        @class([
                            'cursor-pointer rounded-md px-4 py-1.5 text-label-md font-label-md transition-colors',
                            'bg-primary text-on-primary' => $tab === $key,
                            'text-secondary hover:text-on-surface' => $tab !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($this->unreadCount() > 0)
                <button type="button" wire:click="markAllNotificationsRead"
                    class="inline-flex items-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50">
                    <span class="material-symbols-outlined text-lg">done_all</span>
                    Mark all as read
                </button>
            @endif
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($this->notifications() as $notification)
            <a href="{{ $notification['url'] }}" wire:navigate
                @class([
                    'block rounded-xl border bg-white p-4 transition hover:shadow-md',
                    'border-slate-200' => $notification['read'],
                    'border-primary/30 bg-primary/5' => ! $notification['read'],
                ])>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $this->notificationColor($notification['type']) }}">
                        <span class="material-symbols-outlined text-[20px]">
                            {{ $this->notificationIcon($notification['type']) }}
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="truncate text-sm font-semibold text-on-surface">
                                {{ $notification['title'] }}
                            </p>

                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                $this->notificationBadgeColor($notification['type']),
                                'opacity-60' => $notification['read'],
                            ])>
                                {{ $notification['type'] }}
                            </span>
                        </div>

                        <p class="mt-0.5 truncate text-sm text-on-surface">
                            {{ $notification['subject'] }}
                        </p>

                        <p class="mt-0.5 truncate text-xs text-secondary">
                            By {{ $notification['from'] }}
                        </p>

                        <div class="mt-1 flex items-center gap-2">
                            <p class="text-[11px] text-slate-400">
                                {{ $notification['time']?->diffForHumans() }}
                            </p>

                            @if (! $notification['read'])
                                <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <span class="material-symbols-outlined">notifications_off</span>
                </div>

                <h4 class="mt-4 text-base font-semibold text-on-surface">
                    No notifications here
                </h4>

                <p class="mt-1 text-sm text-secondary">
                    {{ $tab === 'unread' ? 'You are all caught up.' : 'Nothing has happened yet.' }}
                </p>
            </div>
        @endforelse
    </div>

    @if ($this->notifications()->hasPages())
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-5">
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
                {{ $this->notifications()->links() }}
            </div>
        </div>
    @endif
</div>