<?php

use App\Events\BookingCreated;
use App\Mail\OrderPlacedMail;
use App\Models\Booking;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component {
    public PricingPlan $pricingPlan;

    public string $billing = 'monthly';

    public float $amount = 0;

    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';

    public string $customer_address = '';

    public string $company_name = '';
    public string $company_phone = '';

    public string $user_note = '';
    public ?float $requested_price = null;

    public function mount(PricingPlan $pricingPlan): void
    {
        abort_if($pricingPlan->status !== 'active', 404);

        $this->pricingPlan = $pricingPlan;

        $billing = request()->query('billing', 'monthly');

        $this->billing = in_array($billing, ['monthly', 'yearly'], true) ? $billing : 'monthly';

        $this->amount = $this->getAmount();

        abort_if($this->amount <= 0, 404);

        $this->fillUserAndCompanyInfo();
    }

    protected function rules(): array
    {
        return [
            'billing' => ['required', 'in:monthly,yearly'],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/'],

            'company_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['required', 'string', 'max:20', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/'],

            'customer_address' => ['required', 'string', 'max:500'],
            'requested_price' => ['nullable', 'numeric', 'min:0'],
            'user_note' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'customer_name.required' => 'Please enter your full name.',
            'customer_email.required' => 'Please enter your email address.',
            'customer_email.email' => 'Please enter a valid email address.',
            'customer_phone.required' => 'Please enter your phone number.',
            'customer_phone.regex' => 'Please enter a valid Bangladeshi phone number.',

            'company_name.required' => 'Please enter your company name.',
            'company_phone.required' => 'Please enter your company phone number.',
            'company_phone.regex' => 'Please enter a valid Bangladeshi company phone number.',

            'customer_address.required' => 'Please enter your company address.',
            'requested_price.numeric' => 'Requested price must be a valid number.',
            'requested_price.min' => 'Requested price cannot be negative.',
        ];
    }

    public function updatedBilling(): void
    {
        $this->billing = in_array($this->billing, ['monthly', 'yearly'], true) ? $this->billing : 'monthly';

        $this->amount = $this->getAmount();
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function fillUserAndCompanyInfo(): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        $this->customer_name = (string) old('customer_name', $user->name ?? '');
        $this->customer_email = (string) old('customer_email', $user->email ?? '');
        $this->customer_phone = (string) old('customer_phone', $user->phone ?? '');

        if (method_exists($user, 'isCompanyAccount') && $user->isCompanyAccount() && $user->company) {
            $this->company_name = (string) old('company_name', $user->company->company_name ?? '');
            $this->company_phone = (string) old('company_phone', $user->company->phone ?? '');
            $this->customer_address = (string) old('customer_address', $user->company->address ?? '');

            return;
        }

        $this->company_name = (string) old('company_name', '');
        $this->company_phone = (string) old('company_phone', '');
        $this->customer_address = (string) old('customer_address', '');
    }

    public function submitBooking(): void
    {
        if (!Auth::check()) {
            $this->dispatch('toast', message: 'Please login first to book a plan.', type: 'error');

            return;
        }

        abort_if($this->pricingPlan->status !== 'active', 404);

        if ($this->userHasActiveOrPendingBooking(Auth::id(), $this->pricingPlan->id)) {
            $this->dispatch('toast', message: 'You already have this plan pending or active. You cannot book the same plan again until it is completed or cancelled.', type: 'warning');

            return;
        }

        $validated = $this->validate();

        $planPrice = $this->getAmount();

        abort_if($planPrice <= 0, 404);

        $booking = Booking::query()->create([
            'user_id' => Auth::id(),

            'booking_no' => $this->makeBookingNo(),
            'booking_type' => 'pricing_plan',

            'service_id' => null,
            'service_plan_id' => null,
            'pricing_plan_id' => $this->pricingPlan->id,

            'billing_cycle' => $validated['billing'],

            'full_name' => $validated['customer_name'],
            'phone' => $this->normalizeBdPhone($validated['customer_phone']),
            'email' => $validated['customer_email'],

            'company_name' => $validated['company_name'],
            'company_phone' => $this->normalizeBdPhone($validated['company_phone']),

            'plan_name' => $this->pricingPlan->title,
            'plan_price' => $planPrice,
            'requested_price' => $validated['requested_price'] ?? null,
            'quoted_price' => null,
            'final_price' => null,
            'currency' => 'BDT',

            'message' => $this->buildMessageWithAddress($validated['customer_address'], $validated['user_note'] ?? null),
            'user_note' => $validated['user_note'] ?? null,
            'admin_note' => null,

            'status' => 'pending',
            'admin_read_at' => null,
        ]);

        BookingCreated::dispatch($booking);

        $email = $booking->email ?: $booking->user?->email;

        if ($email) {
            Mail::to($email)->send(new OrderPlacedMail($booking));
        }

        $this->dispatch('toast', message: 'Your plan booking request has been submitted successfully. Our team will review it and contact you soon.', type: 'success');

        $this->redirectRoute('account.services', ['tab' => 'plans'], navigate: true);
    }

    public function getAmount(): float
    {
        return (float) ($this->billing === 'yearly' ? $this->pricingPlan->yearly_price : $this->pricingPlan->monthly_price);
    }

    private function makeBookingNo(): string
    {
        do {
            $bookingNo = 'BK-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::query()->where('booking_no', $bookingNo)->exists());

        return $bookingNo;
    }

    private function normalizeBdPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/[\s\-()]/', '', $phone);
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, '88')) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    private function buildMessageWithAddress(string $address, ?string $userNote = null): string
    {
        $message = "Company Address:\n" . $address;

        if ($userNote) {
            $message .= "\n\nRequirement / Message:\n" . $userNote;
        }

        return $message;
    }

    private function userHasActiveOrPendingBooking(int $userId, int $pricingPlanId): bool
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->where('booking_type', 'pricing_plan')
            ->where('pricing_plan_id', $pricingPlanId)
            ->whereIn('status', ['pending', 'quoted', 'accepted', 'converted'])
            ->exists();
    }
};
?>

