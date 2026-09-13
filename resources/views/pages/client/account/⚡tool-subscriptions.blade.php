<?php

use App\Models\Invoice;
use App\Models\ToolSubscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Subscriptions')] class extends Component {
    use WithPagination;

    public int $refreshKey = 0;

    public ?Invoice $previewInvoice = null;

    public function getListeners(): array
    {
        return [
            'echo-private:user.'.Auth::id().'.tool-subscriptions,.tool-subscription.updated' => 'refreshSubscriptionsFromBroadcast',
        ];
    }

    public function refreshSubscriptionsFromBroadcast(array $event = []): void
    {
        $this->refreshKey++;

        $status = $event['status'] ?? null;

        $message = match ($status) {
            'active' => 'Your subscription has been activated.',
            'expired' => 'Your subscription has expired.',
            'cancelled' => 'Your subscription has been cancelled.',
            'pending' => 'Your subscription request has been submitted.',
            default => 'Your subscription has been updated.',
        };

        $this->dispatch('toast', message: $message, type: 'info');
    }

    public function viewInvoice(int $invoiceId): void
    {
        $this->previewInvoice = Invoice::query()
            ->where('user_id', auth()->id())
            ->with('items')
            ->findOrFail($invoiceId);
    }

    public function closeInvoice(): void
    {
        $this->previewInvoice = null;
    }

    public function subscriptions()
    {
        return ToolSubscription::query()
            ->with(['toolCategory', 'toolPlan', 'invoices'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
    }
};
?>

<div x-data="{
    sidebarOpen: false,
    showToast: {{ session('success') ? 'true' : 'false' }}
}" class="relative min-h-screen text-white">
    @if (session('success'))
        <div x-show="showToast" x-init="setTimeout(() => showToast = false, 4500)" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="fixed right-5 top-5 z-100 max-w-md rounded-2xl border border-emerald-300/20 bg-emerald-500/15 p-4 text-emerald-50 shadow-[0_18px_60px_rgba(0,0,0,0.35)] backdrop-blur-2xl">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-emerald-300/20 bg-emerald-400/15 text-emerald-200">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>

                <div class="min-w-0">
                    <p class="font-bold text-white">Success</p>
                    <p class="mt-1 text-sm leading-6 text-emerald-50/80">
                        {{ session('success') }}
                    </p>
                </div>

                <button type="button" @click="showToast = false"
                    class="ml-2 rounded-xl p-1 text-emerald-100/70 transition hover:bg-white/10 hover:text-white">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>
    @endif

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                <livewire:shared.user-sidebar />

                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    <div class="mb-8">
                        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
                            My
                            <span
                                class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">Subscriptions</span>
                        </h1>
                        <p class="mt-2 text-blue-100/60">Manage your active and past tool subscriptions.</p>
                    </div>

                    <div wire:key="subscription-list-{{ $refreshKey }}" class="space-y-4">
                        @forelse ($this->subscriptions() as $sub)
                            <div
                                class="rounded-2xl border border-white/15 bg-white/[0.07] p-4 shadow-[0_10px_30px_rgba(0,0,0,0.12)] backdrop-blur-2xl">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300">
                                            <span
                                                class="material-symbols-outlined text-xl">{{ $sub->toolCategory?->icon ?: 'build' }}</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-base font-bold text-white">
                                                    {{ $sub->toolCategory?->name ?? 'Unknown' }}</h3>
                                                <span @class([
                                                    'text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full',
                                                    'bg-emerald-500/15 text-emerald-300' => $sub->status === 'active',
                                                    'bg-amber-500/15 text-amber-300' => $sub->status === 'pending',
                                                    'bg-red-500/15 text-red-300' =>
                                                        $sub->status === 'expired' || $sub->status === 'cancelled',
                                                ])>
                                                    {{ $sub->status }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-blue-100/50">
                                                {{ $sub->toolPlan?->name ?? 'No plan' }} ·
                                                {{ ucfirst($sub->billing_cycle) }}</p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <p class="text-lg font-bold text-white">
                                                ৳{{ number_format((float) $sub->amount, 2) }}</p>

                                            @if ($sub->toolPlan && ($sub->status === 'expired' || $sub->status === 'cancelled' || ($sub->status === 'active' && $sub->expires_at && $sub->expires_at->isPast())))
                                                <a href="{{ route('client.tool-subscriptions.checkout', $sub->toolPlan) }}"
                                                    wire:navigate
                                                    class="inline-flex items-center gap-1 rounded-full border border-cyan-300/30 bg-cyan-400/10 px-3 py-1 text-xs font-bold text-cyan-200 transition hover:border-cyan-300/40 hover:bg-cyan-400/20">
                                                    <span class="material-symbols-outlined text-sm">autorenew</span>
                                                    Renew
                                                </a>
                                            @endif
                                        </div>

                                        @if ($sub->status === 'active' && $sub->expires_at && $sub->expires_at->isPast())
                                            <p class="text-xs text-red-300/70">Expired {{ $sub->expires_at->format('M d, Y') }}</p>
                                        @elseif ($sub->expires_at)
                                            <p class="text-xs text-blue-100/45">
                                                @if ($sub->status === 'active')
                                                    Expires {{ $sub->expires_at->format('M d, Y') }}
                                                @elseif ($sub->status === 'pending')
                                                    Pending verification
                                                @else
                                                    {{ $sub->expires_at->format('M d, Y') }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($sub->transaction_id)
                                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-blue-100/45">
                                        <span>TrxID: <span
                                                class="font-mono text-cyan-300/70">{{ $sub->transaction_id }}</span></span>
                                        @if ($sub->verified_at)
                                            <span>Verified:
                                                {{ $sub->verified_at->format('M d, Y h:i A') }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if ($sub->invoices->isNotEmpty())
                                    <div class="mt-3 space-y-2 pt-2">
                                        @if ($sub->invoices->count() > 1)
                                            <p class="text-[11px] font-bold uppercase tracking-widest text-blue-100/40">
                                                Invoices ({{ $sub->invoices->count() }})</p>
                                        @endif

                                        @foreach ($sub->invoices as $invoice)
                                            <div
                                                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-white">{{ $invoice->invoice_no }}</p>
                                                    <p class="text-[11px] text-blue-100/45">
                                                        {{ $invoice->issue_date?->format('M d, Y') }}
                                                        · ৳{{ number_format((float) $invoice->total(), 2) }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        wire:click="viewInvoice({{ $invoice->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/10 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:border-white/20 hover:bg-white/15">
                                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                                        View
                                                    </button>
                                                    <a href="{{ route('account.tool-subscription.invoice.download', $invoice) }}"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-cyan-300/20 bg-cyan-400/10 px-2.5 py-1.5 text-[11px] font-bold text-cyan-200 transition hover:border-cyan-300/40 hover:bg-cyan-400/20">
                                                        <span class="material-symbols-outlined text-sm">receipt_long</span>
                                                        Download
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div
                                class="rounded-2xl border border-white/15 bg-white/[0.07] p-12 text-center backdrop-blur-2xl">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/8">
                                    <span
                                        class="material-symbols-outlined text-3xl text-blue-100/40">subscriptions</span>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">No subscriptions yet</h3>
                                <p class="mt-2 text-sm text-blue-100/50">Subscribe to a plan to unlock premium
                                    features.</p>
                                <a href="{{ route('client.tools.index') }}" wire:navigate
                                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3 font-semibold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5">
                                    <span class="material-symbols-outlined text-base">workspace_premium</span>
                                    Browse Tools
                                </a>
                            </div>
                        @endforelse

                        <div class="mt-4">
                            {{ $this->subscriptions()->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        @if ($previewInvoice)
            <div class="fixed inset-0 z-100 flex items-center justify-center p-4"
                x-data="{ open: true }"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false" wire:click="closeInvoice"></div>

                <div
                    class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-3xl border border-white/10 bg-[#0b1020] shadow-[0_25px_100px_rgba(0,0,0,0.6)]">
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-cyan-300">receipt_long</span>
                            <h3 class="font-bold text-white">Invoice Preview</h3>
                        </div>
                        <button type="button" @click="open = false" wire:click="closeInvoice"
                            class="rounded-xl p-1.5 text-slate-300 transition hover:bg-white/10 hover:text-white">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>

                    <div class="notification-scroll max-h-[calc(90vh-4rem)] overflow-y-auto p-6">
                        @php
                            $settings = \App\Models\SiteSetting::current();
                            $previewSubtotal = $previewInvoice->subtotal();
                            $previewDiscount = $previewInvoice->discountAmount();
                            $previewTotal = $previewInvoice->total();
                        @endphp

                        <div class="flex flex-wrap items-start justify-between gap-6">
                            <div>
                                <h1 class="text-2xl font-black uppercase tracking-tight text-white">{{ $settings?->site_name ?? config('app.name') }}</h1>
                                <div class="mt-2 space-y-0.5 text-xs leading-6 text-slate-400">
                                    @if ($settings?->location)
                                        <div>{{ $settings->location }}</div>
                                    @endif
                                    @if ($settings?->email)
                                        <div>{{ $settings->email }}</div>
                                    @endif
                                    @if ($settings?->phone)
                                        <div>{{ $settings->phone }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right">
                                <h2 class="text-xl font-black uppercase tracking-widest text-cyan-300">Invoice</h2>
                                <div class="mt-2 space-y-0.5 text-xs text-slate-400">
                                    <div><span class="text-slate-500">Invoice #: </span><span class="font-bold text-white">{{ $previewInvoice->invoice_no }}</span></div>
                                    <div><span class="text-slate-500">Date Issued: </span><span class="font-semibold text-white">{{ $previewInvoice->issue_date?->format('M d, Y') ?: $previewInvoice->created_at?->format('M d, Y') }}</span></div>
                                    <div>
                                        <span class="text-slate-500">Status: </span>
                                        @php
                                            $invoiceStatus = $previewInvoice->status ? ucfirst($previewInvoice->status) : 'Unpaid';
                                        @endphp
                                        <span @class([
                                            'font-bold',
                                            'text-emerald-300' => strtolower($previewInvoice->status ?? '') === 'paid',
                                            'text-amber-300' => strtolower($previewInvoice->status ?? '') === 'pending',
                                            'text-rose-300' => in_array(strtolower($previewInvoice->status ?? ''), ['unpaid', 'overdue', 'expired', 'cancelled'], true),
                                            'text-white' => ! $previewInvoice->status,
                                        ])>{{ $invoiceStatus }}</span>
                                    </div>
                                    @if ($previewInvoice->due_date)
                                        <div><span class="text-slate-500">Due Date: </span><span class="font-semibold text-white">{{ $previewInvoice->due_date->format('M d, Y') }}</span></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-6 rounded-2xl border border-white/10 bg-white/4 p-5 sm:grid-cols-2">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Bill To</p>
                                <div class="mt-2 space-y-0.5 text-sm leading-6 text-slate-300">
                                    <div class="text-base font-bold text-white">{{ $previewInvoice->customer_name }}</div>
                                    @if ($previewInvoice->customer_email)
                                        <div>{{ $previewInvoice->customer_email }}</div>
                                    @endif
                                    @if ($previewInvoice->customer_phone)
                                        <div>{{ $previewInvoice->customer_phone }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="sm:border-l sm:border-white/10 sm:pl-6">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Pay To</p>
                                <div class="mt-2 space-y-0.5 text-sm leading-6 text-slate-300">
                                    <div class="text-base font-bold text-white">{{ $settings?->site_name ?? 'Our Company' }}</div>
                                    @if ($settings?->location)
                                        <div>{{ $settings->location }}</div>
                                    @endif
                                    @if ($settings?->phone)
                                        <div>{{ $settings->phone }}</div>
                                    @endif
                                    @if ($settings?->email)
                                        <div>{{ $settings->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-cyan-400/10 text-xs font-bold uppercase tracking-wider text-cyan-200">
                                        <th class="px-4 py-3">Service</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Unit Price</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-white/4">
                                    @foreach ($previewInvoice->items as $item)
                                        @php $lineTotal = (float) $item->quantity * (float) $item->unit_price; @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-white">{{ $item->title }}</div>
                                                @if ($item->description)
                                                    <div class="mt-1 text-xs leading-5 text-slate-400">{{ $item->description }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center text-slate-300">{{ number_format((int) $item->quantity) }}</td>
                                            <td class="px-4 py-3 text-right text-slate-300">৳{{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td class="px-4 py-3 text-right font-bold text-white">৳{{ number_format($lineTotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <div class="w-full max-w-xs space-y-2 text-sm">
                                <div class="flex justify-between text-slate-400">
                                    <span>Subtotal</span>
                                    <span class="font-semibold text-white">৳{{ number_format($previewSubtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-400">
                                    <span>Discount</span>
                                    <span class="font-semibold text-rose-300">-৳{{ number_format($previewDiscount, 2) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-white/10 pt-3">
                                    <span class="text-[11px] font-black uppercase tracking-widest text-cyan-300">Total Amount</span>
                                    <span class="text-lg font-black text-cyan-300">৳{{ number_format($previewTotal, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-end justify-between gap-6 border-t border-white/10 pt-5">
                            <div class="max-w-xs text-xs leading-5 text-slate-500">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-widest">Terms</span>
                                {{ $previewInvoice->terms ?? 'Thank you for your business.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
