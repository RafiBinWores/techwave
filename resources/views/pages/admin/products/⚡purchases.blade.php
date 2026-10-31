<?php

use App\Models\ProductPurchase;
use App\Models\User;
use App\Services\Products\ProductContent;
use App\Services\Products\ProductPurchases;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Product purchases')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    #[Locked]
    public ?int $editing = null;

    #[Locked]
    public ?int $deleting = null;

    public string $edit_status = 'pending';

    public ?string $edit_starts_at = null;

    public ?string $edit_expires_at = null;

    public string $edit_admin_note = '';

    public function edit(int $id): void
    {
        ProductContent::authorizeAdmin();
        $purchase = ProductPurchase::findOrFail($id);
        $this->editing = $id;
        $this->edit_status = $purchase->displayStatus();
        $this->edit_starts_at = $purchase->starts_at?->format('Y-m-d\TH:i');
        $this->edit_expires_at = $purchase->expires_at?->format('Y-m-d\TH:i');
        $this->edit_admin_note = $purchase->admin_note ?? '';
        $this->resetValidation();
    }

    public function closeEdit(): void
    {
        $this->editing = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        ProductContent::authorizeAdmin();
        abort_unless($this->editing, 404);
        $this->validate([
            'edit_status' => ['required', Rule::in(['pending', 'active', 'expired', 'cancelled', 'rejected', 'renewed'])],
            'edit_starts_at' => ['nullable', 'date'],
            'edit_expires_at' => ['nullable', 'date', 'after_or_equal:edit_starts_at'],
            'edit_admin_note' => ['nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function (): void {
            $initial = ProductPurchase::findOrFail($this->editing);
            User::whereKey($initial->user_id)->lockForUpdate()->firstOrFail();
            $purchase = ProductPurchase::whereKey($this->editing)->lockForUpdate()->firstOrFail();
            // A renewed purchase is historical; only the linked renewal owns the current subscription period.
            if (($purchase->status === 'renewed') !== ($this->edit_status === 'renewed')) {
                throw ValidationException::withMessages(['edit_status' => 'Renewed purchase history cannot be activated again. Edit the current renewal instead.']);
            }
            // Status edits cannot replace payment verification or rewrite a settled invoice.
            if (in_array($this->edit_status, ['active', 'expired'], true) && ! $purchase->invoice_id) {
                throw ValidationException::withMessages(['edit_status' => 'Verify the payment before activating this purchase.']);
            }
            if ($purchase->invoice_id && in_array($this->edit_status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['edit_status' => 'A paid purchase cannot be changed back to an unpaid status.']);
            }
            if ($this->edit_status === 'active' && $purchase->billing_cycle !== 'one_time' && (! $this->edit_starts_at || ! $this->edit_expires_at)) {
                throw ValidationException::withMessages(['edit_expires_at' => 'Active subscriptions require a start date and an expiry date.']);
            }
            if ($purchase->billing_cycle === 'one_time' && ($this->edit_expires_at || $this->edit_status === 'expired')) {
                throw ValidationException::withMessages(['edit_expires_at' => 'One-time purchases have no subscription expiry.']);
            }
            if ($this->edit_status === 'active' && app(\App\Services\Products\ProductPurchases::class)->overlapping($purchase)) {
                throw ValidationException::withMessages(['edit_status' => 'This customer already has an active purchase for this product.']);
            }
            $purchase->update([
                'status' => $this->edit_status,
                'starts_at' => $this->edit_starts_at ?: null,
                'expires_at' => $this->edit_status === 'expired' ? now() : ($this->edit_expires_at ?: null),
                'admin_note' => $this->edit_admin_note ?: null,
            ]);
        });
        $this->closeEdit();
        $this->dispatch('toast', message: 'Product purchase updated.', type: 'success');
    }

    public function markExpired(int $id): void
    {
        ProductContent::authorizeAdmin();
        $purchase = ProductPurchase::findOrFail($id);
        if ($purchase->billing_cycle === 'one_time' || $purchase->status !== 'active') {
            $this->addError('purchase', 'Only active subscriptions can be marked expired.');

            return;
        }
        ProductPurchase::whereKey($id)->where('status', 'active')->update(['status' => 'expired', 'expires_at' => now()]);
        $this->dispatch('toast', message: 'Product subscription marked as expired.', type: 'success');
    }

    public function requestDelete(int $id): void
    {
        ProductContent::authorizeAdmin();
        ProductPurchase::findOrFail($id);
        $this->deleting = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleting = null;
    }

    public function delete(): void
    {
        ProductContent::authorizeAdmin();
        abort_unless($this->deleting, 404);
        // Archive the record; its invoices and gateway payment history remain available for accounting.
        ProductPurchase::findOrFail($this->deleting)->delete();
        if ($this->editing === $this->deleting) {
            $this->closeEdit();
        }
        $this->deleting = null;
        $this->resetPage();
        $this->dispatch('toast', message: 'Product purchase deleted. Financial records were retained.', type: 'success');
    }

    public function boot(): void
    {
        ProductContent::authorizeAdmin();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirm(int $id): void
    {
        app(ProductPurchases::class)->confirmManual($id);
        $this->dispatch('toast', message: 'Product payment verified and purchase activated.', type: 'success');
    }

    public function reject(int $id): void
    {
        ProductContent::authorizeAdmin();
        ProductPurchase::whereKey($id)->where('status', 'pending')->where('payment_method', 'manual')
            ->update(['status' => 'rejected']);
        $this->dispatch('toast', message: 'Manual payment rejected.', type: 'success');
    }

    public function with(): array
    {
        return ['purchases' => ProductPurchase::with(['user', 'invoice'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('product_name', 'like', '%'.$this->search.'%')
                    ->orWhere('plan_name', 'like', '%'.$this->search.'%')
                    ->orWhere('transaction_id', 'like', '%'.$this->search.'%');
            }))
            ->when(in_array($this->status, ['pending', 'active', 'expired', 'cancelled', 'rejected'], true), fn ($query) => $query->where('status', $this->status))
            ->latest()->paginate(15)];
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-h1 font-h1 text-on-surface">Product purchases</h1>
        <p class="mt-2 text-sm text-secondary">Review software subscriptions and one-time purchases. Verify manual payments before activation.</p>
    </div>
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach ($errors->all() as $message) <p>{{ $message }}</p> @endforeach
        </div>
    @endif
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input type="search" wire:model.live.debounce.400ms="search" aria-label="Search product purchases" placeholder="Search products, plans or transactions"
                class="rounded-lg border border-outline-variant px-3 py-2 text-sm sm:w-80">
            <select wire:model.live="status" aria-label="Purchase status" class="rounded-lg border border-outline-variant px-3 py-2 text-sm">
                <option value="all">All statuses</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
                <option value="renewed">Renewed</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-secondary">
                    <tr>
                        <th class="px-5 py-4">Purchase</th>
                        <th class="px-5 py-4">Customer</th>
                        <th class="px-5 py-4">Payment</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($purchases as $purchase)
                        <tr wire:key="purchase-{{ $purchase->id }}">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $purchase->product_name }}</p>
                                <p class="mt-1 text-secondary">{{ $purchase->plan_name }} · {{ str_replace('_', '-', $purchase->billing_cycle) }}</p>
                                @if ($purchase->renewal_of_id)
                                    <p class="mt-1 text-xs text-secondary">Renewal of purchase #{{ $purchase->renewal_of_id }}</p>
                                @endif
                                <p class="mt-1 text-xs text-secondary">{{ $purchase->invoice?->invoice_no ?? 'Purchase #'.$purchase->id }} · {{ $purchase->created_at->format('d M Y') }}</p>
                                @if ($purchase->invoice)
                                    <div class="mt-2 flex gap-3 text-xs font-semibold text-primary">
                                        <a href="{{ route('admin.invoices.view', $purchase->invoice) }}" wire:navigate>View invoice</a>
                                        <a href="{{ route('admin.invoices.pdf', $purchase->invoice) }}">Download</a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p>{{ $purchase->customer['name'] }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $purchase->customer['email'] }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $purchase->customer['phone'] }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">৳{{ number_format((float) $purchase->amount, 2) }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $purchase->payment_method === 'manual' ? 'bKash: '.$purchase->sender_bkash : 'SSLCommerz' }}</p>
                                <p class="mt-1 break-all font-mono text-xs">{{ $purchase->transaction_id ?? 'Awaiting online payment' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize">{{ $purchase->displayStatus() }}</span>
                                @if ($purchase->expires_at)
                                    <p class="mt-2 text-xs text-secondary">Until {{ $purchase->expires_at->format('d M Y') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                            <div x-data="{ open: false, pos: { top: 0, right: 0 } }" class="text-left">
                                <button type="button"
                                    @click="open = !open; if (open) { const r = $el.getBoundingClientRect(); pos = { top: r.bottom + 8, right: Math.max(window.innerWidth - r.right, 12) }; }"
                                    aria-label="Purchase actions" :aria-expanded="open"
                                    @keydown.escape.window="open = false"
                                    class="text-slate-400 transition-colors hover:text-primary">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                                <template x-teleport="body">
                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        :style="`position: fixed; z-index: 60; top: ${pos.top}px; right: ${pos.right}px;`"
                                        class="w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        @if ($purchase->status === 'pending' && $purchase->payment_method === 'manual')
                                        <button type="button" wire:click="confirm({{ $purchase->id }})"
                                            wire:confirm="Verify payment and activate this subscription?"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                            Verify & Activate
                                        </button>
                                        @endif
                                        @if ($purchase->displayStatus() === 'active' && $purchase->billing_cycle !== 'one_time')
                                        <button type="button" wire:click="markExpired({{ $purchase->id }})"
                                            wire:confirm="Mark this subscription as expired?"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-amber-700 transition hover:bg-amber-50">
                                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                                            Mark Expired
                                        </button>
                                        @endif
                                        @if ($purchase->status === 'pending' && $purchase->payment_method === 'manual')
                                            <button type="button" wire:click="reject({{ $purchase->id }})" wire:confirm="Reject this manual payment?"
                                                @click="open = false" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">cancel</span>Reject Payment
                                            </button>
                                        @endif
                                        @if ($purchase->invoice)
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <a href="{{ route('admin.invoices.view', $purchase->invoice) }}" wire:navigate
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            View Invoice
                                        </a>
                                        <a href="{{ route('admin.invoices.pdf', $purchase->invoice) }}" target="_blank"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                            Download Invoice
                                        </a>
                                        @endif
                                        <button type="button" wire:click="edit({{ $purchase->id }})"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                            Edit
                                        </button>
                                        <button type="button" wire:click="requestDelete({{ $purchase->id }})"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                            Delete
                                        </button>
                                    </div>
                                </template>
                            </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center text-secondary">No product purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $purchases->links() }}</div>
    </div>

    {{-- Native dialogs keep edit and delete actions compact and separate from the table. --}}
    @if ($editing)
        <dialog wire:key="edit-purchase-{{ $editing }}" wire:ignore.self x-data
            x-init="$el.showModal()" @cancel.prevent="$wire.closeEdit()"
            aria-labelledby="edit-purchase-title"
            class="m-auto w-[calc(100%-2rem)] max-w-xl max-h-[calc(100dvh-3rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-on-surface shadow-2xl backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm">
            <form wire:submit="saveEdit" novalidate class="p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="edit-purchase-title" class="text-xl font-bold">Edit product purchase</h2>
                    <button type="button" wire:click="closeEdit" aria-label="Close editor" class="rounded-lg p-1 text-secondary hover:bg-slate-100">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <p class="mt-2 text-sm leading-6 text-secondary">Update subscription status, dates and notes. Payment verification and invoice amounts stay tied to the original purchase.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="purchase-edit-status" class="mb-2 block text-sm font-semibold">Status</label>
                        <select id="purchase-edit-status" wire:model="edit_status" class="w-full rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error('edit_status') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="purchase-edit-start" class="mb-2 block text-sm font-semibold">Starts at</label>
                        <input id="purchase-edit-start" type="datetime-local" wire:model="edit_starts_at" class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                        @error('edit_starts_at') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="purchase-edit-expiry" class="mb-2 block text-sm font-semibold">Expires at</label>
                        <input id="purchase-edit-expiry" type="datetime-local" wire:model="edit_expires_at" class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                        @error('edit_expires_at') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="purchase-edit-note" class="mb-2 block text-sm font-semibold">Admin note</label>
                        <textarea id="purchase-edit-note" wire:model="edit_admin_note" rows="3" maxlength="5000" class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm"></textarea>
                        @error('edit_admin_note') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="closeEdit" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Save changes</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($deleting)
        <dialog wire:key="delete-purchase-{{ $deleting }}" wire:ignore.self x-data
            x-init="$el.showModal()" @cancel.prevent="$wire.cancelDelete()"
            aria-labelledby="delete-purchase-title"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-6 text-on-surface shadow-2xl backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm">
            <h2 id="delete-purchase-title" class="text-xl font-bold">Delete product purchase?</h2>
            <p class="mt-3 text-sm leading-6 text-secondary">This removes the purchase from the list. Its invoice and payment history will be retained for accounting.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" wire:click="cancelDelete" autofocus class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Cancel</button>
                <button type="button" wire:click="delete" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Delete purchase</button>
            </div>
        </dialog>
    @endif
</div>
