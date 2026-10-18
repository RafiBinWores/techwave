<?php

use App\Events\ToolSubscriptionUpdated;
use App\Mail\ToolSubscriptionPendingMail;
use App\Models\SiteSetting;
use App\Models\ToolPlan;
use App\Models\ToolSubscription;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscribe')] class extends Component {
    public ToolPlan $plan;

    public string $billing = 'monthly';
    public float $amount = 0;

    public string $sender_bkash = '';
    public string $transaction_id = '';
    public string $paymentMethod = 'manual';
    public string $customer_phone = '';
    public string $customer_address = '';

    public bool $submitted = false;

    public function mount(ToolPlan $plan): void
    {
        abort_if(!$plan->is_active, 404);

        $this->plan = $plan;
        $this->paymentMethod = app(\App\Services\SslCommerzSettings::class)->available() ? 'sslcommerz' : 'manual';
        $this->customer_phone = auth()->user()?->phone ?? '';

        $billing = request()->query('billing', 'monthly');

        $this->billing = in_array($billing, ['monthly', 'yearly'], true) ? $billing : 'monthly';

        $this->recalculateAmount();

        abort_if($this->amount <= 0, 404);
    }

    public function updatedBilling(): void
    {
        $this->recalculateAmount();
    }

    private function recalculateAmount(): void
    {
        $this->amount = $this->billing === 'monthly' ? (float) ($this->plan->monthly_price ?? 0) : (float) ($this->plan->yearly_price ?? 0);
    }

    public function siteSetting()
    {
        return SiteSetting::current();
    }

    protected function rules(): array
    {
        return [
            'sender_bkash' => ['required', 'string', 'max:20', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/'],
            'transaction_id' => ['required', 'string', 'max:50'],
        ];
    }

    protected function messages(): array
    {
        return [
            'sender_bkash.required' => 'Please enter your bKash number.',
            'sender_bkash.regex' => 'Please enter a valid Bangladeshi bKash number.',
            'transaction_id.required' => 'Please enter the transaction ID.',
        ];
    }

    public function submitPayment(): void
    {
        if ($this->paymentMethod === 'sslcommerz') {
            $this->submitOnlinePayment();

            return;
        }
        $this->validate(['paymentMethod' => ['required', 'in:manual,sslcommerz'], 'billing' => ['required', 'in:monthly,yearly']]);
        SiteSetting::current()->requireBkashAvailable();

        $this->plan->refresh();
        abort_unless($this->plan->is_active, 404);
        $this->recalculateAmount();
        abort_if($this->amount <= 0, 404);

        $validated = $this->validate();

        $blockedSubscription = auth()
            ->user()
            ->toolSubscriptions()
            ->where('tool_category_id', $this->plan->tool_category_id)
            ->whereIn('status', ['pending', 'active'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($blockedSubscription) {
            $this->dispatch('toast', message: 'You already have an active or pending subscription for this tool. Please cancel it or wait until it expires before booking again.', type: 'error');

            return;
        }

        $now = now();
        $expiresAt = $this->billing === 'monthly' ? $now->copy()->addMonth() : $now->copy()->addYear();

        $subscription = auth()
            ->user()
            ->toolSubscriptions()
            ->where('tool_category_id', $this->plan->tool_category_id)
            ->renewable()
            ->where('payment_method', 'manual')
            ->latest('id')
            ->first();

        if ($subscription) {
            $subscription->update([
                'tool_plan_id' => $this->plan->id,
                'billing_cycle' => $this->billing,
                'amount' => $this->amount,
                'status' => 'pending',
                'starts_at' => $now,
                'expires_at' => $expiresAt,
                'sender_bkash' => $validated['sender_bkash'],
                'transaction_id' => $validated['transaction_id'],
                'verified_at' => null,
                'admin_note' => null,
                'admin_read_at' => null,
            ]);
        } else {
            $subscription = ToolSubscription::create([
                'user_id' => auth()->id(),
                'tool_category_id' => $this->plan->tool_category_id,
                'tool_plan_id' => $this->plan->id,
                'billing_cycle' => $this->billing,
                'amount' => $this->amount,
                'status' => 'pending',
                'starts_at' => $now,
                'expires_at' => $expiresAt,
                'sender_bkash' => $validated['sender_bkash'],
                'transaction_id' => $validated['transaction_id'],
            ]);
        }

        ToolSubscriptionUpdated::dispatch($subscription->fresh(), 'user');

        Mail::to($subscription->fresh()->user->email)->send(new ToolSubscriptionPendingMail($subscription->fresh()));

        $this->submitted = true;

        $this->dispatch('toast', message: 'Payment submitted successfully! We will verify and activate your subscription shortly.', type: 'success');
    }

    public function isRenewal(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()
            ->toolSubscriptions()
            ->where('tool_category_id', $this->plan->tool_category_id)
            ->renewable()
            ->exists();
    }

    private function submitOnlinePayment(): void
    {
        app(\App\Services\SslCommerzSettings::class)->requireAvailable();
        abort_unless(auth()->check(), 403);
        $this->validate([
            'billing' => ['required', 'in:monthly,yearly'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/D'],
            'customer_address' => ['required', 'string', 'max:500'],
        ]);
        $this->plan->refresh();
        abort_unless($this->plan->is_active, 404);
        $this->recalculateAmount();
        abort_if($this->amount <= 0, 404);
        $subscription = \Illuminate\Support\Facades\DB::transaction(function () {
            \App\Models\User::query()->lockForUpdate()->findOrFail(auth()->id());
            $existing = auth()->user()->toolSubscriptions()->where('tool_category_id', $this->plan->tool_category_id)
                ->whereIn('status', ['pending', 'active'])
                ->where(fn($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
            if ($existing) {
                if (
                    $existing->status === 'pending' && $existing->payment_method === 'sslcommerz'
                    && $existing->tool_plan_id === $this->plan->id && $existing->billing_cycle === $this->billing
                ) {
                    return $existing;
                }
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'You already have an active or pending subscription for this tool.']);
            }

            return ToolSubscription::create([
                'user_id' => auth()->id(),
                'tool_category_id' => $this->plan->tool_category_id,
                'tool_plan_id' => $this->plan->id,
                'billing_cycle' => $this->billing,
                'amount' => $this->amount,
                'status' => 'pending',
                'payment_method' => 'sslcommerz'
            ]);
        });
        $url = app(\App\Services\PurchasePayment::class)->start($subscription, [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => $this->customer_phone,
            'address' => $this->customer_address,
        ]);
        $this->redirect($url);
    }
};
?>

<div class="min-h-screen pb-6 text-white">
    <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="my-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">{{ $plan->toolCategory?->name ?? 'Tool subscription' }}</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $plan->name }}</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-blue-100/60">Complete your subscription and get access to your tools.</p>
        </div>

        @if ($this->isRenewal() && !$submitted)
            <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-400/10 p-4 text-sm text-amber-100">
                <span class="material-symbols-outlined text-amber-300">autorenew</span>
                <p>You are renewing your {{ $plan->toolCategory?->name ?? 'tool' }} subscription. SSLCommerz payments activate automatically; bKash payments activate after review.</p>
            </div>
        @endif

        @if ($submitted)
            {{-- Payment Submitted --}}
            <div class="mx-auto max-w-lg rounded-3xl border border-white/10 bg-slate-950/80 p-6 text-center shadow-2xl backdrop-blur-xl sm:p-8">
                <span class="material-symbols-outlined inline-flex size-14 items-center justify-center rounded-full bg-cyan-300/10 text-3xl text-cyan-300">task_alt</span>
                <h2 class="mt-4 text-2xl font-bold">Payment submitted</h2>
                <p class="mt-3 text-sm leading-6 text-blue-100/60">We will review your bKash payment and activate your subscription shortly.</p>
                <div class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs text-blue-100/50">Transaction ID</p>
                    <p class="mt-1 break-all font-mono text-sm text-cyan-200">{{ $transaction_id }}</p>
                </div>
                <a href="{{ route('account.tool-subscriptions') }}" wire:navigate class="mt-5 inline-flex w-full justify-center rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white">View my subscriptions</a>
            </div>
        @else
            <form wire:submit.prevent="submitPayment" novalidate>
                <x-checkout.validation-errors />

                <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div class="min-w-0 space-y-4">
                        {{-- Billing Cycle --}}
                        <div class="rounded-2xl border border-blue-100/10 bg-white/5 p-4 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-5">
                            <div class="mb-4 flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">calendar_month</span></div>
                                <div>
                                    <h2 class="text-lg font-bold">Billing Cycle</h2>
                                    <p class="mt-1 text-sm text-blue-100/55">Choose your preferred billing cycle.</p>
                                </div>
                            </div>
                            @php
                            $monthlyPrice = (float) $plan->monthly_price;
                            $yearlyPrice = (float) $plan->yearly_price;
                            $yearlySavings = $monthlyPrice > 0 && $yearlyPrice > 0 ? max(0, (int) floor((1 - $yearlyPrice / ($monthlyPrice * 12)) * 100)) : 0;
                            @endphp
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $cycle => $label)
                                <button type="button" wire:click="$set('billing', '{{ $cycle }}')" aria-pressed="{{ $billing === $cycle ? 'true' : 'false' }}"
                                    class="group relative flex min-h-[88px] cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-4 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 {{ $billing === $cycle ? 'border-cyan-300/60 bg-cyan-300/10 shadow-lg shadow-cyan-500/10' : 'border-blue-100/10 bg-white/5 hover:border-blue-200/15 hover:bg-white/10' }}">
                                    @if ($cycle === 'yearly' && $yearlySavings > 0)<span class="absolute -top-2.5 right-3 rounded-full bg-cyan-300 px-2 py-0.5 text-[10px] font-semibold text-slate-950">Save {{ $yearlySavings }}%</span>@endif
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 {{ $billing === $cycle ? 'border-cyan-300' : 'border-blue-200/25' }}" aria-hidden="true"><span class="h-2.5 w-2.5 rounded-full {{ $billing === $cycle ? 'bg-cyan-300' : 'bg-transparent' }}"></span></span>
                                        <span class="text-sm font-semibold text-white">{{ $label }}</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="whitespace-nowrap text-lg font-bold text-white">৳{{ number_format($cycle === 'monthly' ? $monthlyPrice : $yearlyPrice / 12, 2) }}<span class="text-xs font-normal text-blue-100/55">/month</span></p>
                                        <p class="mt-1 text-[11px] text-blue-100/45">{{ $cycle === 'monthly' ? 'Billed every month' : '৳'.number_format($yearlyPrice, 2).' billed yearly' }}</p>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        {{-- Billing Details --}}
                        <div class="rounded-2xl border border-blue-100/10 bg-white/5 p-4 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-5">
                            <div class="mb-4 flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">person</span></div>
                                <div>
                                    <h2 class="text-lg font-bold">Billing Details</h2>
                                    <p class="mt-1 text-sm text-blue-100/55">Your contact information for this purchase.</p>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label for="tool-customer-phone" class="mb-1.5 block text-sm font-semibold text-blue-100/80">Phone number <span class="text-red-300">*</span></label>
                                    <input id="tool-customer-phone" type="tel" wire:model="customer_phone" autocomplete="tel" placeholder="01XXXXXXXXX" class="w-full rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">
                                    @error('customer_phone')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                                </div>
                                <div><label for="tool-customer-address" class="mb-1.5 block text-sm font-semibold text-blue-100/80">Billing address <span class="text-red-300">*</span></label>
                                    <input id="tool-customer-address" wire:model="customer_address" autocomplete="street-address" maxlength="500" placeholder="House / Road / Area" class="w-full rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">
                                    @error('customer_address')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/[0.07] p-4 shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-2xl sm:p-5">
                            <x-checkout.payment.method-selector
                                :online-available="app(\App\Services\SslCommerzSettings::class)->available()"
                                :manual-available="$this->siteSetting()->bkashAvailable()"
                            />

                            @if ($paymentMethod === 'manual' && ($this->siteSetting()->bkashAvailable()))
                                <x-checkout.payment.bkash-details
                                    :number="$this->siteSetting()->bkash_number"
                                    :instructions="$this->siteSetting()->bkash_instructions"
                                    :amount="$amount"
                                    :decimals="2"
                                    :live="false"
                                />
                            @elseif (!(app(\App\Services\SslCommerzSettings::class)->available()) && !($this->siteSetting()->bkashAvailable()))
                                <p role="alert" class="text-sm text-amber-200">Payments are currently unavailable. Please contact the team.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Order Summary --}}
                    <div class="lg:sticky lg:top-24 lg:self-start">
                        <div class="overflow-hidden rounded-2xl border border-blue-100/10 bg-white/5 shadow-2xl shadow-blue-950/30 backdrop-blur-xl">
                            <div class="border-b border-blue-100/10 p-4 sm:p-5">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20"><span class="material-symbols-outlined">receipt_long</span></div>
                                    <div>
                                        <h2 class="text-lg font-bold">Order Summary</h2>
                                        <p class="mt-1 text-sm text-blue-100/55">Review your order before submission.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 p-4 sm:p-5">
                                <div class="rounded-xl border border-blue-100/10 bg-white/5 p-4">
                                    <p class="text-sm text-blue-100/55">{{ $plan->toolCategory?->name ?? 'Selected Plan' }}</p>
                                    <h3 class="mt-1 text-lg font-bold text-white">{{ $plan->name }}</h3>
                                </div>
                                <div class="space-y-3 rounded-xl border border-blue-100/10 bg-white/5 p-4 text-sm">
                                    <div class="flex justify-between gap-4"><span class="text-blue-100/60">Billing Type</span><span class="font-semibold capitalize text-white">{{ $billing }}</span></div>
                                    <div class="flex justify-between gap-4"><span class="text-blue-100/60">Plan Price</span><span class="font-semibold text-white">৳{{ number_format($amount, 2) }}</span></div>
                                    <div class="border-t border-blue-100/10 pt-3">
                                        <div class="flex justify-between gap-4"><span class="text-base font-bold text-white">Total</span><span class="text-lg font-bold text-cyan-200">৳{{ number_format($amount, 2) }}</span></div>
                                    </div>
                                </div>
                                @if ($this->siteSetting()->bkashAvailable() || app(\App\Services\SslCommerzSettings::class)->available())
                                <button type="submit" wire:loading.attr="disabled" wire:target="submitPayment" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 hover:shadow-blue-500/40 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span wire:loading.remove wire:target="submitPayment" class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-xl">shopping_cart</span>{{ $paymentMethod === 'sslcommerz' ? 'PROCEED TO CHECKOUT' : 'SUBMIT PAYMENT' }}</span>
                                    <span wire:loading wire:target="submitPayment" class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200/30 border-t-white"></span>SUBMITTING...</span>
                                </button>
                                @endif
                                @if ($plan->features)
                                <div class="border-t border-blue-100/10 pt-4">
                                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-blue-100/45">What's included</p>
                                    <ul class="space-y-2">@foreach ($plan->features as $feature)<li class="flex items-start gap-2 text-sm text-blue-100/70"><span class="text-cyan-300">✓</span>{{ $feature }}</li>@endforeach</ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
