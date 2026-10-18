@component('layouts.app', ['title' => 'Payment status'])
@php
$paid = $payment->status === 'paid';
$confirmed = $paid && !$payment->needs_review;
$pending = $payment->status === 'pending';
$order = $servicePurchase && $confirmed ? $payment->payable?->order : null;
@endphp
<section class="px-4 py-10 text-white sm:py-14">
    <div class="mx-auto max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-slate-950/80 shadow-2xl shadow-black/20 backdrop-blur-xl">
        <div class="px-6 pb-7 pt-8 text-center sm:px-8">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full {{ $confirmed ? 'bg-cyan-300/10 text-cyan-300 ring-8 ring-cyan-300/5' : ($paid || $pending ? 'bg-amber-300/10 text-amber-200 ring-8 ring-amber-300/5' : 'bg-rose-300/10 text-rose-200 ring-8 ring-rose-300/5') }}">
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    @if ($confirmed)
                    <path d="m5 12 4 4L19 6" />
                    @elseif ($paid || $pending)
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                    @else
                    <circle cx="12" cy="12" r="9" />
                    <path d="m9 9 6 6m0-6-6 6" />
                    @endif
                </svg>
            </div>
            <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-slate-400">{{ $confirmed ? 'Thank you for your purchase' : 'SSLCommerz payment' }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ $confirmed ? 'Payment successful' : ($paid ? 'Payment received' : ($pending ? 'Payment pending' : 'Payment not completed')) }}</h1>
            <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-400">
                @if ($payment->needs_review)
                Your payment was received. Please contact support to complete your purchase. You do not need to pay again.
                @elseif ($confirmed)
                {{ $servicePurchase ? 'Your service order is active and your paid invoice is ready.' : 'Your subscription is active. You’re ready to start using your tools.' }}
                @elseif ($pending)
                We’re waiting for payment confirmation. If you’ve already paid, please wait before trying again.
                @else
                {{ $payment->status === 'cancelled' ? 'Checkout was cancelled. You can try again whenever you’re ready.' : 'We couldn’t complete your payment. Please try again.' }}
                @endif
            </p>
        </div>
        <div class="mx-6 rounded-2xl border border-white/10 bg-white/[0.03] sm:mx-8">
            <div class="flex items-center justify-between gap-4 border-b border-dashed border-white/10 px-5 py-4">
                <span class="text-sm text-slate-400">{{ $paid ? 'Amount paid' : 'Total amount' }}</span>
                <span class="text-xl font-bold tracking-tight"><span class="mr-1 text-xs font-medium text-slate-400">{{ $payment->currency }}</span>{{ number_format((float) $payment->amount, 2) }}</span>
            </div>
            <dl class="space-y-3 px-5 py-4 text-sm">
                @if ($order)
                <div class="flex items-start justify-between gap-5">
                    <dt class="shrink-0 text-slate-400">Order number</dt>
                    <dd class="break-all text-right font-medium text-slate-200">{{ $order->order_no }}</dd>
                </div>
                @endif
                <div class="flex items-start justify-between gap-5">
                    <dt class="shrink-0 text-slate-400">Payment method</dt>
                    <dd class="font-medium text-slate-200">SSLCommerz</dd>
                </div>
                <div class="flex items-start justify-between gap-5">
                    <dt class="shrink-0 text-slate-400">Reference</dt>
                    <dd class="min-w-0 break-all text-right font-mono text-xs leading-5 text-slate-300">{{ $payment->transaction_id }}</dd>
                </div>
            </dl>
        </div>
        <div class="px-6 pb-7 pt-6 sm:px-8">
            @if (session('error'))
            <p role="alert" class="mb-4 rounded-xl bg-amber-300/10 px-4 py-3 text-sm text-amber-200">{{ session('error') }}</p>
            @endif
            @if ($errors->any())
            <ul role="alert" class="mb-4 list-inside list-disc rounded-xl bg-rose-300/10 px-4 py-3 text-sm text-rose-200">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            @endif
            @if (!$paid && $payment->payable && app(\App\Services\SslCommerzSettings::class)->available())
            <form method="POST" action="{{ route('purchases.retry', $payment) }}" class="mb-3">
                @csrf
                <button type="submit" class="w-full cursor-pointer rounded-xl bg-cyan-300 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300">{{ $pending ? 'Continue secure checkout' : 'Try payment again' }}</button>
            </form>
            @endif
            <div class="grid gap-3 {{ $confirmed ? 'sm:grid-cols-2' : '' }}">
                <a href="{{ route($servicePurchase ? 'account.services' : 'account.tool-subscriptions') }}" wire:navigate class="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300 {{ $confirmed ? 'bg-cyan-300 text-slate-950 hover:bg-cyan-200' : 'border border-white/10 text-slate-200 hover:bg-white/5' }}">
                    {{ $servicePurchase ? 'View my services' : 'View my subscriptions' }}
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </a>
                @if ($confirmed)
                <a href="{{ route('account.invoices') }}" wire:navigate class="flex items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8m-8 4h5" />
                    </svg>
                    View invoice
                </a>
                @endif
            </div>
            <p class="mt-5 text-center text-xs text-slate-500">{{ $confirmed ? 'A confirmation will be sent to your email.' : 'Secure payments powered by SSLCommerz' }}</p>
        </div>
    </div>
</section>
@endcomponent