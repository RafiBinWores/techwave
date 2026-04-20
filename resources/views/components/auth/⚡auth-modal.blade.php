<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div x-data="{
        openAuth: false,
        mode: 'login',
        accountType: 'personal',
        showLoginPassword: false,
        showRegisterPassword: false,
        showRegisterConfirmPassword: false
    }"
    @open-auth.window="
        openAuth = true;
        mode = $event.detail.mode ?? 'login';
    ">

    <!-- Modal -->
    <div x-show="openAuth" x-transition.opacity
        class="fixed inset-0 z-[999] flex items-center justify-center overflow-y-auto px-4 py-6"
        style="display: none;">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md" @click="openAuth = false"></div>

        <!-- Modal panel -->
        <div x-show="openAuth" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative z-10 w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-[34px] border border-white/10 bg-white/8 shadow-[0_30px_100px_rgba(0,0,0,0.35)] backdrop-blur-2xl">

            <!-- glow -->
            <div class="absolute left-0 top-0 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-52 w-52 rounded-full bg-blue-500/12 blur-3xl"></div>

            <div class="relative z-10 grid max-h-[90vh] lg:grid-cols-[0.9fr_1.1fr]">
                <!-- Left -->
                <div class="hidden lg:flex flex-col justify-between border-r border-white/10 bg-slate-950/20 p-8">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-xs text-blue-100/80 backdrop-blur-xl">
                            <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            Secure Access
                        </div>

                        <h2 class="mt-6 text-3xl font-bold text-white">
                            Access your
                            <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                digital workspace
                            </span>
                        </h2>

                        <p class="mt-4 text-sm leading-7 text-blue-100/68">
                            Sign in to manage tools, services, requests, and your business account from one place.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-white/6 p-4 backdrop-blur-xl">
                            <p class="text-sm font-semibold text-white">Personal Account</p>
                            <p class="mt-1 text-xs leading-6 text-blue-100/60">
                                Use tools, save activity, and manage your personal workspace.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/6 p-4 backdrop-blur-xl">
                            <p class="text-sm font-semibold text-white">Company Account</p>
                            <p class="mt-1 text-xs leading-6 text-blue-100/60">
                                Manage company profile, team workflows, service requests, and more.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right -->
                <div class="auth-scroll max-h-[90vh] overflow-y-auto p-6 sm:p-8">
                    <!-- top -->
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-bold text-white"
                                x-text="
                                    mode === 'login' ? 'Welcome back' :
                                    mode === 'register' ? 'Create account' :
                                    'Reset password'
                                "></h3>

                            <p class="mt-2 text-sm text-blue-100/65">
                                <span x-show="mode === 'login'">Sign in to continue.</span>
                                <span x-show="mode === 'register'">Create your account and get started.</span>
                                <span x-show="mode === 'forgot'">We’ll send you a password reset link.</span>
                            </p>
                        </div>

                        <button @click="openAuth = false"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white/80 transition hover:bg-white/12 hover:text-white cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- account type -->
                    <div class="mt-5 flex justify-center" x-show="mode !== 'forgot'">
                        <div
                            class="inline-grid grid-cols-2 rounded-full border border-white/10 bg-white/5 p-1 backdrop-blur-xl">
                            <button @click="accountType = 'personal'"
                                :class="accountType === 'personal'
                                    ? 'bg-linear-to-r from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/25'
                                    : 'text-blue-100/70 hover:text-white'"
                                class="min-w-[120px] rounded-full px-4 py-2.5 text-sm font-semibold transition">
                                Personal
                            </button>

                            <button @click="accountType = 'company'"
                                :class="accountType === 'company'
                                    ? 'bg-linear-to-r from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/25'
                                    : 'text-blue-100/70 hover:text-white'"
                                class="min-w-[120px] rounded-full px-4 py-2.5 text-sm font-semibold transition">
                                Company
                            </button>
                        </div>
                    </div>

                    <!-- LOGIN FORM -->
                    <form x-show="mode === 'login'" class="space-y-4" style="display: none;">
                        <template x-if="accountType === 'company'">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Email Address</label>
                                <input type="email" placeholder="Enter your company email" class="auth-input">
                            </div>
                        </template>

                        <template x-if="accountType === 'personal'">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Email Address</label>
                                <input type="email" placeholder="Enter your email" class="auth-input">
                            </div>
                        </template>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-blue-50/85">Password</label>
                            <div class="relative">
                                <input :type="showLoginPassword ? 'text' : 'password'"
                                    placeholder="Enter your password"
                                    class="auth-input pr-12">

                                <button type="button"
                                    @click="showLoginPassword = !showLoginPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-100/55 transition hover:text-white">
                                    <svg x-show="!showLoginPassword" xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>

                                    <svg x-show="showLoginPassword" xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8" style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.477 10.483A3 3 0 0013.5 13.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.88 4.24A10.966 10.966 0 0112 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644a11.052 11.052 0 01-4.293 5.284M6.228 6.228A11.05 11.05 0 002.036 11.678a1.012 1.012 0 000 .644C3.423 16.49 7.36 19.5 12 19.5c1.62 0 3.158-.367 4.534-1.023" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-blue-100/65">
                                <input type="checkbox" class="rounded border-white/20 bg-white/10">
                                Remember me
                            </label>

                            <button type="button" @click="mode = 'forgot'"
                                class="text-sm font-medium text-cyan-200 hover:text-white cursor-pointer">
                                Forgot password?
                            </button>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                            Sign In
                        </button>

                        <!-- social login -->
                        <div class="pt-2">
                            <div class="mb-4 flex items-center gap-4">
                                <div class="h-px flex-1 bg-white/10"></div>
                                <span class="text-xs uppercase tracking-[0.18em] text-blue-100/40">or continue
                                    with</span>
                                <div class="h-px flex-1 bg-white/10"></div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <a href="#"
                                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                    <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/google/default.svg"
                                        alt="Google" width="24" height="24" />
                                    Google
                                </a>

                                <a href="#"
                                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                    <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/facebook/default.svg"
                                        alt="Facebook" width="26" height="26" />
                                    Facebook
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- FORGOT FORM -->
                    <form x-show="mode === 'forgot'" class="space-y-4" style="display:none;">
                        <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-blue-500/15 text-cyan-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 0h10.5A2.25 2.25 0 0119.5 12.75v6A2.25 2.25 0 0117.25 21h-10.5A2.25 2.25 0 014.5 18.75v-6A2.25 2.25 0 016.75 10.5z" />
                            </svg>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-blue-50/85">
                                Email Address
                            </label>

                            <input type="email" placeholder="Enter your email" class="auth-input">
                        </div>

                        <p class="text-sm text-blue-100/60 leading-6">
                            Enter your account email and we’ll send a secure reset link.
                        </p>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                            Send Reset Link
                        </button>

                        <button type="button" @click="mode = 'login'"
                            class="w-full rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-medium text-white hover:bg-white/10 transition cursor-pointer">
                            Back to Login
                        </button>
                    </form>

                    <!-- REGISTER FORM -->
                    <form x-show="mode === 'register'" class="space-y-4" style="display: none;">
                        <template x-if="accountType === 'company'">
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Name</label>
                                    <input type="text" placeholder="Enter company name" class="auth-input">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Logo</label>
                                    <input type="file"
                                        class="block w-full rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm text-white file:mr-4 file:rounded-full file:border-0 file:bg-white/12 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-white/16">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Email</label>
                                    <input type="email" placeholder="Enter company email" class="auth-input">
                                </div>
                            </div>
                        </template>

                        <template x-if="accountType === 'personal'">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Email Address</label>
                                <input type="email" placeholder="Enter your email" class="auth-input">
                            </div>
                        </template>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Password</label>
                                <div class="relative">
                                    <input :type="showRegisterPassword ? 'text' : 'password'"
                                        placeholder="Create password"
                                        class="auth-input pr-12">

                                    <button type="button"
                                        @click="showRegisterPassword = !showRegisterPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-100/55 transition hover:text-white">
                                        <svg x-show="!showRegisterPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>

                                        <svg x-show="showRegisterPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m3 3 18 18" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10.477 10.483A3 3 0 0013.5 13.5" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.88 4.24A10.966 10.966 0 0112 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644a11.052 11.052 0 01-4.293 5.284M6.228 6.228A11.05 11.05 0 002.036 11.678a1.012 1.012 0 000 .644C3.423 16.49 7.36 19.5 12 19.5c1.62 0 3.158-.367 4.534-1.023" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Confirm Password</label>
                                <div class="relative">
                                    <input :type="showRegisterConfirmPassword ? 'text' : 'password'"
                                        placeholder="Confirm password"
                                        class="auth-input pr-12">

                                    <button type="button"
                                        @click="showRegisterConfirmPassword = !showRegisterConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-100/55 transition hover:text-white">
                                        <svg x-show="!showRegisterConfirmPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>

                                        <svg x-show="showRegisterConfirmPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m3 3 18 18" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10.477 10.483A3 3 0 0013.5 13.5" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.88 4.24A10.966 10.966 0 0112 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.437 0 .644a11.052 11.052 0 01-4.293 5.284M6.228 6.228A11.05 11.05 0 002.036 11.678a1.012 1.012 0 000 .644C3.423 16.49 7.36 19.5 12 19.5c1.62 0 3.158-.367 4.534-1.023" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm leading-6 text-blue-100/60">
                            By continuing, you agree to the
                            <a href="#" class="font-medium text-cyan-200 hover:text-white">terms</a>,
                            <a href="#" class="font-medium text-cyan-200 hover:text-white">privacy policy</a>,
                            and account rules.
                        </p>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                            Create Account
                        </button>

                        <!-- social login -->
                        <div class="pt-2">
                            <div class="mb-4 flex items-center gap-4">
                                <div class="h-px flex-1 bg-white/10"></div>
                                <span class="text-xs uppercase tracking-[0.18em] text-blue-100/40">or continue
                                    with</span>
                                <div class="h-px flex-1 bg-white/10"></div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <a href="#"
                                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                    <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/google/default.svg"
                                        alt="Google" width="24" height="24" />
                                    Google
                                </a>

                                <a href="#"
                                    class="inline-flex items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/8 px-4 py-3 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                    <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/facebook/default.svg"
                                        alt="Facebook" width="26" height="26" />
                                    Facebook
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- footer text -->
                    <p x-show="mode !== 'forgot'" class="mt-6 text-center text-sm text-blue-100/60">
                        <span x-show="mode === 'login'">
                            Don’t have an account?
                            <button type="button" @click="mode = 'register'"
                                class="font-semibold text-cyan-200 hover:text-white cursor-pointer">
                                Register
                            </button>
                        </span>

                        <span x-show="mode === 'register'">
                            Already have an account?
                            <button type="button" @click="mode = 'login'"
                                class="font-semibold text-cyan-200 hover:text-white cursor-pointer">
                                Sign in
                            </button>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
