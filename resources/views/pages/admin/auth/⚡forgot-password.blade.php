<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin-auth')] class extends Component
{
    public string $email = '';

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $admin = User::where('email', $this->email)
            ->whereIn('role', [
                UserRole::ADMIN,
                UserRole::MANAGER,
                UserRole::STAFF,
                UserRole::ADMIN_MANAGER,
            ])
            ->first();

        if (! $admin) {
            $this->addError('email', 'We could not find any admin account with this email.');
            return;
        }

        $status = Password::broker()->sendResetLink([
            'email' => $this->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        session()->flash('success', 'Password reset link sent successfully.');
        $this->reset('email');
    }
};
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-[28px] border border-slate-200 bg-white p-8 shadow-xl dark:border-white/10 dark:bg-white/5">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            Admin Password Reset
        </h1>

        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Enter your admin email to receive a reset link.
        </p>

        @if (session('success'))
            <div class="mt-5 rounded-2xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-500">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="sendResetLink" class="mt-6 space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Email
                </label>

                <input
                    type="email"
                    wire:model.defer="email"
                    class="h-13 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    placeholder="Enter admin email"
                >

                @error('email')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="h-13 w-full rounded-2xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-70"
            >
                <span wire:loading.remove>Send Reset Link</span>
                <span wire:loading>Sending...</span>
            </button>

            <a href="{{ route('admin.login') }}" class="block text-center text-sm font-medium text-blue-600">
                Back to Login
            </a>
        </form>
    </div>
</div>