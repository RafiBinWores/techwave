<?php

use App\Models\ServiceBooking;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Service Bookings')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 12;

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

    public function bookings()
    {
        $search = trim($this->search);

        return ServiceBooking::query()
            ->with('service')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%')
                        ->orWhereHas('service', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('card_title', 'like', '%' . $search . '%')
                                ->orWhere('detail_title', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function markAsPending(int $bookingId): void
    {
        ServiceBooking::findOrFail($bookingId)->update([
            'status' => 'pending',
        ]);

        $this->dispatch('toast', message: 'Booking marked as pending.', type: 'success');
    }

    public function markAsContacted(int $bookingId): void
    {
        ServiceBooking::findOrFail($bookingId)->update([
            'status' => 'contacted',
        ]);

        $this->dispatch('toast', message: 'Booking marked as contacted.', type: 'success');
    }

    public function markAsCompleted(int $bookingId): void
    {
        ServiceBooking::findOrFail($bookingId)->update([
            'status' => 'completed',
        ]);

        $this->dispatch('toast', message: 'Booking marked as completed.', type: 'success');
    }

    public function markAsCancelled(int $bookingId): void
    {
        ServiceBooking::findOrFail($bookingId)->update([
            'status' => 'cancelled',
        ]);

        $this->dispatch('toast', message: 'Booking marked as cancelled.', type: 'success');
    }

    public function delete(int $bookingId): void
    {
        ServiceBooking::findOrFail($bookingId)->delete();

        $this->dispatch('toast', message: 'Booking deleted successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Service Bookings
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage customer service booking requests, inquiry status, contact details, and service requirements.
                </p>
            </div>

            <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Search booking..."
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10"
                        />
                    </div>

                    <div class="relative">
                        <select
                            wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10"
                        >
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="contacted">Contacted</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
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
                                Customer
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Service
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Company
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Message
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Submitted At
                            </th>

                            <th class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->bookings() as $booking)
                            <tr wire:key="service-booking-{{ $booking->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $booking->full_name }}
                                        </span>

                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->phone) }}"
                                            class="block text-xs text-slate-400 transition hover:text-primary">
                                            {{ $booking->phone }}
                                        </a>

                                        @if ($booking->email)
                                            <a href="mailto:{{ $booking->email }}"
                                                class="mt-1 block text-xs text-secondary transition hover:text-primary">
                                                {{ $booking->email }}
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $booking->service?->card_title ?? 'General Inquiry' }}
                                    </span>

                                    @if ($booking->service?->category)
                                        <span class="block text-xs capitalize text-secondary">
                                            {{ $booking->service->category->name }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $booking->company_name ?: 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="max-w-xs text-body-sm leading-6 text-secondary">
                                        {{ $booking->message ? Str::limit($booking->message, 90) : 'No message provided' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-slate-100 text-slate-600' => $booking->status === 'pending',
                                        'bg-blue-100 text-blue-700' => $booking->status === 'contacted',
                                        'bg-emerald-100 text-emerald-700' => $booking->status === 'completed',
                                        'bg-red-100 text-red-700' => $booking->status === 'cancelled',
                                    ])>
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-body-sm text-secondary">
                                    {{ $booking->created_at?->format('M d, Y h:i A') }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary"
                                        >
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            @click.outside="open = false"
                                            x-transition
                                            class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                                        >
                                            @if ($booking->status !== 'pending')
                                                <button
                                                    type="button"
                                                    wire:click="markAsPending({{ $booking->id }})"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">pending</span>
                                                    Mark Pending
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'contacted')
                                                <button
                                                    type="button"
                                                    wire:click="markAsContacted({{ $booking->id }})"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">call</span>
                                                    Mark Contacted
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'completed')
                                                <button
                                                    type="button"
                                                    wire:click="markAsCompleted({{ $booking->id }})"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">task_alt</span>
                                                    Mark Completed
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'cancelled')
                                                <button
                                                    type="button"
                                                    wire:click="markAsCancelled({{ $booking->id }})"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                                                    Mark Cancelled
                                                </button>
                                            @endif

                                            @if ($booking->phone)
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->phone) }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">phone</span>
                                                    Call Customer
                                                </a>
                                            @endif

                                            @if ($booking->email)
                                                <a href="mailto:{{ $booking->email }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>
                                                    Send Email
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="delete({{ $booking->id }})"
                                                wire:confirm="Are you sure you want to delete this booking?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 cursor-pointer"
                                            >
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
                                            No bookings found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Customer service booking requests will appear here.
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
                    {{ $this->bookings()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>