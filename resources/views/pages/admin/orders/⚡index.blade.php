    <?php

    use App\Mail\OrderInvoiceMail;
    use App\Models\PricingOrder;
    use Illuminate\Support\Facades\Mail;
    use Livewire\Attributes\Layout;
    use Livewire\Attributes\Title;
    use Livewire\Component;
    use Livewire\WithPagination;

    new #[Layout('layouts.admin-app')] #[Title('Pricing Orders')] class extends Component {
        use WithPagination;

        public string $search = '';
        public string $status = 'all';
        public int $perPage = 10;
        public int $refreshKey = 0;

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

        public function orders()
        {
            $search = trim($this->search);

            return PricingOrder::query()
                ->with(['user', 'pricingPlan'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('order_no', 'like', '%' . $search . '%')
                            ->orWhere('transaction_id', 'like', '%' . $search . '%')
                            ->orWhere('bank_transaction_id', 'like', '%' . $search . '%')
                            ->orWhere('currency', 'like', '%' . $search . '%')
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('email', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('pricingPlan', function ($planQuery) use ($search) {
                                $planQuery->where('title', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->when($this->status !== 'all', function ($query) {
                    $query->where('payment_status', $this->status);
                })
                ->latest()
                ->paginate($this->perPage);
        }

        public function markAsPaid(int $orderId): void
        {
            $order = PricingOrder::with(['user', 'pricingPlan'])->findOrFail($orderId);

            $wasAlreadyPaid = $order->payment_status === 'paid';

            $order->update([
                'payment_status' => 'paid',
                'ssl_status' => $order->ssl_status ?: 'MANUAL',
                'paid_at' => $order->paid_at ?: now(),
            ]);

            if (! $wasAlreadyPaid && $order->user?->email) {
                Mail::to($order->user->email)->send(new OrderInvoiceMail($order));
            }

            $this->dispatch(
                'toast',
                message: $order->user?->email
                    ? 'Order marked as paid and invoice email sent.'
                    : 'Order marked as paid.',
                type: 'success'
            );
        }

        public function markAsPending(int $orderId): void
        {
            $order = PricingOrder::findOrFail($orderId);

            $order->update([
                'payment_status' => 'pending',
                'paid_at' => null,
            ]);

            $this->dispatch('toast', message: 'Order marked as pending.', type: 'success');
        }

        public function resendInvoice(int $orderId): void
        {
            $order = PricingOrder::with(['user', 'pricingPlan'])->findOrFail($orderId);

            if (! $order->user?->email) {
                $this->dispatch('toast', message: 'Customer email not found.', type: 'error');
                return;
            }

            Mail::to($order->user->email)->send(new OrderInvoiceMail($order));

            $this->dispatch('toast', message: 'Invoice email sent successfully.', type: 'success');
        }

        public function delete(int $orderId): void
        {
            PricingOrder::findOrFail($orderId)->delete();

            $this->dispatch('toast', message: 'Order deleted successfully.', type: 'success');
        }
    };
    ?>

    <div>
        <div class="mx-auto w-full max-w-7xl space-y-stack-lg">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                        Pricing Orders
                    </h2>

                    <p class="text-xs font-body-md text-secondary md:text-body-md">
                        Manage customer pricing plan orders, payment status, invoice emails, and invoice downloads.
                    </p>
                </div>

                <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                search
                            </span>

                            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search order..."
                                class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                        </div>

                        <div class="relative">
                            <select wire:model.live="status"
                                class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/10">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
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
                                    Order
                                </th>

                                <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Customer
                                </th>

                                <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Plan
                                </th>

                                <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Amount
                                </th>

                                <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Status
                                </th>

                                <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Paid At
                                </th>

                                <th class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($this->orders() as $order)
                                <tr wire:key="pricing-order-{{ $order->id }}" class="transition-colors hover:bg-slate-50/80">
                                    <td class="px-6 py-4">
                                        <div>
                                            <span class="block text-label-md font-label-md text-on-surface">
                                                {{ $order->order_no }}
                                            </span>

                                            <span class="block font-mono text-[11px] text-slate-400">
                                                {{ $order->transaction_id }}
                                            </span>

                                            @if ($order->bank_transaction_id)
                                                <span class="mt-1 block font-mono text-[11px] text-secondary">
                                                    Bank: {{ $order->bank_transaction_id }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="block text-body-sm text-on-surface">
                                            {{ $order->user?->name ?? 'Guest Customer' }}
                                        </span>

                                        @if ($order->user?->email)
                                            <span class="block text-xs text-slate-400">
                                                {{ $order->user->email }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="block text-body-sm text-on-surface">
                                            {{ $order->pricingPlan?->title ?? 'N/A' }}
                                        </span>

                                        <span class="block text-xs capitalize text-secondary">
                                            {{ $order->billing_cycle }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-body-sm text-on-surface">
                                        {{ $order->currency ?? 'BDT' }}
                                        {{ number_format((float) $order->amount, 2) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider',
                                            'bg-slate-100 text-slate-600' => $order->payment_status === 'pending',
                                            'bg-emerald-100 text-emerald-700' => $order->payment_status === 'paid',
                                            'bg-red-100 text-red-700' => $order->payment_status === 'failed',
                                            'bg-amber-100 text-amber-700' => $order->payment_status === 'cancelled',
                                        ])>
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-body-sm text-secondary">
                                        {{ $order->paid_at ? $order->paid_at->format('M d, Y h:i A') : 'Not paid yet' }}
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div x-data="{ open: false }" class="relative inline-block text-left">
                                            <button type="button" @click="open = !open"
                                                class="text-slate-400 transition-colors hover:text-primary">
                                                <span class="material-symbols-outlined">more_vert</span>
                                            </button>

                                            <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                                class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

                                                @if ($order->payment_status !== 'paid')
                                                    <button type="button" wire:click="markAsPaid({{ $order->id }})"
                                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                        <span class="material-symbols-outlined text-[18px]">task_alt</span>
                                                        Mark Paid
                                                    </button>
                                                @endif

                                                @if ($order->payment_status === 'paid')
                                                    <button type="button" wire:click="markAsPending({{ $order->id }})"
                                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                        <span class="material-symbols-outlined text-[18px]">pending</span>
                                                        Mark Pending
                                                    </button>
                                                @endif

                                                <button type="button" wire:click="resendInvoice({{ $order->id }})"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">outgoing_mail</span>
                                                    Resend Invoice
                                                </button>

                                                <a href="{{ route('admin.orders.invoice.download', $order) }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                                    Download Invoice
                                                </a>

                                                <button type="button" wire:click="delete({{ $order->id }})"
                                                    wire:confirm="Are you sure you want to delete this order?"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 cursor-pointer">
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
                                                <span class="material-symbols-outlined">receipt_long</span>
                                            </div>

                                            <h3 class="text-base font-semibold text-on-surface">
                                                No orders found
                                            </h3>

                                            <p class="mt-1 text-sm text-secondary">
                                                Customer pricing orders will appear here.
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
                        {{ $this->orders()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>