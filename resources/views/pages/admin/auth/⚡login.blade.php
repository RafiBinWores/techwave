<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin-auth')] class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean'],
        ]);

        $user = User::where('email', $validated['email'])
            ->whereIn('role', [UserRole::ADMIN, UserRole::MANAGER, UserRole::STAFF, UserRole::ADMIN_MANAGER])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $this->addError('email', 'These credentials do not match our admin records.');
            return;
        }

        Auth::login($user, $validated['remember']);
        request()->session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: true);
    }
};
?>

<div class="flex min-h-screen flex-col lg:flex-row">
    <!-- Left panel -->
    <section class="relative hidden w-full max-w-140 overflow-hidden bg-[#111827] lg:flex lg:w-[46%]">
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(37,99,235,0.22),rgba(15,23,42,0.4))]"></div>

        <div class="absolute -left-16 top-10 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <div class="relative z-10 flex min-h-screen w-full flex-col justify-between p-10 xl:p-14">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div
                        class="flex h-14 w-14 p-0.5 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10 backdrop-blur-xl">
                        @php
                            use App\Models\SiteSetting;

                            $siteSetting = SiteSetting::current();

                            $logo = $siteSetting->logo
                                ? asset('storage/' . $siteSetting->logo)
                                : asset('assets/images/logo/logo.png');
                        @endphp

                        <img src="{{ $logo }}" alt="">
                    </div>
                    <div>
                        <p class="text-lg font-semibold tracking-wide text-white">TechWave Admin</p>
                        <p class="text-sm text-slate-300">Management Portal</p>
                    </div>
                </a>
            </div>

            <div class="max-w-md">
                <div
                    class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-medium text-blue-100 backdrop-blur-xl">
                    <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                    Secure admin access
                </div>

                <h1 class="text-4xl font-bold leading-tight text-white xl:text-5xl">
                    Welcome back to your
                    <span class="bg-linear-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">
                        control center
                    </span>
                </h1>

                <p class="mt-5 max-w-md text-base leading-7 text-slate-300">
                    Manage your platform, monitor business activity, and control users, services, and operations from
                    one modern dashboard.
                </p>

            </div>

            <div class="text-sm text-slate-400">
                © {{ now()->year }} TechWave. All rights reserved.
            </div>
        </div>
    </section>

    <!-- Right panel -->
    <section class="relative flex min-h-screen flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-10">
        <div class="w-full max-w-117.5">
            <div class="mb-8 text-center lg:hidden">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/25">
                        <svg viewBox="0 0 32 32" class="h-6 w-6 fill-white">
                            <path d="M16 3l10 6v14l-10 6L6 23V9l10-6zm0 3.3L9 10.2v11.6l7 3.9 7-3.9V10.2l-7-3.9z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-lg font-semibold text-slate-900 dark:text-white">TechWave Admin</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Management Portal</p>
                    </div>
                </a>
            </div>

            <div
                class="rounded-[28px] border border-slate-200/70 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8 dark:border-white/10 dark:bg-white/4 dark:shadow-[0_25px_70px_rgba(0,0,0,0.35)]">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Sign In
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Enter your admin credentials to access the dashboard.
                    </p>
                </div>

                <form wire:submit.prevent="login" class="space-y-5">
                    <div>
                        <label for="email"
                            class="mb-2.5 block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Email
                        </label>

                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15A2.25 2.25 0 002.25 6.75m19.5 0v.243a2.25 2.25 0 01-1.07 1.92l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.913a2.25 2.25 0 01-1.07-1.92V6.75" />
                                </svg>
                            </span>

                            <input id="email" type="email" wire:model.defer="email" placeholder="Enter your email"
                                class="h-13 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/3 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-blue-500">
                        </div>

                        @error('email')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ show: false }">
                        <div class="mb-2.5 flex items-center justify-between gap-3">
                            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Password
                            </label>

                            <a href="{{ route('admin.password.request') }}"
                                class="text-sm font-medium text-blue-600 transition hover:text-blue-500 dark:text-blue-400">
                                Forgot password?
                            </a>
                        </div>

                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 0h10.5A2.25 2.25 0 0119.5 12.75v6A2.25 2.25 0 0117.25 21h-10.5A2.25 2.25 0 014.5 18.75v-6A2.25 2.25 0 016.75 10.5z" />
                                </svg>
                            </span>

                            <input id="password" :type="show ? 'text' : 'password'" wire:model.defer="password"
                                placeholder="Enter your password"
                                class="h-13 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-12 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/3 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-blue-500">

                            <button type="button" @click="show = !show"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-700 dark:hover:text-slate-200">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>

                                <svg x-show="show" style="display:none;" xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.477 10.483A3 3 0 0013.5 13.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.88 4.24A10.966 10.966 0 0112 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644a11.052 11.052 0 01-4.293 5.284M6.228 6.228A11.05 11.05 0 002.036 11.678a1.012 1.012 0 000 .644C3.423 16.49 7.36 19.5 12 19.5c1.62 0 3.158-.367 4.534-1.023" />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-1">
                        <label class="inline-flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="remember"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-white/15 dark:bg-white/5">
                            Keep me signed in
                        </label>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="group relative inline-flex h-13 w-full cursor-pointer items-center justify-center overflow-hidden rounded-2xl bg-blue-600 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-80">

                        {{-- Shine animation --}}
                        <span
                            class="pointer-events-none absolute inset-y-0 -left-full w-1/3 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-[120%]">
                        </span>

                        {{-- Normal state --}}
                        <span wire:loading.remove wire:target="login"
                            class="relative inline-flex items-center justify-center gap-2">
                            <span>Sign In</span>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>

                        {{-- Loading state --}}
                        <span wire:loading wire:target="login"
                            class="relative inline-flex items-center justify-center gap-2">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white">
                            </span>

                            <span>Signing in...</span>
                        </span>
                    </button>
                </form>

                {{-- <div class="mt-8">
                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200 dark:border-white/10"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-4 text-xs font-medium uppercase tracking-[0.18em] text-slate-400 dark:bg-[#111827]">
                                secure access
                            </span>
                        </div>
                    </div>

                    <p class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
                        Need admin access?
                        <a href="{{ route('home') }}" class="font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            Contact super admin
                        </a>
                    </p>
                </div> --}}
            </div>
        </div>
    </section>
</div>
