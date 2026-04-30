<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component {
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate(
            [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [],
            [
                'email' => 'email',
                'password' => 'password',
            ],
        );

        $client = User::where('email', $this->email)->where('role', UserRole::CLIENT)->first();

        if (!$client) {
            $this->addError('email', 'We could not find any client account with this email.');
            return;
        }

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password) {
                $user
                    ->forceFill([
                        'password' => Hash::make($password),
                    ])
                    ->save();

                Auth::login($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        session()->flash('auth_success', 'Password reset successfully. Welcome back!');

        $this->redirect(route('home'), navigate: false);
    }
};
?>

<div>
    <div class="min-h-screen bg-slate-950 px-4 py-10 text-white flex items-center justify-center">
        <div
            class="relative w-full max-w-xl overflow-hidden rounded-[34px] border border-white/10 bg-white/8 p-6 sm:p-8 shadow-[0_30px_100px_rgba(0,0,0,0.35)] backdrop-blur-2xl">

            <div class="absolute left-0 top-0 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-52 w-52 rounded-full bg-blue-500/12 blur-3xl"></div>

            <div class="relative z-10">
                <div class="mx-auto mb-5 flex h-14 items-center justify-center">
                    <img src="https://techwave.asia/storage/services/light-logo-142x75.png" alt="">
                </div>

                <h1 class="text-center text-3xl font-bold text-white">
                    Create new password
                </h1>

                <p class="mt-3 text-center text-sm leading-6 text-blue-100/65">
                    Enter your email and new password to secure your account.
                </p>

                @if (session('auth_success'))
                    <div
                        class="mt-5 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {{ session('auth_success') }}
                    </div>
                @endif

                <form wire:submit.prevent="resetPassword" class="mt-6 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-blue-50/85">
                            Email Address
                        </label>

                        <input type="email" wire:model.defer="email" readonly
                            class="auth-input opacity-70 cursor-not-allowed" placeholder="Enter your email">

                        @error('email')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-blue-50/85">
                            New Password
                        </label>

                        <input type="password" wire:model.defer="password" class="auth-input"
                            placeholder="Enter new password">

                        @error('password')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-blue-50/85">
                            Confirm Password
                        </label>

                        <input type="password" wire:model.defer="password_confirmation" class="auth-input"
                            placeholder="Confirm new password">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="resetPassword"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70 cursor-pointer">
                        <span wire:loading.remove wire:target="resetPassword">
                            Reset Password
                        </span>

                        <span wire:loading wire:target="resetPassword" class="inline-flex items-center gap-2">
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                            Updating...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
