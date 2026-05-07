<div>
    <section class="min-h-screen bg-slate-950 px-4 py-20 text-white">
        <div class="mx-auto max-w-2xl rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur-xl">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
            </div>

            <h1 class="mt-6 text-3xl font-bold">
                Payment Successful
            </h1>

            <p class="mt-3 text-blue-100/70">
                Your {{ $order->pricingPlan?->title ?? 'selected' }} plan has been purchased successfully.
            </p>

            <div class="mt-8 rounded-2xl bg-white/5 p-5 text-left text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-blue-100/60">Order No</span>
                    <span>{{ $order->order_no }}</span>
                </div>

                <div class="mt-3 flex justify-between gap-4">
                    <span class="text-blue-100/60">Transaction ID</span>
                    <span>{{ $order->transaction_id }}</span>
                </div>

                <div class="mt-3 flex justify-between gap-4">
                    <span class="text-blue-100/60">Plan</span>
                    <span>{{ $order->pricingPlan?->title ?? 'N/A' }}</span>
                </div>

                <div class="mt-3 flex justify-between gap-4">
                    <span class="text-blue-100/60">Billing</span>
                    <span class="capitalize">{{ $order->billing_cycle }}</span>
                </div>

                <div class="mt-3 flex justify-between gap-4">
                    <span class="text-blue-100/60">Amount</span>
                    <span>৳{{ number_format((float) $order->amount, 2) }}</span>
                </div>

                <div class="mt-3 flex justify-between gap-4">
                    <span class="text-blue-100/60">Status</span>
                    <span class="font-semibold text-emerald-300">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

            <a href="{{ route('home') }}" wire:navigate
                class="mt-8 inline-flex rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3 font-semibold text-white">
                Back to Home
            </a>
        </div>
    </section>
</div>