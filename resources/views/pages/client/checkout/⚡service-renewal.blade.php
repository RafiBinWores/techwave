<?php

use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Service Renewal Checkout')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === Auth::id() && $order->order_type === 'service' && $order->renewal_of_id, 404);
        $this->order = $order->load(['user', 'service', 'servicePlan', 'renewalOf']);

        foreach (['success', 'error'] as $type) {
            if (session()->has($type)) {
                $this->dispatch('toast', message: session()->pull($type), type: $type);
            }
        }
    }
};
?>

<div>
    @php
        $settings = SiteSetting::current();
        $paid = $order->payment_status === 'paid';
        $cancelled = $order->status === 'cancelled' || $order->renewalOf?->status === 'cancelled';
        $reviewPending = !$paid && $order->renewal_manual_submitted_at !== null;
        $payable = !$paid && !$cancelled && !$reviewPending;
        $manualAvailable = filled($settings->bkash_number) && $order->currency === 'BDT';
        $inputClass = 'w-full rounded-2xl border border-blue-100/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15';
        $title = $order->plan_name ?: ($order->servicePlan?->name ?: ($order->service?->card_title ?: 'Renew your service'));
    @endphp
    <section class="min-h-screen pb-10 text-white">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('account.services') }}" wire:navigate class="my-8 inline-flex items-center gap-2 text-sm text-blue-100/65 transition hover:text-cyan-200">
                <span class="material-symbols-outlined text-lg">arrow_back</span> Back to my services
            </a>
            <div class="mb-10 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-300">Service renewal</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl lg:text-5xl">{{ $title }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-blue-100/60 sm:text-base">Continue your service with a {{ $order->billing_cycle }} renewal. Choose your preferred payment method below.</p>
            </div>
            @if ($errors->any())
                <ul role="alert" class="mb-6 list-inside list-disc space-y-1 rounded-2xl border border-red-300/20 bg-red-400/10 p-4 text-sm text-red-100">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('client.renewals.pay', $order) }}" x-data="{ paymentMethod: @js(old('payment_method', 'sslcommerz')), submitting: false }" @submit="submitting = true">
                @csrf
                <div class="grid gap-8 lg:grid-cols-[1fr_420px]">
                    <div class="min-w-0 space-y-6">
                        <div class="rounded-3xl border border-blue-100/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                            <div class="mb-6 flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">calendar_month</span></div>
                                <div><h2 class="text-xl font-bold">{{ $paid ? 'Payment confirmed' : ($cancelled ? 'Renewal cancelled' : ($reviewPending ? 'Awaiting payment verification' : 'Renewal details')) }}</h2><p class="mt-1 text-sm text-blue-100/55">{{ $order->order_no }}</p></div>
                            </div>
                            @if ($paid)
                                <p class="text-sm leading-6 text-emerald-200">Service period: {{ $order->start_date?->format('d M Y') }} – {{ $order->end_date?->format('d M Y') }}.</p>
                            @elseif ($cancelled)
                                <p class="text-sm text-red-200">This renewal has been cancelled. Contact the team if you need help.</p>
                            @elseif ($reviewPending)
                                <p class="text-sm leading-6 text-blue-100/75">Your manual payment details have been received. The team will verify the transfer before activating your renewal. Please do not pay again.</p>
                                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                                    <div><dt class="text-blue-100/55">Sending number</dt><dd class="mt-1 font-medium">{{ $order->renewal_sender_bkash }}</dd></div>
                                    <div><dt class="text-blue-100/55">Transaction ID</dt><dd class="mt-1 break-all font-mono">{{ $order->renewal_manual_transaction_id }}</dd></div>
                                </dl>
                            @else
                                <div class="rounded-2xl border border-cyan-300/20 bg-cyan-300/5 p-4"><p class="font-semibold text-cyan-200">{{ ucfirst($order->billing_cycle) }} subscription</p><p class="mt-2 text-sm leading-6 text-blue-100/65">If your service is still active, the new period starts after your current paid period. If it has expired, the new period starts when payment is confirmed.</p></div>
                            @endif
                        </div>
                        @if ($payable)
                            <div class="rounded-3xl border border-blue-100/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                                <div class="mb-6 flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">person</span></div>
                                    <div><h2 class="text-xl font-bold">Your information</h2><p class="mt-1 text-sm text-blue-100/55">Check your contact and billing details.</p></div>
                                </div>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div><label for="renewal-name" class="mb-2 block text-sm text-blue-100/80">Full name</label><input id="renewal-name" name="customer_name" required maxlength="255" autocomplete="name" value="{{ old('customer_name', $order->full_name ?: $order->user?->name) }}" class="{{ $inputClass }}"></div>
                                    <div><label for="renewal-phone" class="mb-2 block text-sm text-blue-100/80">Phone number</label><input id="renewal-phone" type="tel" name="customer_phone" required maxlength="30" autocomplete="tel" value="{{ old('customer_phone', $order->phone ?: $order->user?->phone) }}" class="{{ $inputClass }}"></div>
                                    <div class="sm:col-span-2"><label for="renewal-email" class="mb-2 block text-sm text-blue-100/80">Email address</label><input id="renewal-email" type="email" name="customer_email" required maxlength="255" autocomplete="email" value="{{ old('customer_email', $order->email ?: $order->user?->email) }}" class="{{ $inputClass }}"></div>
                                    <div class="sm:col-span-2"><label for="renewal-address" class="mb-2 block text-sm text-blue-100/80">Billing address</label><input id="renewal-address" name="customer_address" required maxlength="500" autocomplete="street-address" value="{{ old('customer_address') }}" class="{{ $inputClass }}"></div>
                                </div>
                            </div>
                            <div class="rounded-3xl border border-blue-100/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                                <div class="mb-6 flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">payments</span></div>
                                    <div><h2 class="text-xl font-bold">Payment method</h2><p class="mt-1 text-sm text-blue-100/55">Select how you would like to pay.</p></div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-5 transition" :class="paymentMethod === 'sslcommerz' ? 'border-cyan-300/60 bg-cyan-300/10' : 'border-blue-100/10 bg-white/5'">
                                        <input type="radio" name="payment_method" value="sslcommerz" x-model="paymentMethod" class="mt-1 accent-cyan-300" @checked(old('payment_method', 'sslcommerz') === 'sslcommerz')>
                                        <span><span class="block font-semibold">SSLCommerz</span><span class="mt-1 block text-xs leading-5 text-blue-100/55">Cards, mobile banking and online payments.</span></span>
                                    </label>
                                    <label class="flex items-start gap-3 rounded-2xl border p-5 transition {{ $manualAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}" :class="paymentMethod === 'manual' ? 'border-cyan-300/60 bg-cyan-300/10' : 'border-blue-100/10 bg-white/5'">
                                        <input type="radio" name="payment_method" value="manual" x-model="paymentMethod" class="mt-1 accent-cyan-300" @disabled(!$manualAvailable) @checked(old('payment_method') === 'manual')>
                                        <span><span class="block font-semibold">Manual bKash</span><span class="mt-1 block text-xs leading-5 text-blue-100/55">{{ $manualAvailable ? 'Send money and submit your transaction details.' : 'Currently unavailable. Contact the team for help.' }}</span></span>
                                    </label>
                                </div>
                                <div x-show="paymentMethod === 'sslcommerz'" class="mt-6 rounded-2xl border border-blue-100/10 bg-white/5 p-5 text-sm leading-6 text-blue-100/65">You will be redirected to SSLCommerz to complete your payment securely. Your renewal activates after payment verification.</div>
                                @if ($manualAvailable)
                                    <div x-show="paymentMethod === 'manual'" x-cloak class="mt-6 space-y-5">
                                        <div class="overflow-hidden rounded-2xl border border-cyan-400/15 bg-cyan-400/5 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-cyan-200">Send Money To</p><p class="mt-2 text-2xl font-bold tracking-wide">{{ $settings->bkash_number }}</p>
                                            <p class="mt-3 text-sm text-blue-100/70">Send exactly <strong class="text-white">{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</strong>.</p>
                                            @if ($settings->bkash_instructions)<p class="mt-4 whitespace-pre-line text-sm leading-6 text-blue-100/65">{{ $settings->bkash_instructions }}</p>@endif
                                        </div>
                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div><label for="sender-bkash" class="mb-2 block text-sm text-blue-100/80">Sending bKash number</label><input id="sender-bkash" type="tel" name="sender_bkash" maxlength="20" placeholder="01XXXXXXXXX" value="{{ old('sender_bkash') }}" :required="paymentMethod === 'manual'" :disabled="paymentMethod !== 'manual'" class="{{ $inputClass }}"></div>
                                            <div><label for="transaction-id" class="mb-2 block text-sm text-blue-100/80">Transaction ID (TrxID)</label><input id="transaction-id" name="transaction_id" maxlength="50" placeholder="Enter your TrxID" value="{{ old('transaction_id') }}" :required="paymentMethod === 'manual'" :disabled="paymentMethod !== 'manual'" class="{{ $inputClass }}"></div>
                                        </div>
                                        <p class="text-xs leading-5 text-amber-200/80">Manual payments require verification by our team. Your service period is confirmed after approval.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <aside class="lg:sticky lg:top-24 lg:self-start">
                        <div class="rounded-3xl border border-blue-100/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                            <div class="mb-6 flex items-center gap-3"><span class="material-symbols-outlined text-cyan-200">receipt_long</span><h2 class="text-xl font-bold">Order Summary</h2></div>
                            <p class="text-lg font-semibold">{{ $title }}</p><p class="mt-2 text-sm text-blue-100/55">{{ $order->service?->card_title }} · {{ ucfirst($order->billing_cycle) }} renewal</p>
                            <dl class="my-6 space-y-4 border-y border-blue-100/10 py-6 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-blue-100/55">Renewal amount</dt><dd>{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-blue-100/55">Payment status</dt><dd class="{{ $paid ? 'text-emerald-200' : 'text-amber-200' }}">{{ $paid ? 'Paid' : ($reviewPending ? 'Under review' : 'Awaiting payment') }}</dd></div>
                            </dl>
                            <div class="flex items-center justify-between gap-4"><span class="font-medium">{{ $paid ? 'Total paid' : 'Total to pay' }}</span><span class="text-2xl font-bold text-cyan-200">{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</span></div>
                            @if ($payable)
                                <button type="submit" :disabled="submitting" class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-cyan-300 px-5 py-4 font-bold text-slate-950 transition hover:bg-cyan-200 disabled:cursor-wait disabled:opacity-60">
                                    <span x-text="submitting ? 'Processing…' : (paymentMethod === 'manual' ? 'Submit payment details' : 'Pay securely')">Pay securely</span><span class="material-symbols-outlined text-xl">arrow_forward</span>
                                </button>
                                <p class="mt-4 text-center text-xs leading-5 text-blue-100/45">Your renewal dates are set once payment is confirmed.</p>
                            @else
                                <a href="{{ route('account.services') }}" wire:navigate class="mt-6 block rounded-2xl border border-blue-100/15 px-5 py-3 text-center text-sm font-semibold text-blue-100 transition hover:bg-white/10">Back to my services</a>
                            @endif
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </section>
</div>
