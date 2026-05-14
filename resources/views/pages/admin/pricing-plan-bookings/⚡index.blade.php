<?php

use App\Mail\PricingBookingQuoteMail;
use App\Models\PricingPlanBooking;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Pricing Bookings')] class extends Component {
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

    public function bookings()
    {
        $search = trim($this->search);

        return PricingPlanBooking::query()
            ->with(['user', 'pricingPlan', 'pricingOrder'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_no', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('company_email', 'like', '%' . $search . '%')
                        ->orWhereHas('pricingPlan', function ($planQuery) use ($search) {
                            $planQuery->where('title', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function markAsRead(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'admin_read_at' => now(),
        ]);

        $this->dispatch('toast', message: 'Booking marked as read.', type: 'success');
    }

    public function markAsUnread(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'admin_read_at' => null,
        ]);

        $this->dispatch('toast', message: 'Booking marked as unread.', type: 'success');
    }

    public function updateStatus(int $bookingId, string $status): void
    {
        if (! in_array($status, ['pending', 'contacted', 'quoted', 'accepted', 'rejected', 'converted', 'cancelled'], true)) {
            return;
        }

        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => $status,
            'admin_read_at' => now(),
        ]);

        $this->dispatch('toast', message: 'Booking status updated successfully.', type: 'success');
    }

    public function sendQuoteMail(int $bookingId): void
    {
        $booking = PricingPlanBooking::query()
            ->with(['user', 'pricingPlan', 'pricingOrder'])
            ->findOrFail($bookingId);

        if (! $booking->company_email) {
            $this->dispatch('toast', message: 'Company email not found.', type: 'error');
            return;
        }

        if (! $booking->quoted_price) {
            $this->dispatch('toast', message: 'Please add quoted amount before sending email.', type: 'warning');
            return;
        }

        $booking->update([
            'status' => 'quoted',
            'admin_read_at' => now(),
        ]);

        Mail::to($booking->company_email)->queue(new PricingBookingQuoteMail($booking->fresh(['user', 'pricingPlan', 'pricingOrder'])));

        $this->dispatch('toast', message: 'Quotation email queued successfully.', type: 'success');
    }

    public function delete(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->delete();

        $this->dispatch('toast', message: 'Booking deleted successfully.', type: 'success');
    }

    public function unreadCount(): int
    {
        return PricingPlanBooking::query()
            ->whereNull('admin_read_at')
            ->count();
    }

    public function totalCount(): int
    {
        return PricingPlanBooking::query()->count();
    }

    public function pendingCount(): int
    {
        return PricingPlanBooking::query()
            ->where('status', 'pending')
            ->count();
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Pricing Bookings
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage yearly plan booking requests, negotiation, quotation, and customer follow-up.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $this->totalCount() }}</p>
                </div>

                <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Pending</p>
                    <p class="mt-1 text-lg font-bold text-amber-700">{{ $this->pendingCount() }}</p>
                </div>

                <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-red-400">Unread</p>
                    <p class="mt-1 text-lg font-bold text-red-600">{{ $this->unreadCount() }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[1fr_220px_140px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Search booking no, customer, company, email, plan..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                </div>

                <div class="relative">
                    <select wire:model.live="status"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="quoted">Quoted</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="converted">Converted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>

                <div class="relative">
                    <select wire:model.live="perPage"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="10">10 / page</option>
                        <option value="15">15 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Booking</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Customer</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Plan</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Price</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Date</th>
                            <th class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->bookings() as $booking)
                            <tr wire:key="pricing-booking-{{ $booking->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        @if (! $booking->admin_read_at)
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-red-500"></span>
                                        @else
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                        @endif

                                        <div>
                                            <a href="{{ route('admin.pricing-plan-bookings.show', $booking) }}" wire:navigate
                                                class="block text-sm font-bold text-primary hover:underline">
                                                {{ $booking->booking_no }}
                                            </a>

                                            <p class="mt-1 text-xs text-secondary capitalize">
                                                {{ $booking->billing_cycle }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-on-surface">
                                        {{ $booking->customer_name ?? $booking->user?->name ?? 'N/A' }}
                                    </p>

                                    <p class="mt-1 text-xs text-secondary">
                                        {{ $booking->customer_email ?? $booking->user?->email ?? 'No email' }}
                                    </p>

                                    @if ($booking->company_name)
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $booking->company_name }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-on-surface">
                                        {{ $booking->pricingPlan?->title ?? 'N/A' }}
                                    </p>

                                    <p class="mt-1 text-xs text-secondary">
                                        {{ $booking->pricingPlan?->plan_type ?? 'Plan' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-slate-900">
                                        ৳ {{ number_format((float) $booking->plan_price, 2) }}
                                    </p>

                                    <p class="mt-1 text-xs text-amber-600">
                                        Requested:
                                        {{ $booking->requested_price ? '৳ ' . number_format((float) $booking->requested_price, 2) : 'N/A' }}
                                    </p>

                                    <p class="mt-1 text-xs text-emerald-600">
                                        Quoted:
                                        {{ $booking->quoted_price ? '৳ ' . number_format((float) $booking->quoted_price, 2) : 'N/A' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                        'bg-amber-100 text-amber-700' => $booking->status === 'pending',
                                        'bg-blue-100 text-blue-700' => $booking->status === 'contacted',
                                        'bg-purple-100 text-purple-700' => $booking->status === 'quoted',
                                        'bg-emerald-100 text-emerald-700' => $booking->status === 'accepted',
                                        'bg-red-100 text-red-700' => in_array($booking->status, ['rejected', 'cancelled'], true),
                                        'bg-green-100 text-green-700' => $booking->status === 'converted',
                                        'bg-slate-100 text-slate-600' => ! in_array($booking->status, ['pending', 'contacted', 'quoted', 'accepted', 'rejected', 'cancelled', 'converted'], true),
                                    ])>
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm text-secondary">
                                        {{ $booking->created_at?->format('M d, Y') }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $booking->created_at?->format('h:i A') }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                            <a href="{{ route('admin.pricing-plan-bookings.show', $booking) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                View Details
                                            </a>

                                            <button type="button" wire:click="sendQuoteMail({{ $booking->id }})"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>
                                                Send Quote Email
                                            </button>

                                            @if ($booking->admin_read_at)
                                                <button type="button" wire:click="markAsUnread({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">mark_email_unread</span>
                                                    Mark Unread
                                                </button>
                                            @else
                                                <button type="button" wire:click="markAsRead({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">mark_email_read</span>
                                                    Mark Read
                                                </button>
                                            @endif

                                            <div class="my-1 border-t border-slate-100"></div>

                                            <button type="button" wire:click="updateStatus({{ $booking->id }}, 'contacted')"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">support_agent</span>
                                                Mark Contacted
                                            </button>

                                            <button type="button" wire:click="updateStatus({{ $booking->id }}, 'cancelled')"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">cancel</span>
                                                Cancel Booking
                                            </button>

                                            <button type="button" wire:click="delete({{ $booking->id }})"
                                                wire:confirm="Are you sure you want to delete this booking?"
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
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">event_note</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No pricing bookings found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Yearly plan booking requests will appear here.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
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