<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function resendVerification(): void
    {
        if (! Auth::check()) {
            session()->flash('auth_error', 'Please login first.');
            $this->redirectRoute('home', navigate: true);
            return;
        }

        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('verified.success', navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('auth_success', 'A new verification email has been sent.');
    }
};
?>

<div class="px-4 py-12 text-white">
    <div class="mx-auto max-w-2xl rounded-4xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm text-cyan-200">
            Email Verification Required
        </div>

        <h1 class="text-3xl font-bold">Check your inbox</h1>

        <p class="mt-4 text-sm leading-7 text-slate-300">
            We sent a verification link to your email address. Please click the link in that email to verify your account.
        </p>

        @if (session('auth_success'))
            <div class="mt-5 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('auth_success') }}
            </div>
        @endif

        @if (session('auth_error'))
            <div class="mt-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('auth_error') }}
            </div>
        @endif

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <button
                wire:click="resendVerification"
                type="button"
                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5"
            >
                Resend Verification Email
            </button>

            <a
                href="{{ route('home') }}"
                class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10"
            >
                Back to Home
            </a>
        </div>
    </div>
</div>