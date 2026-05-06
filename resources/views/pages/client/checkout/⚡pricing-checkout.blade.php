<?php

use App\Models\PricingPlan;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component {
    public PricingPlan $pricingPlan;

    public string $billing = 'monthly';

    public float $amount = 0;

    public function mount(PricingPlan $pricingPlan): void
    {
        abort_if($pricingPlan->status !== 'active', 404);

        $this->pricingPlan = $pricingPlan;

        $billing = request()->query('billing', 'monthly');

        $this->billing = in_array($billing, ['monthly', 'yearly']) ? $billing : 'monthly';

        $this->amount = $this->getAmount();

        abort_if($this->amount <= 0, 404);
    }

    public function updatedBilling(): void
    {
        $this->billing = in_array($this->billing, ['monthly', 'yearly']) ? $this->billing : 'monthly';

        $this->amount = $this->getAmount();
    }

    public function getAmount(): float
    {
        return (float) ($this->billing === 'yearly' ? $this->pricingPlan->yearly_price : $this->pricingPlan->monthly_price);
    }
};
?>

<div>
    <section class="min-h-screen px-4 py-20 text-white">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-2">

            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur-xl">
                <a href="{{ route('home') }}" wire:navigate
                    class="inline-flex items-center gap-2 text-sm font-semibold text-blue-100/70 transition hover:text-white">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to Home
                </a>

                <p class="mt-8 text-sm uppercase tracking-[0.25em] text-cyan-300">
                    Checkout
                </p>

                <h1 class="mt-3 text-4xl font-bold">
                    {{ $pricingPlan->title }}
                </h1>

                <p class="mt-4 text-blue-100/70">
                    {{ $pricingPlan->description ?: 'Flexible IT support plan for your business.' }}
                </p>

                <div class="mt-6 inline-flex rounded-full border border-white/10 bg-white/5 p-1">
                    <button type="button" wire:click="$set('billing', 'monthly')"
                        class="rounded-full px-5 py-2.5 text-sm font-semibold transition cursor-pointer
                    {{ $billing === 'monthly' ? 'bg-linear-to-r from-blue-500 to-sky-400 text-white' : 'text-blue-100/70 hover:text-white' }}">
                        Monthly
                    </button>

                    <button type="button" wire:click="$set('billing', 'yearly')"
                        class="rounded-full px-5 py-2.5 text-sm font-semibold transition cursor-pointer
                    {{ $billing === 'yearly' ? 'bg-linear-to-r from-blue-500 to-sky-400 text-white' : 'text-blue-100/70 hover:text-white' }}">
                        Yearly
                    </button>
                </div>

                <ul class="mt-8 space-y-3 text-sm text-blue-50/85">
                    @forelse ($pricingPlan->features ?? [] as $feature)
                        <li class="pricing-li">
                            {{ $feature }}
                        </li>
                    @empty
                        <li class="pricing-li">
                            Custom features available on request
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur-xl">
                <h2 class="text-2xl font-bold">Order Summary</h2>

                <div class="mt-6 space-y-4 rounded-2xl bg-white/5 p-5">
                    <div class="flex justify-between gap-4">
                        <span class="text-blue-100/70">Plan</span>
                        <span class="text-right">{{ $pricingPlan->title }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-blue-100/70">Billing</span>
                        <span class="capitalize">{{ $billing }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-blue-100/70">Price</span>
                        <span>৳{{ number_format($this->getAmount(), 2) }}</span>
                    </div>

                    <div class="flex justify-between border-t border-white/10 pt-4 text-xl font-bold">
                        <span>Total</span>
                        <span>৳{{ number_format($this->getAmount(), 2) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('client.checkout.pricing.pay', $pricingPlan->id) }}">
                    @csrf

                    <input type="hidden" name="billing" value="{{ $billing }}">

                    <button type="submit"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-4 font-bold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                        Pay with SSLCommerz
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-blue-100/50">
                    You will be redirected to SSLCommerz secure payment page.
                </p>
            </div>
        </div>
    </section>
</div>
