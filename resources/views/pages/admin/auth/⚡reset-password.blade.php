<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin-auth')] class extends Component
{
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
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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

        $status = Password::reset([
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token' => $this->token,
        ], function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            Auth::login($user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        $this->redirect(route('admin.dashboard'), navigate: true);
    }
};
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-[28px] border border-slate-200 bg-white p-8 shadow-xl dark:border-white/10 dark:bg-white/5">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            Create New Password
        </h1>

        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Reset your admin account password.
        </p>

        <form wire:submit.prevent="resetPassword" class="mt-6 space-y-5">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Email
                </label>

                <input
                    type="email"
                    wire:model.defer="email"
                    readonly
                    class="h-13 w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-500 outline-none dark:border-white/10 dark:bg-white/5 dark:text-slate-400"
                >

                @error('email')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                    New Password
                </label>

                <input
                    type="password"
                    wire:model.defer="password"
                    class="h-13 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    placeholder="Enter new password"
                >

                @error('password')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Confirm Password
                </label>

                <input
                    type="password"
                    wire:model.defer="password_confirmation"
                    class="h-13 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    placeholder="Confirm password"
                >
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="h-13 w-full rounded-2xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-70"
            >
                <span wire:loading.remove>Reset Password</span>
                <span wire:loading>Updating...</span>
            </button>
        </form>
    </div>
</div>