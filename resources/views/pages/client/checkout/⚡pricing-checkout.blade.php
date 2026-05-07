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
    <section class="min-h-screen py-10 text-white">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-10 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-300">
                    Secure Checkout
                </p>

                <h1 class="mt-3 text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Complete Your Order
                </h1>

                {{-- <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-blue-100/60 sm:text-base">
                    Enter your contact details and continue to SSLCommerz secure payment gateway.
                </p> --}}
            </div>

            <form method="POST" action="{{ route('client.checkout.pricing.pay', $pricingPlan->id) }}">
                @csrf

                <input type="hidden" name="billing" value="{{ $billing }}">

                <div class="grid gap-8 lg:grid-cols-[1fr_420px]">

                    {{-- Left: Checkout Form --}}
                    <div class="space-y-6">

                        {{-- Billing Option --}}
                        <div
                            class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                            <div class="mb-6 flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100">
                                    <span class="material-symbols-outlined">calendar_month</span>
                                </div>

                                <div>
                                    <h2 class="text-xl font-bold">Billing Cycle</h2>
                                    <p class="mt-1 text-sm text-blue-100/55">
                                        Choose how you want to pay for this plan.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <button type="button" wire:click="$set('billing', 'monthly')"
                                    class="group cursor-pointer rounded-2xl border p-5 text-left transition
                                    {{ $billing === 'monthly'
                                        ? 'border-cyan-300/60 bg-cyan-300/10 shadow-lg shadow-cyan-500/10'
                                        : 'border-white/10 bg-white/5 hover:border-white/20 hover:bg-white/10' }}">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-bold text-white">Monthly</p>
                                            <p class="mt-1 text-sm text-blue-100/55">
                                                Pay every month
                                            </p>
                                        </div>

                                        <div
                                            class="flex h-6 w-6 items-center justify-center rounded-full border
                                            {{ $billing === 'monthly' ? 'border-cyan-300 bg-cyan-300 text-slate-950' : 'border-white/20 text-transparent' }}">
                                            <span class="material-symbols-outlined text-base">check</span>
                                        </div>
                                    </div>

                                    <p class="mt-4 text-2xl font-bold">
                                        ৳{{ number_format((float) $pricingPlan->monthly_price, 2) }}
                                    </p>
                                </button>

                                <button type="button" wire:click="$set('billing', 'yearly')"
                                    class="group cursor-pointer rounded-2xl border p-5 text-left transition
                                    {{ $billing === 'yearly'
                                        ? 'border-cyan-300/60 bg-cyan-300/10 shadow-lg shadow-cyan-500/10'
                                        : 'border-white/10 bg-white/5 hover:border-white/20 hover:bg-white/10' }}">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-bold text-white">Yearly</p>
                                            <p class="mt-1 text-sm text-blue-100/55">
                                                Pay once a year
                                            </p>
                                        </div>

                                        <div
                                            class="flex h-6 w-6 items-center justify-center rounded-full border
                                            {{ $billing === 'yearly' ? 'border-cyan-300 bg-cyan-300 text-slate-950' : 'border-white/20 text-transparent' }}">
                                            <span class="material-symbols-outlined text-base">check</span>
                                        </div>
                                    </div>

                                    <p class="mt-4 text-2xl font-bold">
                                        ৳{{ number_format((float) $pricingPlan->yearly_price, 2) }}
                                    </p>
                                </button>
                            </div>
                        </div>

                        {{-- Customer Information --}}
                        <div
                            class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-8">
                            <div class="mb-6 flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-300/10 text-cyan-200">
                                    <span class="material-symbols-outlined">person</span>
                                </div>

                                <div>
                                    <h2 class="text-xl font-bold">Customer Information</h2>
                                    <p class="mt-1 text-sm text-blue-100/55">
                                        Enter your details for order confirmation and payment processing.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                {{-- Name --}}
                                <div>
                                    <label for="customer_name"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        Full Name <span class="text-red-300">*</span>
                                    </label>

                                    <input id="customer_name" type="text" name="customer_name"
                                        value="{{ old('customer_name', auth()->user()->name ?? '') }}" required
                                        placeholder="Your full name"
                                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">

                                    @error('customer_name')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label for="customer_email"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        Email Address <span class="text-red-300">*</span>
                                    </label>

                                    <input id="customer_email" type="email" name="customer_email"
                                        value="{{ old('customer_email', auth()->user()->email ?? '') }}" required
                                        placeholder="you@example.com"
                                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">

                                    @error('customer_email')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Phone --}}
                                <div class="sm:col-span-2">
                                    <label for="customer_phone"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        Phone Number <span class="text-red-300">*</span>
                                    </label>

                                    <input id="customer_phone" type="tel" name="customer_phone"
                                        value="{{ old('customer_phone') }}" required placeholder="Enter phone number"
                                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">

                                    <input type="hidden" name="phone_country" id="phone_country"
                                        value="{{ old('phone_country', 'BD') }}">
                                    <input type="hidden" name="phone_e164" id="phone_e164"
                                        value="{{ old('phone_e164') }}">

                                    @error('customer_phone')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror

                                    @error('phone_e164')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                    <p id="customer_phone_client_error" class="mt-1 hidden text-xs text-red-300">
                                        Please enter a valid phone number.
                                    </p>
                                </div>

                                {{-- Address --}}
                                <div class="sm:col-span-2">
                                    <label for="customer_address"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        Address <span class="text-red-300">*</span>
                                    </label>

                                    <textarea id="customer_address" name="customer_address" rows="3" required placeholder="House / Road / Area"
                                        class="w-full resize-none rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">{{ old('customer_address') }}</textarea>

                                    @error('customer_address')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- City --}}
                                <div>
                                    <label for="customer_city"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        City <span class="text-red-300">*</span>
                                    </label>

                                    <input id="customer_city" type="text" name="customer_city"
                                        value="{{ old('customer_city') }}" required placeholder="Dhaka"
                                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">

                                    @error('customer_city')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Postcode --}}
                                <div>
                                    <label for="customer_postcode"
                                        class="mb-2 block text-sm font-semibold text-blue-100/80">
                                        Postcode <span class="text-red-300">*</span>
                                    </label>

                                    <input id="customer_postcode" type="number" name="customer_postcode"
                                        value="{{ old('customer_postcode') }}" required placeholder="1200"
                                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">

                                    @error('customer_postcode')
                                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Right: Order Summary --}}
                    <div class="lg:sticky lg:top-24 lg:self-start">
                        <div
                            class="overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-blue-950/30 backdrop-blur-xl">
                            <div class="border-b border-white/10 p-6 sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20">
                                        <span class="material-symbols-outlined">receipt_long</span>
                                    </div>

                                    <div>
                                        <h2 class="text-xl font-bold">Order Summary</h2>
                                        <p class="mt-1 text-sm text-blue-100/55">
                                            Review your order before payment.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 p-6 sm:p-8">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm text-blue-100/55">Selected Plan</p>
                                    <h3 class="mt-1 text-xl font-bold text-white">
                                        {{ $pricingPlan->title }}
                                    </h3>

                                    @if ($pricingPlan->description)
                                        <p class="mt-2 line-clamp-2 text-sm text-blue-100/50">
                                            {{ $pricingPlan->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="space-y-3 rounded-2xl border border-white/10 bg-white/5 p-5 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <span class="text-blue-100/60">Billing Type</span>
                                        <span class="font-semibold capitalize text-white">{{ $billing }}</span>
                                    </div>

                                    <div class="flex justify-between gap-4">
                                        <span class="text-blue-100/60">Subtotal</span>
                                        <span class="font-semibold text-white">
                                            ৳{{ number_format($this->getAmount(), 2) }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between gap-4">
                                        <span class="text-blue-100/60">TAX (+15%)</span>
                                        <span
                                            class="font-semibold text-emerald-300">৳{{ number_format($this->getAmount() * 0.15, 2) }}</span>
                                    </div>

                                    <div class="flex justify-between border-t border-white/10 pt-4 text-lg font-bold">
                                        <span>Total</span>
                                        <span>৳{{ number_format($this->getAmount() + $this->getAmount() * 0.15, 2) }}</span>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-sky-400 px-6 py-4 font-bold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 hover:shadow-blue-500/40">
                                    {{-- <span class="material-symbols-outlined text-xl">lock</span> --}}
                                    PLACE ORDER
                                </button>

                                <div
                                    class="flex items-center justify-center gap-2 text-center text-xs text-blue-100/50">
                                    <span class="material-symbols-outlined text-base">verified_user</span>
                                    Secure payment powered by SSLCommerz
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>


    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/intlTelInput.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js"></script>

        <script>
            function initCheckoutPhoneInput() {
                const phoneInput = document.querySelector('#customer_phone');
                const phoneCountryInput = document.querySelector('#phone_country');
                const phoneE164Input = document.querySelector('#phone_e164');
                const phoneError = document.querySelector('#customer_phone_client_error');

                if (!phoneInput || phoneInput.dataset.itiInitialized === 'true') return;

                phoneInput.dataset.itiInitialized = 'true';

                const iti = window.intlTelInput(phoneInput, {
                    initialCountry: (phoneCountryInput.value || 'BD').toLowerCase(),
                    preferredCountries: ['bd', 'in', 'pk', 'us', 'gb', 'ae', 'sa', 'my'],
                    separateDialCode: true,
                    nationalMode: true,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js',
                });

                function cleanNumber(number) {
                    return number.replace(/[\s\-()]/g, '');
                }

                function showPhoneError(message = 'Please enter a valid phone number.') {
                    phoneInput.classList.remove('border-white/10');
                    phoneInput.classList.add('border-red-300');

                    if (phoneError) {
                        phoneError.innerText = message;
                        phoneError.classList.remove('hidden');
                    }
                }

                function hidePhoneError() {
                    phoneInput.classList.remove('border-red-300');
                    phoneInput.classList.add('border-white/10');

                    if (phoneError) {
                        phoneError.classList.add('hidden');
                    }
                }

                phoneInput.addEventListener('input', hidePhoneError);
                phoneInput.addEventListener('countrychange', hidePhoneError);

                phoneInput.closest('form').addEventListener('submit', function(event) {
                    const selectedCountry = iti.getSelectedCountryData();
                    const countryIso = selectedCountry.iso2.toUpperCase();

                    let rawPhone = cleanNumber(phoneInput.value);

                    phoneCountryInput.value = countryIso;

                    /**
                     * Bangladesh validation
                     * Accept:
                     * 01712345678
                     * 8801712345678
                     * +8801712345678
                     */
                    if (countryIso === 'BD') {
                        rawPhone = rawPhone.replace(/^\+/, '');

                        let bdLocalNumber = rawPhone;

                        if (bdLocalNumber.startsWith('880')) {
                            bdLocalNumber = '0' + bdLocalNumber.substring(3);
                        }

                        const bdRegex = /^01[3-9][0-9]{8}$/;

                        if (!bdRegex.test(bdLocalNumber)) {
                            event.preventDefault();
                            showPhoneError('Please enter a valid Bangladeshi mobile number. Example: 01712345678');
                            phoneInput.focus();
                            return false;
                        }

                        phoneInput.value = bdLocalNumber;
                        phoneE164Input.value = '+88' + bdLocalNumber;

                        hidePhoneError();
                        return true;
                    }

                    /**
                     * Other country validation
                     */
                    phoneE164Input.value = iti.getNumber();

                    if (!iti.isValidNumber()) {
                        event.preventDefault();
                        showPhoneError('Please enter a valid phone number for the selected country.');
                        phoneInput.focus();
                        return false;
                    }

                    hidePhoneError();
                });
            }

            document.addEventListener('DOMContentLoaded', initCheckoutPhoneInput);
            document.addEventListener('livewire:navigated', initCheckoutPhoneInput);
        </script>
    @endpush
</div>