<div class="min-h-screen pb-6 text-white">
    <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="my-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">Plan booking</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $pricingPlan->title }}</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-blue-100/60">Tell us what you need. Our team will review your request and confirm a price.</p>
        </div>

        <form wire:submit.prevent="submitBooking" novalidate>
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
                        $monthlyPrice = (float) $pricingPlan->monthly_price;
                        $yearlyPrice = (float) $pricingPlan->yearly_price;
                        $yearlySavings = $monthlyPrice > 0 && $yearlyPrice > 0 ? max(0, (int) floor((1 - $yearlyPrice / ($monthlyPrice * 12)) * 100)) : 0;
                        @endphp
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $cycle => $label)
                            <button type="button" wire:click="$set('billing', '{{ $cycle }}')" aria-pressed="{{ $billing === $cycle ? 'true' : 'false' }}"
                                class="group relative flex min-h-[88px] cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-4 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 {{ $billing === $cycle ? 'border-cyan-300/60 bg-cyan-300/10 shadow-lg shadow-cyan-500/10' : 'border-blue-100/10 bg-white/5 hover:border-blue-200/15 hover:bg-white/10' }}">
                                @if ($cycle === 'yearly' && $yearlySavings > 0)
                                <span class="absolute -top-2.5 right-3 rounded-full bg-cyan-300 px-2 py-0.5 text-[10px] font-semibold text-slate-950">Save {{ $yearlySavings }}%</span>
                                @endif
                                <div class="flex items-center gap-3">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 {{ $billing === $cycle ? 'border-cyan-300' : 'border-blue-200/25' }}" aria-hidden="true"><span class="h-2.5 w-2.5 rounded-full {{ $billing === $cycle ? 'bg-cyan-300' : 'bg-transparent' }}"></span></span>
                                    <span class="text-sm font-semibold">{{ $label }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="whitespace-nowrap text-lg font-bold">৳{{ number_format($cycle === 'monthly' ? $monthlyPrice : $yearlyPrice / 12, 2) }}<span class="text-xs font-normal text-blue-100/55">/month</span></p>
                                    <p class="mt-1 text-[11px] text-blue-100/45">{{ $cycle === 'monthly' ? 'Billed every month' : '৳'.number_format($yearlyPrice, 2).' billed yearly' }}</p>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @error('billing')<p class="mt-3 text-xs text-red-300">{{ $message }}</p>@enderror
                    </div>

                    {{-- Billing Details --}}
                    <div class="rounded-2xl border border-blue-100/10 bg-white/5 p-4 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-blue-300/20 bg-blue-300/10 text-blue-100"><span class="material-symbols-outlined">person</span></div>
                            <div>
                                <h2 class="text-lg font-bold">Billing Details</h2>
                                <p class="mt-1 text-sm text-blue-100/55">Your contact and company information.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([
                            'customer_name' => ['Full Name', 'text', 'Your full name'],
                            'customer_email' => ['Email Address', 'email', 'you@example.com'],
                            'customer_phone' => ['Phone Number', 'tel', '01XXXXXXXXX'],
                            'company_name' => ['Company Name', 'text', 'Your company name'],
                            'company_phone' => ['Company Phone', 'tel', '01XXXXXXXXX'],
                            ] as $field => [$label, $type, $placeholder])
                            <div>
                                <label for="{{ $field }}" class="mb-1.5 block text-sm font-semibold text-blue-100/80">{{ $label }} <span class="text-red-300">*</span></label>
                                <input id="{{ $field }}" type="{{ $type }}" wire:model.live="{{ $field }}" placeholder="{{ $placeholder }}"
                                    class="w-full rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">
                                @error($field)<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                            </div>
                            @endforeach
                            <div class="sm:col-span-2">
                                <label for="customer_address" class="mb-1.5 block text-sm font-semibold text-blue-100/80">Company Address <span class="text-red-300">*</span></label>
                                <textarea id="customer_address" wire:model.live="customer_address" rows="2" placeholder="House / Road / Area" class="w-full resize-none rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15"></textarea>
                                @error('customer_address')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Booking Requirements --}}
                    <div class="rounded-2xl border border-blue-100/10 bg-white/5 p-4 shadow-2xl shadow-blue-950/20 backdrop-blur-xl sm:p-5">
                        <h2 class="text-lg font-bold">Your Requirements</h2>
                        <p class="mt-1 text-sm text-blue-100/55">Share your budget and what you need from this plan.</p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="requested_price" class="mb-1.5 block text-sm font-semibold text-blue-100/80">Requested Price</label>
                                <input id="requested_price" type="number" step="0.01" min="0" wire:model.live.debounce.500ms="requested_price" placeholder="Your expected {{ $billing }} price" class="w-full rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15">
                                @error('requested_price')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="user_note" class="mb-1.5 block text-sm font-semibold text-blue-100/80">Requirement / Message</label>
                                <textarea id="user_note" wire:model.live="user_note" rows="3" placeholder="Tell us about your business and requirements..." class="w-full resize-none rounded-xl border border-blue-100/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-blue-100/35 focus:border-cyan-300/70 focus:bg-white/15"></textarea>
                                @error('user_note')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Booking Summary --}}
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="overflow-hidden rounded-2xl border border-blue-100/10 bg-white/5 shadow-2xl shadow-blue-950/30 backdrop-blur-xl">
                        <div class="border-b border-blue-100/10 p-4 sm:p-5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20"><span class="material-symbols-outlined">contract</span></div>
                                <div>
                                    <h2 class="text-lg font-bold">Booking Summary</h2>
                                    <p class="mt-1 text-sm text-blue-100/55">Review your request before submission.</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4 p-4 sm:p-5">
                            <div class="rounded-xl border border-blue-100/10 bg-white/5 p-4">
                                <p class="text-sm text-blue-100/55">Selected Plan</p>
                                <h3 class="mt-1 text-lg font-bold">{{ $pricingPlan->title }}</h3>
                                @if ($pricingPlan->description)<p class="mt-2 line-clamp-2 text-sm text-blue-100/50">{{ $pricingPlan->description }}</p>@endif
                            </div>
                            <div class="space-y-3 rounded-xl border border-blue-100/10 bg-white/5 p-4 text-sm">
                                <div class="flex justify-between gap-4"><span class="text-blue-100/60">Billing Type</span><span class="font-semibold capitalize">{{ $billing }}</span></div>
                                <div class="flex justify-between gap-4"><span class="text-blue-100/60">Listed Price</span><span class="font-semibold">৳{{ number_format($amount, 2) }}</span></div>
                                @if ($requested_price !== null)
                                <div class="flex justify-between gap-4"><span class="text-blue-100/60">Requested Price</span><span class="font-semibold text-cyan-200">৳{{ number_format($requested_price, 2) }}</span></div>
                                @endif
                                <div class="border-t border-blue-100/10 pt-3 text-xs leading-5 text-blue-100/55">Final pricing will be confirmed after reviewing your request.</div>
                            </div>
                            <div class="rounded-xl border border-cyan-300/15 bg-cyan-300/5 p-4">
                                <p class="text-sm font-semibold text-cyan-200">No payment required now</p>
                                <p class="mt-1 text-xs leading-5 text-blue-100/55">Our team will review your booking and contact you with the next steps.</p>
                            </div>
                            @auth
                            <button type="submit" wire:loading.attr="disabled" wire:target="submitBooking" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="submitBooking" class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart-plus preview-icon">
                                        <path d="M16 5h6" />
                                        <path d="M19 2v6" />
                                        <path d="m2.05 2.05 1.099-.028a1 1 0 011.008.815l2.69 14.347A1 1 0 007.83 18H18" />
                                        <path d="M4.564 5H12" />
                                        <path d="M6.25 14h12.712a2 2 0 001.991-1.57l.172-1.041" />
                                        <circle cx="18" cy="20" r="2" />
                                        <circle cx="8" cy="20" r="2" />
                                    </svg>
                                    SUBMIT BOOKING REQUEST
                                </span>
                                <span wire:loading wire:target="submitBooking" class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200/30 border-t-white"></span>SUBMITTING...</span>
                            </button>
                            @else
                            <button type="button" x-data @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white"><span class="material-symbols-outlined text-xl">lock</span>LOGIN TO BOOK THIS PLAN</button>
                            @endauth
                            <p class="text-center text-xs text-blue-100/50">Our team will contact you after review.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>