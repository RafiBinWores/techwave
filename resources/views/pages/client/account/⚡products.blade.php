<?php

use App\Models\Invoice;
use App\Models\NetworkLicenseTrial;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Services\NetworkLicensing;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('My Products')] class extends Component
{
    use WithPagination;

    public ?Invoice $previewInvoice = null;

    #[Locked]
    public string $networkActivationKey = '';

    public function generateLicenseKey(int $purchaseId): void
    {
        $purchase = ProductPurchase::where('user_id', auth()->id())->findOrFail($purchaseId);
        $this->networkActivationKey = app(NetworkLicensing::class)->keyForPurchase($purchase);
    }

    public function closeLicenseKey(): void
    {
        $this->networkActivationKey = '';
    }

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function viewInvoice(int $invoiceId): void
    {
        // Invoice previews are scoped to the signed-in customer, just like tool subscriptions.
        $this->previewInvoice = Invoice::where('user_id', auth()->id())->with('items')->findOrFail($invoiceId);
    }

    public function closeInvoice(): void
    {
        $this->previewInvoice = null;
    }

    public function with(): array
    {
        return ['purchases' => ProductPurchase::where('user_id', auth()->id())
            ->with(['product', 'plan', 'invoice.items', 'onlinePayments' => fn ($query) => $query->latest()])->latest()->paginate(10),
            'trial' => NetworkLicenseTrial::with(['product', 'license'])->where('user_id', auth()->id())->first(),
            'licensedProductIds' => Product::get(['id', 'name', 'slug'])->filter(fn ($product) => app(NetworkLicensing::class)->isProduct($product))->pluck('id')->all(),
            'pendingProductIds' => ProductPurchase::where('user_id', auth()->id())->where('status', 'pending')->pluck('product_id')->all(),
            'renewalCandidateIds' => ProductPurchase::where('user_id', auth()->id())->whereNotNull('invoice_id')
                ->whereIn('status', ['active', 'expired'])->selectRaw('MAX(id) as id')->groupBy('product_id')
                ->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="px-4 py-6 text-white sm:px-6">
    <div class="mx-auto max-w-350 overflow-hidden rounded-2xl border border-white/10 bg-white/6 shadow-2xl backdrop-blur-2xl">
        <div class="flex min-h-[calc(100vh-3rem)]">
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>
            <livewire:shared.user-sidebar />
            <main class="min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
                <button type="button" @click="sidebarOpen = true" aria-label="Open account menu"
                    class="rounded-xl border border-white/10 bg-white/8 p-2 lg:hidden">
                    <span class="material-symbols-outlined">menu</span>
                </button>
    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">My Products</h1>
        <p class="mt-2 text-sm text-blue-100/60">Your software subscriptions and one-time purchases.</p>
    </div>
    @if ($trial && !$trial->license?->product_purchase_id)
        <article class="rounded-2xl border border-cyan-300/20 bg-cyan-300/5 p-5 shadow-xl backdrop-blur-2xl">
            <span class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-xs text-cyan-200">7-day free trial</span>
            <h2 class="mt-4 text-xl font-bold">{{ $trial->product?->name ?? 'NetWatch' }}</h2>
            <p class="mt-2 text-sm text-blue-100/65">Unlimited sensors · One installation</p>
            <p class="mt-2 text-sm text-blue-100/65">{{ $trial->expires_at->isFuture() ? 'Trial expires' : 'Trial expired' }} {{ $trial->expires_at->format('d M Y, h:i A T') }}</p>
            @if ($trial->license?->revoked_at)
                <p class="mt-3 text-sm text-red-200">Your license is revoked. Contact support.</p>
            @endif
            @if ($trial->product?->is_published)
                <div class="mt-5 flex flex-wrap gap-3">
                    @if ($trial->expires_at->isFuture() && !$trial->license?->revoked_at)
                        <a href="{{ route('client.products.download.setup', $trial->product->slug) }}" wire:navigate class="rounded-xl border border-white/10 px-4 py-2 text-sm text-cyan-200">Activation key & download</a>
                    @endif
                    <a href="{{ route('client.products.show', $trial->product->slug) }}#pricing" wire:navigate class="rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-2 text-sm font-semibold">Choose a paid plan</a>
                </div>
            @endif
        </article>
    @endif
    @error('license')
        <p role="alert" class="rounded-xl border border-red-300/20 bg-red-400/10 p-4 text-sm text-red-200">{{ $message }}</p>
    @enderror
    @forelse ($purchases as $purchase)
        <article wire:key="product-purchase-{{ $purchase->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-xl shadow-blue-950/20 backdrop-blur-2xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row">
                <div>
                    <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs capitalize text-cyan-200">{{ $purchase->displayStatus() }}</span>
                    <h2 class="mt-4 text-xl font-bold">{{ $purchase->product_name }}</h2>
                    <p class="mt-1 text-sm text-blue-100/70">{{ $purchase->plan_name }} · {{ $purchase->billing_cycle === 'one_time' ? 'One-time purchase' : ucfirst($purchase->billing_cycle) }}</p>
                    @if ($purchase->renewal_of_id)
                        <p class="mt-2 text-xs text-blue-100/50">Renewal of purchase #{{ $purchase->renewal_of_id }}</p>
                    @endif
                    <p class="mt-2 text-xs text-blue-100/50">{{ $purchase->invoice?->invoice_no ?? 'Purchase #'.$purchase->id }} · {{ $purchase->created_at->format('d M Y') }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-xl font-bold text-cyan-200">৳{{ number_format((float) $purchase->amount, 2) }}</p>
                    @if ($purchase->status === 'pending')
                        <p class="mt-2 text-xs text-amber-200">{{ $purchase->payment_method === 'manual' ? 'Awaiting bKash verification' : 'Awaiting online payment' }}</p>
                    @elseif ($purchase->expires_at)
                        <p class="mt-2 text-xs text-blue-100/60">{{ $purchase->status === 'renewed' ? 'Previous expiry' : ($purchase->displayStatus() === 'expired' ? 'Expired' : 'Expires') }} {{ $purchase->expires_at->format('d M Y') }}</p>
                    @elseif ($purchase->status === 'active')
                        <p class="mt-2 text-xs text-blue-100/60">No subscription expiry</p>
                    @endif
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-3 border-t border-white/10 pt-4 text-sm">
                @if ($purchase->displayStatus() === 'active' && $purchase->billing_cycle !== 'one_time' && in_array((int) $purchase->product_id, $licensedProductIds, true))
                    <button type="button" wire:click="generateLicenseKey({{ $purchase->id }})"
                        wire:confirm="Generate a new NetWatch activation key? Older keys stop working for new activations. Existing installations keep their credentials."
                        class="rounded-xl bg-cyan-300/10 px-4 py-2 text-cyan-200">Activation key</button>
                @endif
                @if ($purchase->product?->is_published && !$purchase->product->trashed())
                    <a href="{{ route('client.products.show', $purchase->product->slug) }}" wire:navigate class="rounded-xl border border-white/10 px-4 py-2 text-blue-100">View product</a>
                @endif
                @if ($purchase->invoice)
                    <div class="flex w-full flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/4 px-3 py-2">
                        <div>
                            <p class="text-xs font-bold text-white">{{ $purchase->invoice->invoice_no }}</p>
                            <p class="mt-1 text-xs text-blue-100/45">{{ $purchase->invoice->issue_date?->format('M d, Y') }} · ৳{{ number_format((float) $purchase->invoice->total(), 2) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="viewInvoice({{ $purchase->invoice_id }})"
                                class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/10 px-2.5 py-1.5 text-xs font-bold text-white transition hover:bg-white/15">
                                <span class="material-symbols-outlined text-sm">visibility</span>View
                            </button>
                            <a href="{{ route('account.products.invoice.download', $purchase->invoice) }}"
                                class="inline-flex items-center gap-1 rounded-lg border border-cyan-300/20 bg-cyan-400/10 px-2.5 py-1.5 text-xs font-bold text-cyan-200 transition hover:bg-cyan-400/20">
                                <span class="material-symbols-outlined text-sm">receipt_long</span>Download
                            </a>
                        </div>
                    </div>
                @endif
                @if ($purchase->payment_method === 'sslcommerz' && $purchase->status === 'pending' && !$purchase->onlinePayments->first() && $purchase->product?->is_published && $purchase->product_plan_id)
                    <a href="{{ route('client.products.purchase', ['slug' => $purchase->product->slug, 'plan' => $purchase->product_plan_id, 'cycle' => $purchase->billing_cycle, 'renew' => $purchase->renewal_of_id]) }}" wire:navigate class="rounded-xl bg-blue-500/20 px-4 py-2 text-blue-100">Continue checkout</a>
                @endif
                @if ($purchase->payment_method === 'sslcommerz' && $purchase->status === 'pending' && $purchase->onlinePayments->first())
                    <a href="{{ route('purchases.show', $purchase->onlinePayments->first()) }}" wire:navigate class="rounded-xl bg-blue-500/20 px-4 py-2 text-blue-100">Payment status</a>
                @endif
                @if (in_array($purchase->id, $renewalCandidateIds, true) && ($renewalUrl = $purchase->renewalUrl()))
                    @if (in_array($purchase->product_id, $pendingProductIds, true))
                        <span class="rounded-xl border border-amber-300/20 bg-amber-300/10 px-4 py-2 text-amber-200">Renewal awaiting payment verification</span>
                    @else
                        <a href="{{ $renewalUrl }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-2 font-semibold">
                            <span class="material-symbols-outlined text-lg">autorenew</span>Renew subscription
                        </a>
                    @endif
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur-2xl">
            <p class="text-blue-100/65">{{ $trial ? 'Your trial is shown above. Choose a paid plan to continue after it expires.' : 'You have no product purchases yet.' }}</p>
            <a href="{{ route('client.products.index') }}" wire:navigate class="mt-5 inline-flex rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold">Explore products</a>
        </div>
    @endforelse
    {{ $purchases->links() }}
        @if ($previewInvoice)
        @teleport('body')
        <div role="dialog" aria-modal="true" aria-label="Invoice Preview" @keydown.escape.window="$wire.closeInvoice()" class="fixed inset-0 z-100 flex items-center justify-center p-4"
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
                                    <span @class([ 'font-bold' , 'text-emerald-300'=> strtolower($previewInvoice->status ?? '') === 'paid',
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
                                    <th class="px-4 py-3">Product</th>
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
        @endteleport
        @endif
            </main>
        </div>
    </div>

    @if ($networkActivationKey)
        <dialog wire:ignore.self x-data x-init="$el.showModal()" @cancel.prevent="$wire.closeLicenseKey()"
            class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-white/15 bg-slate-950/90 p-6 text-white shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xl">
            <h2 class="text-xl font-bold">Activation key</h2>
            <p class="mt-3 text-sm leading-7 text-blue-100/65">Copy this key and enter it in NetWatch on your computer. We only store its hash, so it cannot be displayed again.</p>
            <code class="mt-4 block break-all rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-cyan-200">{{ $networkActivationKey }}</code>
            <div class="mt-5 text-right"><button type="button" wire:click="closeLicenseKey" class="rounded-xl bg-blue-500 px-4 py-2 text-sm font-semibold">Done</button></div>
        </dialog>
    @endif
</div>
