<?php

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Edit Order')] class extends Component {
    public Order $order;

    public string $status = 'awaiting_payment';
    public string $payment_status = 'awaiting_payment';
    public string $billing_cycle = 'monthly';

    public string $start_date = '';
    public string $end_date = '';

    public string $admin_note = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['booking', 'user', 'service.category', 'servicePlan', 'pricingPlan']);

        $this->status = $order->status ?? 'awaiting_payment';
        $this->payment_status = $order->payment_status;
        $this->billing_cycle = $order->billing_cycle ?? 'monthly';

        $this->start_date = $order->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $order->end_date?->format('Y-m-d') ?? '';

        $this->admin_note = $order->admin_note ?? '';
    }

    protected function rules(): array
    {
        return [
            'status' => ['required', $this->order->order_type === 'service'
                ? 'in:pending,active,completed,cancelled'
                : 'in:pending,awaiting_payment,paid,active,completed,cancelled'],
            'payment_status' => ['required', 'in:awaiting_payment,paid'],
            'billing_cycle' => ['nullable', 'in:one_time,monthly,yearly,custom'],

            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'admin_note' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date must be same as or after start date.',
        ];
    }

    public function autoSetDates(): void
    {
        $this->start_date = now()->toDateString();

        $this->end_date = match ($this->billing_cycle) {
            'monthly' => now()->addMonth()->toDateString(),
            'yearly' => now()->addYear()->toDateString(),
            default => '',
        };

        $this->dispatch('toast', message: 'Dates updated based on billing cycle.', type: 'success');
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            if ($this->order->renewal_of_id) {
                if ($validated['payment_status'] !== $this->order->payment_status) {
                    if ($validated['payment_status'] === 'paid' && $this->order->renewal_manual_submitted_at && ! $this->order->renewal_manual_verified_at) {
                        app(\App\Services\ServiceRenewalManualPayment::class)->confirm($this->order);
                    } else {
                        $this->order->setPaymentStatus($validated['payment_status']);
                    }
                }
                $attributes = ['admin_note' => $validated['admin_note'] ?: null];
                if (in_array($validated['status'], ['cancelled', 'completed'], true)) {
                    $attributes['status'] = $validated['status'];
                }
                $this->order->update($attributes);

                return;
            }
            if ($this->order->order_type === 'service') {
                $this->order->setPaymentStatus($validated['payment_status']);
            }
            $this->order->update([
                'status' => $validated['status'],
                'billing_cycle' => $validated['billing_cycle'],

                'start_date' => $validated['start_date'] ?: null,
                'end_date' => $validated['end_date'] ?: null,

                'admin_note' => $validated['admin_note'] ?: null,
            ]);
        });

        $this->order = $this->order->fresh(['booking', 'user', 'service.category', 'servicePlan', 'pricingPlan']);

        $this->dispatch('toast', message: 'Order updated successfully.', type: 'success');
    }

    public function orderTitle(): string
    {
        if ($this->order->order_type === 'pricing_plan') {
            return $this->order->pricingPlan?->title ?? ($this->order->plan_name ?? 'Pricing Plan Order');
        }

        return $this->order->service?->card_title ?? ($this->order->servicePlan?->name ?? ($this->order->plan_name ?? 'Service Order'));
    }

    public function orderSubtitle(): ?string
    {
        if ($this->order->order_type === 'pricing_plan') {
            return $this->order->pricingPlan?->plan_type ? ucfirst($this->order->pricingPlan->plan_type) : 'Pricing Plan';
        }

        if ($this->order->servicePlan?->name) {
            return $this->order->servicePlan->name;
        }

        return $this->order->service?->category?->name;
    }

    public function sendInvoice(): void
    {
        $email = $this->clientEmail();

        if (!$email) {
            $this->dispatch('toast', message: 'No customer email found for this order.', type: 'error');

            return;
        }

        Mail::to($email)->send(new OrderInvoiceMail($this->order));

        $this->dispatch('toast', message: 'Invoice sent successfully to ' . $email, type: 'success');
    }

    public function clientEmail(): ?string
    {
        return $this->order->email ?: $this->order->user?->email;
    }

    public function statusClass(): string
    {
        return match ($this->order->status) {
            'pending' => 'bg-slate-100 text-slate-700',
            'awaiting_payment' => 'bg-amber-100 text-amber-700',
            'paid' => 'bg-emerald-100 text-emerald-700',
            'active' => 'bg-blue-100 text-blue-700',
            'completed' => 'bg-purple-100 text-purple-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $this->statusClass() }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>

                    <span
                        class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                        {{ $order->order_no }}
                    </span>

                    @if ($order->booking?->booking_no)
                        <span
                            class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-700">
                            Booking: {{ $order->booking->booking_no }}
                        </span>
                    @endif
                </div>

                <h1 class="text-h1 font-h1 text-on-surface">
                    Edit Order
                </h1>

                <p class="mt-1 text-body-md text-secondary">
                    Update order status, payment status, billing cycle, and admin note.
                </p>
            </div>

            <a href="{{ route('admin.orders.index') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Back to Orders
            </a>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-7">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">receipt_long</span>
                        Invoice Items
                    </h3>

                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-primary text-white">
                                    <th class="px-4 py-3 uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-3 text-center uppercase tracking-wider">Billing</th>
                                    <th class="px-4 py-3 text-right uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-right uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200">
                                <tr class="bg-white">
                                    <td class="px-4 py-4 align-top font-bold text-slate-900">
                                        {{ $this->orderTitle() }}

                                        @if ($this->orderSubtitle())
                                            <div class="mt-0.5 text-[11px] font-normal text-slate-500">
                                                {{ $this->orderSubtitle() }}
                                            </div>
                                        @endif

                                        @if ($order->booking?->addons)
                                            <div class="mt-2 space-y-0.5 border-t border-dashed border-slate-200 pt-2">
                                                @foreach ($order->booking->addons as $addon)
                                                    <div class="flex items-center justify-between text-[11px]">
                                                        <span class="text-indigo-700">
                                                            + {{ $addon['name'] ?? 'Addon' }}
                                                        </span>
                                                        <span class="font-semibold text-indigo-600">
                                                            @if (isset($addon['price']) && $addon['price'] !== null && $addon['price'] !== '')
                                                                ৳ {{ number_format((float) $addon['price'], 2) }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-center align-top text-slate-600">
                                        {{ ucfirst($order->billing_cycle ?? 'N/A') }}
                                    </td>

                                    <td class="px-4 py-4 text-right align-top text-slate-600">
                                        {{ $order->plan_price !== null ? '৳ ' . number_format((float) $order->plan_price, 2) : 'N/A' }}
                                    </td>

                                    <td class="px-4 py-4 text-right align-top font-bold text-slate-900">
                                        {{ $order->amount !== null ? '৳ ' . number_format((float) $order->amount, 2) : 'N/A' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <div class="w-full max-w-xs">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm text-slate-500">
                                    <span>Plan Price</span>
                                    <span class="font-mono">{{ $order->plan_price !== null ? '৳ ' . number_format((float) $order->plan_price, 2) : 'N/A' }}</span>
                                </div>

                                @if ($order->booking?->addons)
                                    @php
                                        $addonsTotal = collect($order->booking->addons)->sum(fn ($a) => (float) ($a['price'] ?? 0));
                                    @endphp
                                    <div class="flex justify-between text-sm text-indigo-600">
                                        <span>Addons</span>
                                        <span class="font-mono">{{ $addonsTotal > 0 ? '৳ ' . number_format($addonsTotal, 2) : 'N/A' }}</span>
                                    </div>
                                @endif

                                @if ($order->requested_price !== null)
                                    <div class="flex justify-between text-sm text-amber-600">
                                        <span>Requested Price</span>
                                        <span class="font-mono">৳ {{ number_format((float) $order->requested_price, 2) }}</span>
                                    </div>
                                @endif

                                @if ($order->quoted_price !== null)
                                    <div class="flex justify-between text-sm text-emerald-600">
                                        <span>Quoted Price</span>
                                        <span class="font-mono">৳ {{ number_format((float) $order->quoted_price, 2) }}</span>
                                    </div>
                                @endif

                                @if ($order->final_price !== null)
                                    <div class="flex justify-between text-sm text-blue-600">
                                        <span>Final Price</span>
                                        <span class="font-mono">৳ {{ number_format((float) $order->final_price, 2) }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between border-t-2 border-primary pt-3">
                                    <span class="text-base font-bold uppercase text-primary">Order Amount</span>
                                    <span class="text-lg font-extrabold text-primary">
                                        {{ $order->amount !== null ? '৳ ' . number_format((float) $order->amount, 2) : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($order->renewal_manual_submitted_at)
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-slate-800">
                        <h3 class="font-semibold">Manual bKash payment</h3>
                        <dl class="mt-3 space-y-2">
                            <div><dt class="font-medium">Sending number</dt><dd>{{ $order->renewal_sender_bkash }}</dd></div>
                            <div><dt class="font-medium">Transaction ID</dt><dd class="font-mono">{{ $order->renewal_manual_transaction_id }}</dd></div>
                            <div><dt class="font-medium">Submitted</dt><dd>{{ $order->renewal_manual_submitted_at->format('d M Y, h:i A') }}</dd></div>
                        </dl>
                        <p class="mt-3">{{ $order->renewal_manual_verified_at ? 'Verified on '.$order->renewal_manual_verified_at->format('d M Y, h:i A') : 'Check the received payment against these details, then set Payment Status to Paid and save to confirm the renewal.' }}</p>
                    </div>
                @endif
                <form wire:submit.prevent="save">
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">settings</span>
                            Order Settings
                        </h3>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            @if ($order->renewal_of_id)
                                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800 md:col-span-2">
                                    This is a renewal order. Payment confirmation sets its service period automatically.
                                    @if ($order->start_date && $order->end_date)
                                        Period: {{ $order->start_date->format('d M Y') }} – {{ $order->end_date->format('d M Y') }}.
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Order Status</label>

                                <select wire:model="status"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    <option value="pending">Pending</option>
                                    @if ($order->order_type === 'pricing_plan')
                                        <option value="awaiting_payment">Awaiting Payment</option>
                                        <option value="paid">Paid</option>
                                    @endif
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>

                                @error('status')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($order->order_type === 'service')
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Payment Status</label>
                                    <select wire:model="payment_status" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                        <option value="awaiting_payment">Awaiting Payment</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                    @error('payment_status')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Billing Cycle</label>

                                <select wire:model="billing_cycle"
                                    @disabled($order->renewal_of_id)
                                    class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    <option value="one_time">One-time</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Custom</option>
                                </select>

                                @error('billing_cycle')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Start Date</label>

                                <input type="date" wire:model="start_date"
                                    @disabled($order->renewal_of_id)
                                    class="w-full rounded border border-outline-variant px-4 py-2.5">

                                @error('start_date')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">End Date</label>

                                <input type="date" wire:model="end_date"
                                    @disabled($order->renewal_of_id)
                                    class="w-full rounded border border-outline-variant px-4 py-2.5">

                                @error('end_date')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <button type="button" wire:click="autoSetDates"
                                    @disabled($order->renewal_of_id)
                                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                    <span class="material-symbols-outlined text-lg">event_repeat</span>
                                    Auto Set Dates From Billing Cycle
                                </button>
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Admin Note</label>

                                <textarea rows="4" wire:model="admin_note"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5"
                                    placeholder="Write internal order note, delivery note, renewal note, or payment note..."></textarea>

                                @error('admin_note')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ route('admin.orders.index') }}" wire:navigate
                                class="inline-flex items-center justify-center rounded-lg border border-outline-variant bg-white px-5 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50">
                                Cancel
                            </a>

                            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="save">Save Changes</span>

                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                    <span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-5">
                <div class="sticky top-20 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Order Summary</h3>

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-secondary">Order No</p>
                            <p class="mt-1 font-mono text-sm font-bold text-on-surface">
                                {{ $order->order_no }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider text-secondary">Customer</p>
                            <p class="mt-1 font-sm font-bold text-on-surface">
                                {{ $order->full_name ?: $order->user?->name ?: 'Guest Customer' }}
                            </p>

                            @if ($order->email ?: $order->user?->email)
                                <p class="mt-1 text-xs text-secondary">
                                    {{ $order->email ?: $order->user?->email }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider text-secondary">Company</p>
                            <p class="mt-1 text-sm font-bold text-on-surface">
                                {{ $order->company_name ?: 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider text-secondary">Current Period</p>
                            <p class="mt-1 text-sm font-bold text-on-surface">
                                {{ $order->start_date?->format('M d, Y') ?? 'N/A' }}
                                -
                                {{ $order->end_date?->format('M d, Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-slate-200 pt-6">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary">
                            Send Invoice
                        </h4>

                        @if ($this->clientEmail())
                            <button type="button" wire:click="sendInvoice" wire:loading.attr="disabled"
                                wire:target="sendInvoice"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>

                                <span wire:loading.remove wire:target="sendInvoice">
                                    Send Invoice
                                </span>

                                <span wire:loading wire:target="sendInvoice" class="inline-flex items-center gap-2">
                                    <span
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700"></span>
                                    Sending...
                                </span>
                            </button>
                        @else
                            <p class="text-sm text-red-500">No customer email found.</p>
                        @endif
                    </div>

                    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <div class="flex gap-3">
                            <span class="material-symbols-outlined text-blue-700">info</span>

                            <div>
                                <h4 class="font-bold text-blue-900">Date Rules</h4>

                                <p class="mt-1 text-sm leading-6 text-blue-800">
                                    Monthly orders should normally have a 1 month period. Yearly orders should normally have
                                    a 1 year period. You can manually change both dates anytime.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
