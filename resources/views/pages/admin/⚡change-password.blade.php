<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Change Password')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $showCurrentPassword = false;
    public bool $showPassword = false;
    public bool $showPasswordConfirmation = false;

    public function generatePassword(): void
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%^&*';

        $generated = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $upper . $lower . $numbers . $symbols;

        for ($i = count($generated); $i < 14; $i++) {
            $generated[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($generated);

        $password = implode('', $generated);
        $this->password = $password;
        $this->password_confirmation = $password;

        $this->showPassword = true;
        $this->showPasswordConfirmation = true;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.required' => 'Please enter your new password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');

            return;
        }

        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', message: 'Password changed successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full max-w-2xl space-y-8">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Change Password</h1>
            <p class="mt-1 text-body-md text-secondary">Update your account password.</p>
        </div>

        <form wire:submit.prevent="updatePassword" class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="mb-6 flex items-center gap-2 text-h3 font-h2">
                    <span class="material-symbols-outlined text-primary">lock</span>
                    New Password
                </h3>

                <div class="space-y-4">
                    <!-- Current Password -->
                    <div class="space-y-2">
                        <label class="block font-label-md text-on-surface">Current Password</label>
                        <div class="relative">
                            <input :type="$wire.showCurrentPassword ? 'text' : 'password'" wire:model.live="current_password"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 pr-10">
                            <button type="button" wire:click="$toggle('showCurrentPassword')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600">
                                <span class="material-symbols-outlined text-[18px]">{{ $showCurrentPassword ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="space-y-2">
                        <label class="block font-label-md text-on-surface">New Password</label>
                        <div class="relative">
                            <input :type="$wire.showPassword ? 'text' : 'password'" wire:model.live="password"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 pr-10">
                            <button type="button" wire:click="$toggle('showPassword')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600">
                                <span class="material-symbols-outlined text-[18px]">{{ $showPassword ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-2">
                        <label class="block font-label-md text-on-surface">Confirm New Password</label>
                        <div class="relative">
                            <input :type="$wire.showPasswordConfirmation ? 'text' : 'password'" wire:model.live="password_confirmation"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 pr-10">
                            <button type="button" wire:click="$toggle('showPasswordConfirmation')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-slate-600">
                                <span class="material-symbols-outlined text-[18px]">{{ $showPasswordConfirmation ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="button" wire:click="generatePassword"
                    class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary transition hover:text-primary/80">
                    <span class="material-symbols-outlined text-[18px]">autorenew</span>
                    Generate Strong Password
                </button>

                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:opacity-60">
                    <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                    <span wire:loading wire:target="updatePassword" class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Updating...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
