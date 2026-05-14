<?php

use App\Models\PricingOrder;
use App\Models\PricingPlanBooking;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Pricing Booking Details')] class extends Component {
    public PricingPlanBooking $booking;

    public string $status = 'pending';
    public string $quoted_price = '';
    public string $admin_note = '';

    public function mount(PricingPlanBooking $booking): void
    {
        $this->booking = $booking->load(['user', 'pricingPlan', 'pricingOrder']);

        if (is_null($this->booking->admin_read_at)) {
            $this->booking->update([
                'admin_read_at' => now(),
            ]);
        }

        $this->status = $this->booking->status ?: 'pending';
        $this->quoted_price = $this->booking->quoted_price !== null ? (string) $this->booking->quoted_price : '';
        $this->admin_note = $this->booking->admin_note ?: '';
    }

    protected function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,contacted,quoted,accepted,rejected,converted,cancelled'],
            'quoted_price' => ['nullable', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function saveQuote(): void
    {
        $validated = $this->validate();

        $this->booking->update([
            'status' => $validated['status'],
            'quoted_price' => $validated['quoted_price'] !== '' ? $validated['quoted_price'] : null,
            'admin_note' => $validated['admin_note'] ?: null,
            'admin_read_at' => now(),
        ]);

        $this->booking = $this->booking->fresh(['user', 'pricingPlan', 'pricingOrder']);

        $this->dispatch('toast', message: 'Booking quote updated successfully.', type: 'success');
    }

    public function markAsContacted(): void
    {
        $this->booking->update([
            'status' => 'contacted',
            'admin_read_at' => now(),
        ]);

        $this->status = 'contacted';
        $this->booking = $this->booking->fresh(['user', 'pricingPlan', 'pricingOrder']);

        $this->dispatch('toast', message: 'Booking marked as contacted.', type: 'success');
    }

    public function markAsCancelled(): void
    {
        $this->booking->update([
            'status' => 'cancelled',
            'admin_read_at' => now(),
        ]);

        $this->status = 'cancelled';
        $this->booking = $this->booking->fresh(['user', 'pricingPlan', 'pricingOrder']);

        $this->dispatch('toast', message: 'Booking cancelled successfully.', type: 'success');
    }

    public function statusColor(): string
    {
        return match ($this->booking->status) {
            'pending' => 'bg-amber-100 text-amber-700',
            'contacted' => 'bg-blue-100 text-blue-700',
            'quoted' => 'bg-purple-100 text-purple-700',
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-red-100 text-red-700',
            'converted' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-slate-100 text-slate-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function finalAmount(): float
    {
        return (float) ($this->booking->quoted_price ?: $this->booking->requested_price ?: $this->booking->plan_price);
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $this->statusColor() }}">
                    {{ ucfirst($booking->status) }}
                </span>

                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                    {{ $booking->booking_no }}
                </span>
            </div>

            <h1 class="text-h1 font-h1 text-on-surface">
                Pricing Booking Details
            </h1>

            <p class="mt-1 text-body-md font-body-md text-secondary">
                Review yearly plan booking, customer negotiation request, and provide final quoted amount.
            </p>
        </div>

        <a href="{{ route('admin.pricing-plan-bookings.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Bookings
        </a>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Main Content --}}
        <div class="col-span-12 space-y-6 lg:col-span-8">

            {{-- Booking Summary --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <h3 class="flex items-center gap-2 text-h3 font-h2 text-on-surface">
                        <span class="material-symbols-outlined text-primary">event_note</span>
                        Booking Summary
                    </h3>

                    <span class="text-xs font-bold uppercase tracking-wider text-secondary">
                        Submitted {{ $booking->created_at?->diffForHumans() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Plan</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">
                            {{ $booking->pricingPlan?->title ?? 'N/A' }}
                        </p>
                        <p class="mt-1 text-sm text-secondary">
                            {{ $booking->pricingPlan?->description ? Str::limit($booking->pricingPlan->description, 100) : 'No plan description available.' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Billing Cycle</p>
                        <p class="mt-2 text-base font-semibold capitalize text-slate-900">
                            {{ $booking->billing_cycle }}
                        </p>
                        <p class="mt-1 text-sm text-secondary">
                            Yearly booking with review and negotiation process.
                        </p>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-500">Original Plan Price</p>
                        <p class="mt-2 text-2xl font-bold text-blue-700">
                            ৳ {{ number_format((float) $booking->plan_price, 2) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Customer Requested Price</p>
                        <p class="mt-2 text-2xl font-bold text-amber-700">
                            {{ $booking->requested_price ? '৳ ' . number_format((float) $booking->requested_price, 2) : 'Not requested' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5 md:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-500">Final / Quoted Amount</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-700">
                            {{ $booking->quoted_price ? '৳ ' . number_format((float) $booking->quoted_price, 2) : 'Not quoted yet' }}
                        </p>

                        @if ($booking->quoted_price)
                            <p class="mt-2 text-sm text-emerald-700/80">
                                This is the amount admin offered after negotiation.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Customer Information --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2 text-on-surface">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Customer Information
                </h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Name</p>
                        <p class="mt-2 font-semibold text-slate-900">
                            {{ $booking->customer_name ?? $booking->user?->name ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Email</p>
                        @if ($booking->user?->email)
                            <a href="mailto:{{ $booking->user?->email }}"
                                class="mt-2 block break-all font-semibold text-primary hover:underline">
                                {{ $booking->user?->email }}
                            </a>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">N/A</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Phone</p>
                        @if ($booking->user?->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->user?->phone) }}"
                                class="mt-2 block font-semibold text-primary hover:underline">
                                {{ $booking->user?->phone }}
                            </a>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">N/A</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Account User</p>
                        <p class="mt-2 font-semibold text-slate-900">
                            {{ $booking->user?->name ?? 'N/A' }}
                        </p>
                        <p class="mt-1 text-sm text-secondary">
                            {{ $booking->user?->email ?? 'No account email' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5 md:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Address</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $booking->customer_address ?: 'No address provided.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Company Information --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2 text-on-surface">
                    <span class="material-symbols-outlined text-primary">business</span>
                    Company Information
                </h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Company Name</p>
                        <p class="mt-2 font-semibold text-slate-900">
                            {{ $booking->company_name ?: 'N/A' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Company Email</p>
                        @if ($booking->company_email)
                            <a href="mailto:{{ $booking->company_email }}"
                                class="mt-2 block break-all font-semibold text-primary hover:underline">
                                {{ $booking->company_email }}
                            </a>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">N/A</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Company Phone</p>
                        @if ($booking->company_phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->company_phone) }}"
                                class="mt-2 block font-semibold text-primary hover:underline">
                                {{ $booking->company_phone }}
                            </a>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">N/A</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Booking Status</p>
                        <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $this->statusColor() }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2 text-on-surface">
                    <span class="material-symbols-outlined text-primary">notes</span>
                    Notes
                </h3>

                <div class="grid grid-cols-1 gap-5">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Customer Note</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                            {{ $booking->user_note ?: 'No customer note provided.' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-500">Admin Note</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-blue-900">
                            {{ $booking->admin_note ?: 'No admin note added yet.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-span-12 space-y-6 lg:col-span-4">

            {{-- Admin Quote Form --}}
            <form wire:submit.prevent="saveQuote"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-h3 font-h2 text-on-surface">
                    <span class="material-symbols-outlined text-primary">payments</span>
                    Negotiation & Quote
                </h3>

                <div class="space-y-5">
                    <div>
                        <label class="mb-2 block font-label-md text-on-surface">Status</label>
                        <select wire:model="status"
                            class="w-full rounded border border-outline-variant bg-white px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10">
                            <option value="pending">Pending</option>
                            <option value="contacted">Contacted</option>
                            <option value="quoted">Quoted</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                            <option value="converted">Converted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        @error('status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block font-label-md text-on-surface">Quoted Amount</label>
                        <input wire:model="quoted_price" type="number" step="0.01"
                            class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                            placeholder="e.g., 8500" />

                        @error('quoted_price')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block font-label-md text-on-surface">Admin Note</label>
                        <textarea wire:model="admin_note" rows="5"
                            class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                            placeholder="Write negotiation details, final offer, terms, or next step..."></textarea>

                        @error('admin_note')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                        <span wire:loading.remove wire:target="saveQuote">Save Quote</span>

                        <span wire:loading wire:target="saveQuote" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>

            {{-- Quick Actions --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-label-sm font-label-sm uppercase tracking-widest text-secondary">
                    Quick Actions
                </h3>

                <div class="space-y-3">
                    @if ($booking->customer_phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer_phone) }}"
                            class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <span class="material-symbols-outlined text-[18px]">call</span>
                            Call Customer
                        </a>
                    @endif

                    @if ($booking->customer_email)
                        <a href="mailto:{{ $booking->customer_email }}?subject=Re: {{ rawurlencode($booking->booking_no) }}"
                            class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>
                            Email Customer
                        </a>
                    @endif

                    <button type="button" wire:click="markAsContacted"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                        <span class="material-symbols-outlined text-[18px]">support_agent</span>
                        Mark Contacted
                    </button>

                    <button type="button" wire:click="markAsCancelled"
                        wire:confirm="Are you sure you want to cancel this booking?"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                        Cancel Booking
                    </button>
                </div>
            </div>

            {{-- Price Snapshot --}}
            {{-- <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-h3 font-h2 text-on-surface">Price Snapshot</h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                        <span class="text-sm text-secondary">Plan Price</span>
                        <span class="font-semibold text-slate-900">
                            ৳ {{ number_format((float) $booking->plan_price, 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-3">
                        <span class="text-sm text-amber-700">Requested</span>
                        <span class="font-semibold text-amber-700">
                            {{ $booking->requested_price ? '৳ ' . number_format((float) $booking->requested_price, 2) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3">
                        <span class="text-sm text-emerald-700">Quoted</span>
                        <span class="font-semibold text-emerald-700">
                            {{ $booking->quoted_price ? '৳ ' . number_format((float) $booking->quoted_price, 2) : 'N/A' }}
                        </span>
                    </div>

                    <div class="mt-4 rounded-xl border border-primary/10 bg-primary/5 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-primary">
                            Current Final Amount
                        </p>
                        <p class="mt-2 text-2xl font-bold text-primary">
                            ৳ {{ number_format($this->finalAmount(), 2) }}
                        </p>
                    </div>
                </div>
            </div> --}}

            {{-- Linked Order --}}
            {{-- <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-h3 font-h2 text-on-surface">Linked Order</h3>

                @if ($booking->pricingOrder)
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">
                            Converted to Order
                        </p>

                        <p class="mt-2 font-semibold text-emerald-900">
                            {{ $booking->pricingOrder->order_no }}
                        </p>

                        <p class="mt-1 text-sm text-emerald-700">
                            ৳ {{ number_format((float) $booking->pricingOrder->amount, 2) }}
                        </p>

                        @if (Route::has('admin.pricing-orders.show'))
                            <a href="{{ route('admin.pricing-plan-orders.show', $booking->pricingOrder) }}" wire:navigate
                                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                                View Order
                                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </a>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                        <span class="material-symbols-outlined text-4xl text-slate-400">shopping_cart_off</span>

                        <p class="mt-2 text-sm font-semibold text-slate-700">
                            No order linked yet
                        </p>

                        <p class="mt-1 text-xs text-secondary">
                            Once customer accepts and pays, the linked order can appear here.
                        </p>
                    </div>
                @endif
            </div> --}}
        </div>
    </div>
</div>