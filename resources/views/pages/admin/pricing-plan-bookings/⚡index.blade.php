<?php

use App\Models\PricingPlanBooking;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Pricing Plan Bookings')] class extends Component {
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
            ->with(['user', 'pricingPlan'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_no', 'like', '%' . $search . '%')
                        ->orWhere('billing_cycle', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_phone', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('company_email', 'like', '%' . $search . '%')
                        ->orWhere('company_phone', 'like', '%' . $search . '%')
                        ->orWhere('user_note', 'like', '%' . $search . '%')
                        ->orWhere('admin_note', 'like', '%' . $search . '%')
                        ->orWhereHas('pricingPlan', function ($planQuery) use ($search) {
                            $planQuery
                                ->where('title', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
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
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'pending',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as pending.', type: 'success');
    }

    public function markAsReviewing(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'reviewing',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as reviewing.', type: 'success');
    }

    public function markAsQuoted(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'quoted',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as quoted.', type: 'success');
    }

    public function markAsAccepted(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'accepted',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as accepted.', type: 'success');
    }

    public function markAsConverted(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'converted',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as converted.', type: 'success');
    }

    public function markAsRejected(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->update([
            'status' => 'rejected',
        ]);

        $this->dispatch('toast', message: 'Plan booking marked as rejected.', type: 'success');
    }

    public function delete(int $bookingId): void
    {
        PricingPlanBooking::findOrFail($bookingId)->delete();

        $this->dispatch('toast', message: 'Plan booking deleted successfully.', type: 'success');
    }

    public function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            'pending' => 'bg-amber-100 text-amber-700',
            'reviewing' => 'bg-cyan-100 text-cyan-700',
            'quoted' => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'converted' => 'bg-purple-100 text-purple-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Pricing Plan Bookings
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage yearly plan booking requests, customer details, company information, requested price, quotation status, and admin follow-up.
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
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>

                    <div class="relative">
                        <select
                            wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="reviewing">Reviewing</option>
                            <option value="quoted">Quoted</option>
                            <option value="accepted">Accepted</option>
                            <option value="converted">Converted</option>
                            <option value="rejected">Rejected</option>
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
                                Plan
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Company
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Price
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Requirement
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
                            <tr wire:key="pricing-plan-booking-{{ $booking->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $booking->customer_name ?? $booking->user?->name ?? 'N/A' }}
                                        </span>

                                        @if ($booking->customer_phone)
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer_phone) }}"
                                                class="block text-xs text-slate-400 transition hover:text-primary">
                                                {{ $booking->customer_phone }}
                                            </a>
                                        @endif

                                        @if ($booking->customer_email)
                                            <a href="mailto:{{ $booking->customer_email }}"
                                                class="mt-1 block text-xs text-secondary transition hover:text-primary">
                                                {{ $booking->customer_email }}
                                            </a>
                                        @endif

                                        @if ($booking->booking_no)
                                            <span class="mt-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                                {{ $booking->booking_no }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $booking->pricingPlan?->title ?? 'Pricing Plan' }}
                                    </span>

                                    <span class="block text-xs capitalize text-secondary">
                                        {{ ucfirst($booking->billing_cycle ?? 'yearly') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block text-body-sm text-on-surface">
                                        {{ $booking->company_name ?: 'N/A' }}
                                    </span>

                                    @if ($booking->company_phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->company_phone) }}"
                                            class="mt-1 block text-xs text-slate-400 transition hover:text-primary">
                                            {{ $booking->company_phone }}
                                        </a>
                                    @endif

                                    @if ($booking->company_email)
                                        <a href="mailto:{{ $booking->company_email }}"
                                            class="mt-1 block text-xs text-secondary transition hover:text-primary">
                                            {{ $booking->company_email }}
                                        </a>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <p class="text-body-sm text-on-surface">
                                            Listed:
                                            <span class="font-semibold">
                                                ৳{{ number_format((float) ($booking->plan_price ?? 0), 2) }}
                                            </span>
                                        </p>

                                        <p class="text-xs text-secondary">
                                            Requested:
                                            <span class="font-semibold">
                                                @if ($booking->requested_price)
                                                    ৳{{ number_format((float) $booking->requested_price, 2) }}
                                                @else
                                                    N/A
                                                @endif
                                            </span>
                                        </p>

                                        <p class="text-xs text-secondary">
                                            Quoted:
                                            <span class="font-semibold">
                                                @if ($booking->quoted_price)
                                                    ৳{{ number_format((float) $booking->quoted_price, 2) }}
                                                @else
                                                    Not quoted
                                                @endif
                                            </span>
                                        </p>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="max-w-xs text-body-sm leading-6 text-secondary">
                                        {{ $booking->user_note ? Str::limit($booking->user_note, 90) : 'No requirement provided' }}
                                    </p>

                                    @if ($booking->admin_note)
                                        <p class="mt-2 max-w-xs rounded-lg bg-cyan-50 px-3 py-2 text-xs leading-5 text-cyan-700">
                                            Admin: {{ Str::limit($booking->admin_note, 80) }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $this->statusBadgeClass($booking->status) }}">
                                        {{ ucfirst($booking->status ?? 'pending') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-body-sm text-secondary">
                                    {{ $booking->created_at?->format('M d, Y h:i A') }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                            @if ($booking->status !== 'pending')
                                                <button type="button" wire:click="markAsPending({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">pending</span>
                                                    Mark Pending
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'reviewing')
                                                <button type="button" wire:click="markAsReviewing({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">manage_search</span>
                                                    Mark Reviewing
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'quoted')
                                                <button type="button" wire:click="markAsQuoted({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">request_quote</span>
                                                    Mark Quoted
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'accepted')
                                                <button type="button" wire:click="markAsAccepted({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                                    Mark Accepted
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'converted')
                                                <button type="button" wire:click="markAsConverted({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">published_with_changes</span>
                                                    Mark Converted
                                                </button>
                                            @endif

                                            @if ($booking->status !== 'rejected')
                                                <button type="button" wire:click="markAsRejected({{ $booking->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                                                    Mark Rejected
                                                </button>
                                            @endif

                                            <div class="my-1 border-t border-slate-100"></div>

                                            @if ($booking->customer_phone)
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer_phone) }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">phone</span>
                                                    Call Customer
                                                </a>
                                            @endif

                                            @if ($booking->customer_email)
                                                <a href="mailto:{{ $booking->customer_email }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>
                                                    Email Customer
                                                </a>
                                            @endif

                                            @if ($booking->company_email)
                                                <a href="mailto:{{ $booking->company_email }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">business_messages</span>
                                                    Email Company
                                                </a>
                                            @endif

                                            <div class="my-1 border-t border-slate-100"></div>

                                            <button type="button" wire:click="delete({{ $booking->id }})"
                                                wire:confirm="Are you sure you want to delete this plan booking?"
                                                @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">contract</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No plan bookings found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Customer yearly pricing plan booking requests will appear here.
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