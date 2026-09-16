<?php

use App\Events\ToolSubscriptionUpdated;
use App\Mail\ToolSubscriptionConfirmedMail;
use App\Models\Invoice;
use App\Models\ToolCategory;
use App\Models\ToolSubscription;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Tool Subscriptions')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $categoryFilter = '';
    public int $perPage = 10;

    public int $refreshKey = 0;

    public function mount(): void
    {
        ToolSubscription::query()
            ->whereNull('admin_read_at')
            ->update(['admin_read_at' => now()]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function categories()
    {
        return ToolCategory::query()->orderBy('name')->get();
    }

    public function subscriptions()
    {
        return ToolSubscription::query()
            ->with(['user', 'toolCategory', 'toolPlan', 'invoices'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter !== '', function ($query) {
                $query->where('tool_category_id', $this->categoryFilter);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function verify(int $id): void
    {
        $sub = ToolSubscription::findOrFail($id);

        $sub->update([
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $invoice = $this->createSubscriptionInvoice($sub);
        $this->notifySubscriptionConfirmed($sub);

        Mail::to($sub->user?->email)->send(new ToolSubscriptionConfirmedMail($sub, $invoice));

        ToolSubscriptionUpdated::dispatch($sub->fresh());

        $this->dispatch('toast', message: 'Payment verified. Subscription is now active.', type: 'success');
    }

    private function createSubscriptionInvoice(ToolSubscription $sub): ?Invoice
    {
        $alreadyInvoicedToday = $sub->invoices()
            ->whereDate('issue_date', now()->toDateString())
            ->exists();

        if ($alreadyInvoicedToday) {
            return null;
        }

        $invoice = Invoice::create([
            'user_id' => $sub->user_id,
            'tool_subscription_id' => $sub->id,
            'invoice_no' => Invoice::generateInvoiceNumber(),
            'customer_name' => $sub->user?->name ?? 'Customer',
            'customer_email' => $sub->user?->email,
            'customer_phone' => $sub->sender_bkash,
            'subject' => 'Tool Subscription - ' . ($sub->toolCategory?->name ?? 'Tool') . ' (' . ($sub->toolPlan?->name ?? 'Plan') . ')',
            'status' => 'paid',
            'issue_date' => now(),
            'sent_at' => now(),
        ]);

        $invoice->items()->create([
            'item_type' => 'custom',
            'item_id' => $sub->id,
            'title' => ($sub->toolCategory?->name ?? 'Tool') . ' - ' . ($sub->toolPlan?->name ?? 'Plan') . ' (' . ucfirst($sub->billing_cycle) . ')',
            'description' => 'Tool subscription activated. TrxID: ' . ($sub->transaction_id ?? '-'),
            'quantity' => 1,
            'unit_price' => (float) $sub->amount,
        ]);

        return $invoice;
    }

    private function notifySubscriptionConfirmed(ToolSubscription $sub): void
    {
        UserNotificationService::notifyUser(
            $sub->user_id,
            'Subscription Confirmed',
            'Your ' . ($sub->toolCategory?->name ?? 'tool') . ' subscription has been activated. You can download your invoice from My Subscriptions.',
            from: 'Support Team',
            url: route('account.tool-subscriptions'),
            type: 'subscription',
        );
    }

    public function markExpired(int $id): void
    {
        $sub = ToolSubscription::findOrFail($id);

        $sub->update([
            'status' => 'expired',
        ]);

        ToolSubscriptionUpdated::dispatch($sub->fresh());

        $this->dispatch('toast', message: 'Subscription marked as expired.', type: 'info');
    }

    public function delete(int $id): void
    {
        $sub = ToolSubscription::findOrFail($id);

        ToolSubscriptionUpdated::dispatch($sub->fresh());

        $sub->delete();

        $this->dispatch('toast', message: 'Subscription deleted successfully.', type: 'success');
    }

    #[On('echo-private:admin.tool-subscriptions,.tool-subscription.updated')]
    public function refreshSubscriptionsFromBroadcast(array $event = []): void
    {
        if (($event['actor'] ?? null) === 'admin') {
            return;
        }

        $this->refreshKey++;
    }
};
?>

<div class="mx-auto space-y-stack-lg">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h2 class="font-h1 text-h1 text-on-surface">Tool Subscriptions</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">
                Manage user subscriptions to tool category plans.
            </p>
        </div>
        <a href="{{ route('admin.tool-subscriptions.create') }}" wire:navigate
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md text-white shadow-sm transition-all hover:opacity-90">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Create Subscription
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-slate-400">search</span>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search by user name or email..."
                    class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10" />
            </div>
            <select wire:model.live="categoryFilter"
                class="rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10">
                <option value="">All Categories</option>
                @foreach ($this->categories() as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
                <option value="pending">Pending</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant">USER</th>
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant">TOOLS</th>
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant">PAYMENT</th>
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant">AMOUNT</th>
                        <th class="px-6 py-4 text-center text-label-sm text-on-surface-variant">STATUS</th>
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant">EXPIRES</th>
                        <th class="px-6 py-4 text-label-sm text-on-surface-variant"></th>
                    </tr>
                </thead>
                <tbody wire:key="sub-table-{{ $refreshKey }}" class="divide-y divide-slate-100">
                    @forelse ($this->subscriptions() as $sub)
                    <tr wire:key="sub-{{ $sub->id }}" class="transition-colors hover:bg-slate-50/80">
                        <td class="px-6 py-4">
                            <div>
                                <span class="block text-label-md font-semibold text-on-surface">{{ $sub->user?->name ?? '—' }}</span>
                                <span class="block text-body-sm text-on-surface-variant">{{ $sub->user?->email }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-label-md font-medium text-on-surface">
                                {{ $sub->toolCategory?->name ?? '—' }} - {{ $sub->toolPlan?->name ?? '—' }}
                            </span>
                            @if ($sub->invoices->isNotEmpty())
                            <div class="mt-1 space-y-0.5">
                                @foreach ($sub->invoices as $invoice)
                                <span class="block font-mono text-[11px] text-on-surface-variant">
                                    {{ $invoice->invoice_no }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($sub->transaction_id)
                            <div class="text-xs">
                                <span class="text-on-surface-variant">TrxID:</span>
                                <span class="font-mono text-primary">{{ $sub->transaction_id }}</span>
                                @if ($sub->sender_bkash)
                                <br><span class="text-on-surface-variant">From:</span>
                                <span class="font-mono">{{ $sub->sender_bkash }}</span>
                                @endif
                            </div>
                            @else
                            <span class="text-body-sm text-on-surface-variant">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-body-md">৳{{ number_format((float) $sub->amount, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span @class([ 'inline-flex rounded border px-2 py-1 text-[11px] font-bold uppercase tracking-wider' , 'bg-green-50 text-green-700 border-green-100'=> $sub->status === 'active',
                                'bg-red-50 text-red-700 border-red-100' => $sub->status === 'expired',
                                'bg-amber-50 text-amber-700 border-amber-100' => $sub->status === 'cancelled',
                                'bg-blue-50 text-blue-700 border-blue-100' => $sub->status === 'pending',
                                ])>
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono text-body-sm text-on-surface-variant">
                            {{ $sub->expires_at?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div x-data="{ open: false, pos: { top: 0, right: 0 } }" class="text-left">
                                <button type="button"
                                    @click="open = !open; if (open) { const r = $el.getBoundingClientRect(); pos = { top: r.bottom + 8, right: Math.max(window.innerWidth - r.right, 12) }; }"
                                    class="text-slate-400 transition-colors hover:text-primary">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                                <template x-teleport="body">
                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        :style="`position: fixed; z-index: 60; top: ${pos.top}px; right: ${pos.right}px;`"
                                        class="w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        @if ($sub->status === 'pending')
                                        <button type="button" wire:click="verify({{ $sub->id }})"
                                            wire:confirm="Verify payment and activate this subscription?"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                            Verify & Activate
                                        </button>
                                        @endif
                                        @if ($sub->status === 'active')
                                        <button type="button" wire:click="markExpired({{ $sub->id }})"
                                            wire:confirm="Mark this subscription as expired?"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-amber-700 transition hover:bg-amber-50">
                                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                                            Mark Expired
                                        </button>
                                        @endif
                                        @if ($sub->invoices->isNotEmpty())
                                        <div class="my-1 border-t border-slate-100"></div>
                                        @foreach ($sub->invoices as $invoice)
                                        <a href="{{ route('admin.invoices.view', $invoice) }}" wire:navigate
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            View Invoice
                                        </a>
                                        <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                            Download Invoice
                                        </a>
                                        @endforeach
                                        @endif
                                        <a href="{{ route('admin.tool-subscriptions.edit', $sub) }}" wire:navigate
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                            Edit
                                        </a>
                                        <button type="button" wire:click="delete({{ $sub->id }})"
                                            wire:confirm="Are you sure you want to delete this subscription?"
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
                    <tr>
                        <td colspan="7" class="px-6 py-14 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <span class="material-symbols-outlined">subscriptions</span>
                                </div>
                                <h3 class="text-base font-semibold text-on-surface">No subscriptions found</h3>
                                <p class="mt-1 text-sm text-secondary">Create a subscription to get started.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-4 bg-slate-50/50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-body-sm text-on-surface-variant">Showing subscriptions</span>
            <div>
                {{ $this->subscriptions()->links() }}
            </div>
        </div>
    </div>
</div>