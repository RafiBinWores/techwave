<?php

use App\Mail\WhmcsLinkVerificationMail;
use App\Models\WhmcsAccount;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Link Billing Account')] class extends Component {
    public string $step = 'email';

    public string $email = '';

    public string $code = '';

    public string $unlinkStep = 'confirm';

    public string $unlinkCode = '';

    public string $linkedEmail = '';

    public string $linkedAt = '';

    public function mount(): void
    {
        $account = Auth::user()->whmcsAccount;

        if ($account) {
            $this->linkedEmail = $account->email;
            $this->linkedAt = $account->verified_at->format('d M Y, h:i A');
            $this->step = 'linked';

            return;
        }

        $pending = $this->pendingLink();

        if ($pending && now()->lessThan(Carbon::parse($pending['expires_at']))) {
            $this->email = $pending['email'];
            $this->step = 'code';
        }
    }

    public function sendCode(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        if (RateLimiter::tooManyAttempts($this->rateLimitKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->rateLimitKey());

            $this->addError('email', "Too many code requests. Please try again in {$seconds} seconds.");

            return;
        }

        $api = app(WhmcsApi::class);
        $email = strtolower(trim($this->email));

        try {
            $whmcsUser = $api->findUserByEmail($email);
        } catch (WhmcsApiException $exception) {
            $this->addError('email', $exception->getMessage());

            return;
        }

        if (! $whmcsUser) {
            $this->addError('email', 'No billing account was found for this email address.');

            return;
        }

        $whmcsUserId = (string) ($whmcsUser['id'] ?? '');

        if (WhmcsAccount::query()->where('whmcs_user_id', $whmcsUserId)->whereNot('user_id', Auth::id())->exists()) {
            $this->addError('email', 'This billing account is already linked to another portal account.');

            return;
        }

        $clientId = null;

        try {
            $clientDetails = $api->getClientDetails(null, $email);

            $clientId = $clientDetails !== null ? (string) data_get($clientDetails, 'id', '') : '';
            $clientId = $clientId !== '' ? $clientId : null;
        } catch (WhmcsApiException) {
            $clientId = null;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session()->put('whmcs_link_pending', [
            'email' => $email,
            'whmcs_user_id' => $whmcsUserId,
            'whmcs_client_id' => $clientId,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'attempts' => 0,
        ]);

        Mail::to($email)->send(new WhmcsLinkVerificationMail(
            code: $code,
            expiresInMinutes: 15,
            recipientName: trim(($whmcsUser['firstname'] ?? '') . ' ' . ($whmcsUser['lastname'] ?? '')) ?: 'Customer',
        ));

        RateLimiter::hit($this->rateLimitKey(), 600);

        $this->resetValidation();
        $this->code = '';
        $this->step = 'code';
    }

    public function resendCode(): void
    {
        $pending = $this->pendingLink();

        if (! $pending) {
            $this->step = 'email';

            return;
        }

        $this->email = (string) $pending['email'];

        $this->sendCode();
    }

    public function verifyCode(): void
    {
        $pending = $this->pendingLink();

        if (! $pending || now()->greaterThanOrEqualTo(Carbon::parse($pending['expires_at']))) {
            session()->forget('whmcs_link_pending');
            $this->addError('code', 'The verification code has expired. Please request a new one.');
            $this->step = 'email';

            return;
        }

        if ((int) ($pending['attempts'] ?? 0) >= 5) {
            session()->forget('whmcs_link_pending');
            $this->addError('code', 'Too many incorrect attempts. Please request a new code.');
            $this->step = 'email';

            return;
        }

        $this->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! Hash::check($this->code, (string) $pending['code_hash'])) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            session()->put('whmcs_link_pending', $pending);

            $remaining = 5 - (int) $pending['attempts'];

            $this->addError('code', "Incorrect code. {$remaining} " . ($remaining === 1 ? 'attempt' : 'attempts') . ' remaining.');

            return;
        }

        if (WhmcsAccount::query()->where('whmcs_user_id', (string) $pending['whmcs_user_id'])->whereNot('user_id', Auth::id())->exists()) {
            session()->forget('whmcs_link_pending');

            $this->addError('code', 'This billing account is already linked to another portal account.');

            return;
        }

        $account = WhmcsAccount::query()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'whmcs_user_id' => (string) $pending['whmcs_user_id'],
                'whmcs_client_id' => $pending['whmcs_client_id'],
                'email' => (string) $pending['email'],
                'verified_at' => now(),
            ],
        );

        session()->forget('whmcs_link_pending');
        $this->resetValidation();

        $this->linkedEmail = $account->email;
        $this->linkedAt = $account->verified_at->format('d M Y, h:i A');

        $this->dispatch('toast', type: 'success', message: 'Your billing account has been linked successfully.');

        $this->step = 'linked';
    }

    public function sendUnlinkCode(): void
    {
        $account = Auth::user()->whmcsAccount;

        if (! $account) {
            return;
        }

        if (RateLimiter::tooManyAttempts($this->unlinkRateLimitKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->unlinkRateLimitKey());
            $this->addError('unlinkCode', "Too many code requests. Please try again in {$seconds} seconds.");

            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session()->put('whmcs_unlink_pending', [
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'attempts' => 0,
        ]);

        Mail::to($account->email)->send(new WhmcsLinkVerificationMail(
            code: $code,
            expiresInMinutes: 15,
            recipientName: trim(Auth::user()->name) ?: 'Customer',
        ));

        RateLimiter::hit($this->unlinkRateLimitKey(), 600);

        $this->resetValidation('unlinkCode');
        $this->unlinkCode = '';
        $this->unlinkStep = 'code';
    }

    public function verifyUnlinkCode(): void
    {
        $pending = $this->pendingUnlink();

        if (! $pending || now()->greaterThanOrEqualTo(Carbon::parse($pending['expires_at']))) {
            session()->forget('whmcs_unlink_pending');
            $this->addError('unlinkCode', 'The verification code has expired. Please request a new one.');
            $this->unlinkStep = 'confirm';

            return;
        }

        if ((int) ($pending['attempts'] ?? 0) >= 5) {
            session()->forget('whmcs_unlink_pending');
            $this->addError('unlinkCode', 'Too many incorrect attempts. Please request a new code.');
            $this->unlinkStep = 'confirm';

            return;
        }

        $this->validate([
            'unlinkCode' => ['required', 'digits:6'],
        ]);

        if (! Hash::check($this->unlinkCode, (string) $pending['code_hash'])) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            session()->put('whmcs_unlink_pending', $pending);

            $remaining = 5 - (int) $pending['attempts'];

            $this->addError('unlinkCode', "Incorrect code. {$remaining} " . ($remaining === 1 ? 'attempt' : 'attempts') . ' remaining.');

            return;
        }

        session()->forget('whmcs_unlink_pending');

        Auth::user()->whmcsAccount?->delete();

        $this->linkedEmail = '';
        $this->linkedAt = '';
        $this->email = '';
        $this->code = '';
        $this->unlinkCode = '';
        $this->unlinkStep = 'confirm';

        $this->dispatch('toast', type: 'success', message: 'Your billing account has been unlinked.');

        $this->step = 'email';

        $this->redirectRoute('account.link-whmcs', navigate: true);
    }

    public function resendUnlinkCode(): void
    {
        $this->sendUnlinkCode();
    }

    public function cancelUnlink(): void
    {
        session()->forget('whmcs_unlink_pending');
        $this->resetValidation('unlinkCode');
        $this->unlinkCode = '';
        $this->unlinkStep = 'confirm';
    }

    public function cancelLinking(): void
    {
        session()->forget('whmcs_link_pending');

        $this->resetValidation();
        $this->email = '';
        $this->code = '';
        $this->step = 'email';
    }

    public function backToEmail(): void
    {
        $pending = $this->pendingLink();

        $this->email = (string) ($pending['email'] ?? '');
        $this->code = '';
        $this->step = 'email';
    }

    public function maskedPendingEmail(): string
    {
        $pending = $this->pendingLink();
        $email = (string) ($pending['email'] ?? $this->email);

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2) . str_repeat('*', max(mb_strlen($local) - 2, 3)) . '@' . $domain;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /** @return array<string, mixed>|null */
    protected function pendingLink(): ?array
    {
        /** @var array<string, mixed>|null $pending */
        $pending = session()->get('whmcs_link_pending');

        return is_array($pending) ? $pending : null;
    }

    protected function rateLimitKey(): string
    {
        return 'whmcs-link-code:' . Auth::id();
    }

    protected function unlinkRateLimitKey(): string
    {
        return 'whmcs-unlink-code:' . Auth::id();
    }

    /** @return array<string, mixed>|null */
    protected function pendingUnlink(): ?array
    {
        /** @var array<string, mixed>|null $pending */
        $pending = session()->get('whmcs_unlink_pending');

        return is_array($pending) ? $pending : null;
    }
};
?>

