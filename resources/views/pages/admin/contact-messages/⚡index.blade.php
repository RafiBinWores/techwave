<?php

use App\Models\ContactMessage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Contact Messages')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;

    public int $refreshKey = 0;

    #[On('echo-private:admin.contact-messages,.contact.message.created')]
    public function refreshMessagesFromBroadcast(): void
    {
        $this->refreshKey++;

        $this->dispatch('toast', message: 'New contact message received.', type: 'info');
    }

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

    public function messages()
    {
        $search = trim($this->search);

        return ContactMessage::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('subject', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status === 'unread', function ($query) {
                $query->whereNull('admin_read_at');
            })
            ->when($this->status === 'read', function ($query) {
                $query->whereNotNull('admin_read_at');
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function markAsRead(int $messageId): void
    {
        $message = ContactMessage::findOrFail($messageId);

        $message->update([
            'admin_read_at' => now(),
        ]);

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Contact message marked as read.', type: 'success');
    }

    public function markAsUnread(int $messageId): void
    {
        $message = ContactMessage::findOrFail($messageId);

        $message->update([
            'admin_read_at' => null,
        ]);

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Contact message marked as unread.', type: 'success');
    }

    public function markAllAsRead(): void
    {
        ContactMessage::query()
            ->whereNull('admin_read_at')
            ->update([
                'admin_read_at' => now(),
            ]);

        $this->refreshKey++;

        $this->dispatch('toast', message: 'All contact messages marked as read.', type: 'success');
    }

    public function delete(int $messageId): void
    {
        $message = ContactMessage::findOrFail($messageId);

        $message->delete();

        $this->refreshKey++;

        $this->dispatch('toast', message: 'Contact message deleted successfully.', type: 'success');
    }

    public function unreadCount(): int
    {
        return ContactMessage::query()
            ->whereNull('admin_read_at')
            ->count();
    }

    public function totalCount(): int
    {
        return ContactMessage::query()->count();
    }
};
?>

<div wire:key="admin-contact-message-index-{{ $refreshKey }}">
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">

        {{-- Header --}}
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Contact Messages
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage website contact form submissions, customer inquiries, and new business leads.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[12px] font-bold uppercase tracking-wider text-slate-400">
                        Total : <span class="text-lg font-semibold text-slate-900">{{ $this->totalCount() }}</span>
                    </p>
                </div>

                <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 shadow-sm">
                    <p class="text-[12px] font-bold uppercase tracking-wider text-red-400">
                        Unread : <span class="text-lg font-bold text-red-600">{{ $this->unreadCount() }}</span>
                    </p>
                </div>

                @if ($this->unreadCount() > 0)
                    <button type="button" wire:click="markAllAsRead"
                        class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 sm:col-span-1">
                        <span class="material-symbols-outlined text-[18px]">done_all</span>
                        Mark all read
                    </button>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[1fr_220px_140px]">
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Search name, email, phone, subject, or message..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                </div>

                <div class="relative">
                    <select wire:model.live="status"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="all">All Messages</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>

                    <span
                        class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Sender
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Subject & Message
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Contact
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Submitted
                            </th>

                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->messages() as $message)
                            <tr wire:key="contact-message-{{ $message->id }}-{{ $refreshKey }}"
                                class="transition-colors hover:bg-slate-50/80">

                                {{-- Sender --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div @class([
                                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                            'bg-primary/10 text-primary' => $message->admin_read_at,
                                            'bg-red-100 text-red-600' => !$message->admin_read_at,
                                        ])>
                                            {{ strtoupper(substr($message->name ?? 'U', 0, 1)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-on-surface">
                                                {{ $message->name }}
                                            </p>

                                            <p class="truncate text-xs text-secondary">
                                                {{ $message->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Subject --}}
                                <td class="max-w-md px-6 py-4">
                                    <p class="truncate text-sm font-semibold text-on-surface">
                                        {{ $message->subject }}
                                    </p>

                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-secondary">
                                        {{ $message->message }}
                                    </p>
                                </td>

                                {{-- Contact --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <a href="mailto:{{ $message->email }}"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                            <span class="material-symbols-outlined text-[15px]">mail</span>
                                            {{ $message->email }}
                                        </a>

                                        @if ($message->phone)
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $message->phone) }}"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-secondary hover:text-primary">
                                                <span class="material-symbols-outlined">call</span>
                                                {{ $message->phone }}
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">No phone</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4">
                                    @if ($message->admin_read_at)
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-700">
                                            Read
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-red-700">
                                            Unread
                                        </span>
                                    @endif
                                </td>

                                {{-- Time --}}
                                <td class="px-6 py-4">
                                    <p class="text-sm text-secondary">
                                        {{ $message->created_at?->format('M d, Y') }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $message->created_at?->format('h:i A') }}
                                    </p>
                                </td>

                                {{-- Action --}}
                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false, preview: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                            <button type="button" @click="preview = true; open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                View Message
                                            </button>

                                            <a href="mailto:{{ $message->email }}?subject=Re: {{ rawurlencode($message->subject) }}"
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">reply</span>
                                                Reply by Email
                                            </a>

                                            @if ($message->admin_read_at)
                                                <button type="button" wire:click="markAsUnread({{ $message->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">mark_email_unread</span>
                                                    Mark Unread
                                                </button>
                                            @else
                                                <button type="button" wire:click="markAsRead({{ $message->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">mark_email_read</span>
                                                    Mark Read
                                                </button>
                                            @endif

                                            <button type="button" wire:click="delete({{ $message->id }})"
                                                wire:confirm="Are you sure you want to delete this contact message?"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>

                                        {{-- Preview Modal --}}
                                        <div x-cloak x-show="preview" x-transition.opacity
                                            class="fixed inset-0 z-9999 flex items-center justify-center bg-slate-950/50 p-4">
                                            <div @click.outside="preview = false"
                                                class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">

                                                <div
                                                    class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                                                    <div>
                                                        <p class="text-xs font-bold uppercase tracking-wider text-primary">
                                                            Contact Message
                                                        </p>

                                                        <h3 class="mt-1 text-lg font-bold text-slate-900">
                                                            {{ $message->subject }}
                                                        </h3>

                                                        <p class="mt-1 text-sm text-slate-500">
                                                            From {{ $message->name }} ·
                                                            {{ $message->created_at?->diffForHumans() }}
                                                        </p>
                                                    </div>

                                                    <button type="button" @click="preview = false"
                                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                                        <span class="material-symbols-outlined">close</span>
                                                    </button>
                                                </div>

                                                <div class="space-y-5 px-6 py-5">
                                                    <div class="grid gap-4 sm:grid-cols-2">
                                                        <div class="rounded-xl bg-slate-50 p-4">
                                                            <p class="text-xs uppercase tracking-wider text-slate-400">
                                                                Name
                                                            </p>
                                                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                                                {{ $message->name }}
                                                            </p>
                                                        </div>

                                                        <div class="rounded-xl bg-slate-50 p-4">
                                                            <p class="text-xs uppercase tracking-wider text-slate-400">
                                                                Email
                                                            </p>
                                                            <p class="mt-1 break-all text-sm font-semibold text-slate-900">
                                                                {{ $message->email }}
                                                            </p>
                                                        </div>

                                                        <div class="rounded-xl bg-slate-50 p-4">
                                                            <p class="text-xs uppercase tracking-wider text-slate-400">
                                                                Phone
                                                            </p>
                                                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                                                {{ $message->phone ?: 'N/A' }}
                                                            </p>
                                                        </div>

                                                        <div class="rounded-xl bg-slate-50 p-4">
                                                            <p class="text-xs uppercase tracking-wider text-slate-400">
                                                                Status
                                                            </p>
                                                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                                                {{ $message->admin_read_at ? 'Read' : 'Unread' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                                            Message
                                                        </p>

                                                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                                                            {{ $message->message }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                                                    @unless ($message->admin_read_at)
                                                        <button type="button"
                                                            wire:click="markAsRead({{ $message->id }})"
                                                            @click="preview = false"
                                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                                            <span class="material-symbols-outlined text-[18px]">done</span>
                                                            Mark read
                                                        </button>
                                                    @endunless

                                                    <a href="mailto:{{ $message->email }}?subject=Re: {{ rawurlencode($message->subject) }}"
                                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                                                        <span class="material-symbols-outlined text-[18px]">reply</span>
                                                        Reply by Email
                                                    </a>
                                                </div>
                                            </div>
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
                                            <span class="material-symbols-outlined">mail</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No contact messages found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Website contact form submissions will appear here.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
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
                    {{ $this->messages()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>