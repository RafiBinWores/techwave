<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="relative min-h-screen overflow-hidden text-white">
    <section class="flex min-h-screen items-center justify-center px-4 py-16">
        <div class="w-full max-w-2xl">
            <div class="relative overflow-hidden rounded-[36px] border border-white/10 bg-white/8 p-6 shadow-[0_30px_100px_rgba(0,0,0,0.32)] backdrop-blur-2xl sm:p-10">
                <!-- Card Glow -->
                <div class="absolute left-0 top-0 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-52 w-52 rounded-full bg-blue-500/12 blur-3xl"></div>

                <div class="relative z-10 text-center">
                    <!-- Icon -->
                    <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border border-emerald-300/20 bg-emerald-400/10 shadow-[0_0_45px_rgba(52,211,153,0.18)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-emerald-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-4 py-2 text-xs font-medium text-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        Email Verified
                    </div>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl">
                        Your email has been
                        <span class="bg-linear-to-r from-emerald-300 to-cyan-300 bg-clip-text text-transparent">
                            verified successfully
                        </span>
                    </h1>

                    <p class="mx-auto mt-5 max-w-xl text-sm leading-7 text-blue-100/70 sm:text-base">
                        Your account is now active. You can access your dashboard, manage services, open tickets,
                        and continue using your client workspace.
                    </p>

                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('account.dashboard') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                            Go to Dashboard
                        </a>

                        <a href="{{ route('home') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-xl transition hover:bg-white/12">
                            Back to Home
                        </a>
                    </div>

                    <div class="mt-8 rounded-[24px] border border-white/10 bg-white/6 p-5 text-left backdrop-blur-xl">
                        <p class="text-sm font-semibold text-white">What’s next?</p>

                        <div class="mt-4 space-y-3">
                            <div class="flex items-start gap-3 text-sm text-blue-100/68">
                                <span class="mt-1 h-2 w-2 rounded-full bg-cyan-300"></span>
                                Complete your profile information.
                            </div>

                            <div class="flex items-start gap-3 text-sm text-blue-100/68">
                                <span class="mt-1 h-2 w-2 rounded-full bg-cyan-300"></span>
                                Review your services and proposals.
                            </div>

                            <div class="flex items-start gap-3 text-sm text-blue-100/68">
                                <span class="mt-1 h-2 w-2 rounded-full bg-cyan-300"></span>
                                Open a support ticket whenever you need help.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-blue-100/45">
                Thank you for verifying your account.
            </p>
        </div>
    </section>
</div>