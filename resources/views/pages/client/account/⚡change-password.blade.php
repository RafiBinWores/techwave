<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $generatedPassword = '';

    public bool $showCurrentPassword = false;
    public bool $showPassword = false;
    public bool $showPasswordConfirmation = false;

    public function generatePassword(): void
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%^&*';

        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $upper . $lower . $numbers . $symbols;

        for ($i = count($password); $i < 14; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        $this->generatedPassword = implode('', $password);
        $this->password = $this->generatedPassword;
        $this->password_confirmation = $this->generatedPassword;

        $this->showPassword = true;
        $this->showPasswordConfirmation = true;

        // $this->dispatch('toast', type: 'success', message: 'Strong password generated.');
    }

    public function updatePassword(): void
    {
        $this->validate(
            [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            ],
            [
                'current_password.required' => 'Please enter your current password.',
                'password.required' => 'Please enter your new password.',
                'password.confirmed' => 'Password confirmation does not match.',
            ],
        );

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user
            ->forceFill([
                'password' => Hash::make($this->password),
            ])
            ->save();

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
            'generatedPassword',
            'showCurrentPassword',
            'showPassword',
            'showPasswordConfirmation',
        ]);

        session()->flash('success', 'Your password has been updated successfully.');

        $this->dispatch('toast', type: 'success', message: 'Password updated successfully.');
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
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

                    {{-- Content --}}
                    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

                        {{-- Left Content --}}
                        <div class="space-y-6">

                            {{-- Password Card --}}
                            <div class="overflow-hidden p-0">
                                <div class="border-b border-white/10 px-4 pb-4 lg:px-6 lg:pb-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h2 class="text-2xl font-bold text-white">
                                                Update your password
                                            </h2>
                                            <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-100/60">
                                                Use a strong password that you do not use on other websites.
                                            </p>
                                        </div>

                                        {{-- <div
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                            <span class="material-symbols-outlined">lock_reset</span>
                                        </div> --}}
                                    </div>
                                </div>

                                <form wire:submit.prevent="updatePassword" class="p-4 lg:p-6">

                                    <div class="grid gap-5">

                                        {{-- Current Password --}}
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-blue-50">
                                                Current Password
                                            </label>

                                            <div class="relative">
                                                <input :type="$wire.showCurrentPassword ? 'text' : 'password'"
                                                    wire:model="current_password" autocomplete="current-password"
                                                    placeholder="Enter your current password"
                                                    class="w-full rounded-2xl border border-white/10 bg-white/8 px-5 py-4 pr-14 text-sm text-white outline-none backdrop-blur-xl placeholder:text-blue-100/35 transition focus:border-cyan-300/40 focus:bg-white/10 focus:ring-4 focus:ring-cyan-400/10">

                                                <button type="button" wire:click="$toggle('showCurrentPassword')"
                                                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-xl text-blue-100/55 transition hover:bg-white/10 hover:text-white">
                                                    <span class="material-symbols-outlined text-xl">
                                                        {{ $showCurrentPassword ? 'visibility_off' : 'visibility' }}
                                                    </span>
                                                </button>
                                            </div>

                                            @error('current_password')
                                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- New Password --}}
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-blue-50">
                                                New Password
                                            </label>

                                            <div class="relative">
                                                <input :type="$wire.showPassword ? 'text' : 'password'"
                                                    wire:model="password" autocomplete="new-password"
                                                    placeholder="Enter new password"
                                                    class="w-full rounded-2xl border border-white/10 bg-white/8 px-5 py-4 pr-14 text-sm text-white outline-none backdrop-blur-xl placeholder:text-blue-100/35 transition focus:border-cyan-300/40 focus:bg-white/10 focus:ring-4 focus:ring-cyan-400/10">

                                                <button type="button" wire:click="$toggle('showPassword')"
                                                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-xl text-blue-100/55 transition hover:bg-white/10 hover:text-white">
                                                    <span class="material-symbols-outlined text-xl">
                                                        {{ $showPassword ? 'visibility_off' : 'visibility' }}
                                                    </span>
                                                </button>
                                            </div>

                                            @error('password')
                                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Confirm Password --}}
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-blue-50">
                                                Confirm New Password
                                            </label>

                                            <div class="relative">
                                                <input :type="$wire.showPasswordConfirmation ? 'text' : 'password'"
                                                    wire:model="password_confirmation" autocomplete="new-password"
                                                    placeholder="Confirm new password"
                                                    class="w-full rounded-2xl border border-white/10 bg-white/8 px-5 py-4 pr-14 text-sm text-white outline-none backdrop-blur-xl placeholder:text-blue-100/35 transition focus:border-cyan-300/40 focus:bg-white/10 focus:ring-4 focus:ring-cyan-400/10">

                                                <button type="button" wire:click="$toggle('showPasswordConfirmation')"
                                                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-xl text-blue-100/55 transition hover:bg-white/10 hover:text-white">
                                                    <span class="material-symbols-outlined text-xl">
                                                        {{ $showPasswordConfirmation ? 'visibility_off' : 'visibility' }}
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Password Generator --}}
                                    <div class="mt-6 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-5">
                                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="flex items-start gap-3">
                                                {{-- <div
                                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-cyan-300/20 bg-cyan-300/10 text-cyan-200">
                                                    <span class="material-symbols-outlined text-xl">key</span>
                                                </div> --}}

                                                <div>
                                                    <h3 class="text-sm font-bold text-white">
                                                        Password Generator
                                                    </h3>

                                                    <p class="mt-2 text-sm leading-6 text-blue-100/60">
                                                        Generate a secure password with uppercase, lowercase, numbers,
                                                        and symbols.
                                                    </p>
                                                </div>
                                            </div>

                                            <button type="button" wire:click="generatePassword"
                                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-cyan-300/20 bg-cyan-400/15 px-5 py-3 text-sm font-bold text-cyan-100 transition hover:-translate-y-0.5 hover:bg-cyan-400/20">
                                                <span class="material-symbols-outlined text-lg">auto_fix_high</span>
                                                Generate
                                            </button>
                                        </div>

                                        @if ($generatedPassword)
                                            <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                                <div
                                                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                                            Generated Password
                                                        </p>

                                                        <p class="mt-2 break-all font-mono text-sm font-semibold text-white">
                                                            {{ $generatedPassword }}
                                                        </p>
                                                    </div>

                                                    <button type="button" x-data
                                                        @click="
                                                            navigator.clipboard.writeText(@js($generatedPassword));

                                                            window.dispatchEvent(new CustomEvent('toast', {
                                                                detail: {
                                                                    type: 'success',
                                                                    message: 'Password copied successfully.'
                                                                }
                                                            }));
                                                        "
                                                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/8 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/12">
                                                        <span class="material-symbols-outlined text-base">content_copy</span>
                                                        Copy
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div
                                        class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

                                        <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-500 to-cyan-400 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70">

                                            <span wire:loading.remove wire:target="updatePassword"
                                                class="material-symbols-outlined text-lg">
                                                lock_reset
                                            </span>

                                            <span wire:loading wire:target="updatePassword"
                                                class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white">
                                            </span>

                                            <span wire:loading.remove wire:target="updatePassword">
                                                Update Password
                                            </span>

                                            <span wire:loading wire:target="updatePassword">
                                                Updating...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Right Content --}}
                        <div class="space-y-6">

                            <div class="client-card p-6">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Security Tips
                                </p>

                                <h2 class="mt-2 text-2xl font-bold text-white">
                                    Keep it safe
                                </h2>

                                <div class="mt-6 space-y-4">
                                    <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                        <div class="flex gap-3">
                                            <span class="material-symbols-outlined text-cyan-200">password</span>
                                            <p class="text-sm leading-6 text-blue-100/65">
                                                Use a unique password for this account.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                        <div class="flex gap-3">
                                            <span class="material-symbols-outlined text-cyan-200">verified_user</span>
                                            <p class="text-sm leading-6 text-blue-100/65">
                                                Avoid using your name, phone number, or common words.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                        <div class="flex gap-3">
                                            <span class="material-symbols-outlined text-cyan-200">content_copy</span>
                                            <p class="text-sm leading-6 text-blue-100/65">
                                                Copy the generated password before saving it somewhere secure.
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
    </div>
</div>