<div x-data="{ sidebarOpen: false, showUnlinkModal: false }" class="relative min-h-screen text-white">
    <livewire:shared.font-toast-notification />
    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = true"
                                class="cursor-pointer flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <span class="cursor-pointer material-symbols-outlined">menu</span>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Account Settings</p>
                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">Link Billing Account</h1>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 backdrop-blur-xl">
                            <span
                                class="material-symbols-outlined {{ $step === 'linked' ? 'text-emerald-300' : 'text-cyan-300' }}">
                                {{ $step === 'linked' ? 'link_off' : 'link' }}
                            </span>
                            <div>
                                <p class="text-xs text-blue-100/45">Billing Account</p>
                                <p class="text-sm font-semibold {{ $step === 'linked' ? 'text-emerald-300' : 'text-blue-100/70' }}">
                                    {{ $step === 'linked' ? 'Linked' : 'Not linked' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

                        {{-- Left Content --}}
                        <div class="space-y-6">

                            {{-- Step 1: Enter email --}}
                            @if ($step === 'email')
                            <form wire:submit.prevent="sendCode"
                                class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="mb-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Step 1 of 2 · Verification
                                    </p>
                                    <h2 class="mt-2 text-2xl font-bold text-white">Link your billing account</h2>
                                    <p class="mt-2 text-sm text-blue-100/55">
                                        Enter the email address you use in our billing portal. We will send a
                                        6-digit verification code to confirm ownership.
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-100/70">
                                        Billing Email Address
                                    </label>
                                    <input type="email" wire:model="email"
                                        class="h-12 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-sm text-white placeholder:text-blue-100/35 outline-none backdrop-blur-xl focus:border-cyan-300/40"
                                        placeholder="you@example.com">
                                    @error('email')
                                    <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="submit" wire:loading.attr="disabled"
                                        wire:target="sendCode"
                                        class="cursor-pointer inline-flex items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-cyan-500/35 disabled:cursor-not-allowed disabled:opacity-60">

                                        <span wire:loading.remove wire:target="sendCode"
                                            class="inline-flex items-center">
                                            <span class="material-symbols-outlined mr-2 text-lg">mark_email_read</span>
                                            Send Verification Code
                                        </span>

                                        <span wire:loading wire:target="sendCode"
                                            class="inline-flex items-center">
                                            <span
                                                class="material-symbols-outlined mr-2 animate-spin text-lg">progress_activity</span>
                                            Sending code...
                                        </span>
                                    </button>
                                </div>
                            </form>
                            @endif

                            {{-- Step 2: Verify code --}}
                            @if ($step === 'code')
                            <form wire:submit.prevent="verifyCode"
                                class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="mb-6">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Step 2 of 2 · Verification
                                    </p>
                                    <h2 class="mt-2 text-2xl font-bold text-white">Enter verification code</h2>
                                    <p class="mt-2 text-sm text-blue-100/55">
                                        We sent a 6-digit code to
                                        <span class="font-semibold text-cyan-200">{{ $this->maskedPendingEmail() }}</span>.
                                        The code expires in 15 minutes.
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-100/70">
                                        Verification Code
                                    </label>
                                    <input type="text" inputmode="numeric" autocomplete="one-time-code"
                                        maxlength="6" wire:model="code"
                                        class="h-14 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-center text-2xl font-bold tracking-[0.6em] text-white placeholder:text-blue-100/25 outline-none backdrop-blur-xl focus:border-cyan-300/40"
                                        placeholder="000000">
                                    @error('code')
                                    <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="button" wire:click="resendCode"
                                            wire:loading.attr="disabled" wire:target="resendCode"
                                            class="cursor-pointer rounded-full border border-white/10 bg-white/8 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60">
                                            Resend code
                                        </button>

                                        <button type="button" wire:click="cancelLinking"
                                            class="cursor-pointer rounded-full px-5 py-2.5 text-sm font-medium text-blue-100/60 transition hover:text-white">
                                            Cancel
                                        </button>
                                    </div>

                                    <button type="submit" wire:loading.attr="disabled"
                                        wire:target="verifyCode"
                                        class="cursor-pointer inline-flex items-center justify-center rounded-full bg-gradient-to-r from-emerald-400 to-green-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5 hover:shadow-emerald-500/35 disabled:cursor-not-allowed disabled:opacity-60">

                                        <span wire:loading.remove wire:target="verifyCode"
                                            class="inline-flex items-center">
                                            <span class="material-symbols-outlined mr-2 text-lg">verified</span>
                                            Verify & Link Account
                                        </span>

                                        <span wire:loading wire:target="verifyCode"
                                            class="inline-flex items-center">
                                            <span
                                                class="material-symbols-outlined mr-2 animate-spin text-lg">progress_activity</span>
                                            Verifying...
                                        </span>
                                    </button>
                                </div>
                            </form>
                            @endif

                            {{-- Linked status --}}
                            @if ($step === 'linked')
                            <div
                                class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="flex flex-col items-center text-center">
                                    <div
                                        class="flex h-16 w-16 items-center justify-center rounded-full border border-emerald-300/20 bg-emerald-400/10 text-emerald-200">
                                        <span class="material-symbols-outlined text-4xl">link</span>
                                    </div>

                                    <p class="mt-5 text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                        Billing Account Linked
                                    </p>
                                    <h2 class="mt-2 text-2xl font-bold text-white">{{ $linkedEmail }}</h2>
                                    <p class="mt-2 text-sm text-blue-100/55">Linked on {{ $linkedAt }}</p>

                                    <span
                                        class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-300">
                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                        Verified &amp; Active
                                    </span>

                                    <button type="button" @click="showUnlinkModal = true"
                                        class="cursor-pointer mt-6 inline-flex items-center justify-center gap-2 rounded-full border border-rose-300/20 bg-rose-500/10 px-6 py-3 text-sm font-bold text-rose-200 transition hover:bg-rose-500/20 disabled:cursor-not-allowed disabled:opacity-60">
                                        <span class="material-symbols-outlined text-lg">link_off</span>
                                        Unlink Account
                                    </button>
                                </div>
                            </div>
                            @endif

                        </div>

                        {{-- Right Sidebar --}}
                        <div class="space-y-6">

                            {{-- How it works --}}
                            <div
                                class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Guide</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">How linking works</h2>

                                <ol class="mt-6 space-y-5">
                                    @foreach ([
                                    'Enter your billing email address.',
                                    'Receive a 6-digit code in your inbox.',
                                    'Enter the code to verify ownership.',
                                    'Your billing services and invoices appear here.',
                                    ] as $index => $instruction)
                                    <li class="flex items-start gap-4">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-cyan-300/25 bg-cyan-400/10 text-sm font-bold text-cyan-200">
                                            {{ $index + 1 }}
                                        </span>
                                        <p class="pt-1.5 text-sm leading-6 text-blue-100/60">{{ $instruction }}</p>
                                    </li>
                                    @endforeach
                                </ol>
                            </div>

                            {{-- Security note --}}
                            <div
                                class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-emerald-300/20 bg-emerald-400/10 text-emerald-200">
                                        <span class="material-symbols-outlined">shield_lock</span>
                                    </div>

                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Security</p>
                                        <h2 class="mt-2 text-xl font-bold text-white">Verified ownership only</h2>
                                        <p class="mt-3 text-sm leading-7 text-blue-100/60">
                                            An account can only be linked after proving you own its email. A billing
                                            account can be linked to one portal profile at a time, and you can unlink
                                            it whenever you want.
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Unlink Confirmation Modal --}}
    <div x-show="showUnlinkModal" x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6" style="display: none;">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md"
            @click="showUnlinkModal = false; $wire.cancelUnlink();"></div>

        <div x-show="showUnlinkModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 w-full max-w-md rounded-[28px] border border-white/10 bg-slate-950/90 p-6 shadow-2xl backdrop-blur-2xl"
            style="display: none;">

            {{-- Step: Confirm --}}
            @if ($unlinkStep === 'confirm')
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-rose-300/20 bg-rose-400/10 text-rose-200">
                    <span class="material-symbols-outlined text-2xl">link_off</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Unlink billing account?</h3>
                    <p class="mt-2 text-sm leading-6 text-blue-100/60">
                        We'll send a verification code to
                        <span class="font-semibold text-cyan-200">{{ $linkedEmail }}</span>
                        to confirm this action.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    @click="showUnlinkModal = false; $wire.cancelUnlink();"
                    class="cursor-pointer rounded-full border border-white/10 bg-white/8 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/12">
                    Cancel
                </button>
                <button type="button" wire:click="sendUnlinkCode"
                    wire:loading.attr="disabled" wire:target="sendUnlinkCode"
                    class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="sendUnlinkCode" class="inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">mark_email_read</span>
                        Send Verification Code
                    </span>
                    <span wire:loading wire:target="sendUnlinkCode" class="inline-flex items-center gap-2">
                        <span class="material-symbols-outlined mr-1 animate-spin text-lg">progress_activity</span>
                        Sending...
                    </span>
                </button>
            </div>
            @endif

            {{-- Step: Verify Code --}}
            @if ($unlinkStep === 'code')
            <div>
                <h3 class="text-lg font-bold text-white">Enter verification code</h3>
                <p class="mt-2 text-sm leading-6 text-blue-100/60">
                    We sent a 6-digit code to
                    <span class="font-semibold text-cyan-200">{{ $linkedEmail }}</span>.
                    The code expires in 15 minutes.
                </p>
            </div>

            <div class="mt-5">
                <input type="text" inputmode="numeric" autocomplete="one-time-code"
                    maxlength="6" wire:model="unlinkCode"
                    class="h-14 w-full rounded-2xl border border-white/10 bg-white/8 px-4 text-center text-2xl font-bold tracking-[0.6em] text-white placeholder:text-blue-100/25 outline-none backdrop-blur-xl focus:border-cyan-300/40"
                    placeholder="000000">
                @error('unlinkCode')
                <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="resendUnlinkCode"
                        wire:loading.attr="disabled" wire:target="resendUnlinkCode"
                        class="cursor-pointer rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60">
                        Resend code
                    </button>
                    <button type="button"
                        @click="showUnlinkModal = false; $wire.cancelUnlink();"
                        class="cursor-pointer rounded-full px-4 py-2 text-sm font-medium text-blue-100/60 transition hover:text-white">
                        Cancel
                    </button>
                </div>
                <button type="button" wire:click="verifyUnlinkCode"
                    wire:loading.attr="disabled" wire:target="verifyUnlinkCode"
                    class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-full border border-rose-300/20 bg-rose-500/15 px-5 py-2.5 text-sm font-bold text-rose-200 transition hover:bg-rose-500/25 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="verifyUnlinkCode" class="inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        Unlink
                    </span>
                    <span wire:loading wire:target="verifyUnlinkCode" class="inline-flex items-center gap-2">
                        <span class="material-symbols-outlined mr-1 animate-spin text-lg">progress_activity</span>
                        Verifying...
                    </span>
                </button>
            </div>
            @endif
        </div>
    </div>
</div>