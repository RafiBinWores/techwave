<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('home'), navigate: true);
    }
};
?>

<div>
    <nav class="glass-panel rounded-2xl px-4 py-4 sm:px-6" x-data="{ mobileMenu: false, userMenu: false }">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                <img src="https://techwave.asia/storage/services/light-logo-142x75.png" alt="Logo"
                    class="h-10 rounded-xl">
            </a>

            <div class="hidden items-center gap-8 text-sm font-medium text-blue-50/85 lg:flex">

                <a href="{{ route('home') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">Home</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.services') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">Services</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.tools.index') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">Tools</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.blogs.index') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">Blogs</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.about') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">About</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

                <a href="{{ route('client.contact') }}" wire:navigate wire:current.exact="text-white"
                    class="group relative px-1 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:text-white">

                    <span class="relative z-10">Contact</span>

                    <span
                        class="absolute inset-x-0 -bottom-0.5 h-px bg-linear-to-r from-transparent via-cyan-300 to-transparent scale-x-0 transition-transform duration-300 group-hover:scale-x-100 group-[.text-white]:scale-x-100">
                    </span>
                </a>

            </div>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    <div class="relative">
                        <button type="button" @click="userMenu = !userMenu"
                            class="flex items-center gap-3 rounded-full px-2 py-1.5 text-white transition hover:bg-white/5">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="text-sm font-semibold leading-none text-white">
                                {{ auth()->user()->name }}
                            </div>

                            <svg class="h-4 w-4 text-white/70 transition" :class="{ 'rotate-180': userMenu }"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="userMenu" x-transition @click.outside="userMenu = false" @click.stop
                            style="display: none;"
                            class="absolute right-0 top-full z-50 mt-0 w-56 overflow-hidden rounded-2xl border border-white/10 bg-slate-900/95 p-2 shadow-2xl backdrop-blur-xl">
                            <a href="{{ route('account.dashboard') }}"
                                class="block rounded-xl px-4 py-3 text-sm text-white transition hover:bg-white/10">
                                Profile
                            </a>

                            <a href="{{ route('account.dashboard') }}" wire:navigate
                                class="block rounded-xl px-4 py-3 text-sm text-white transition hover:bg-white/10">
                                Dashboard
                            </a>

                            <div class="my-2 border-t border-white/10"></div>

                            <form wire:submit.prevent="logout">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="block w-full rounded-xl px-4 py-3 text-left text-sm text-red-300 transition hover:bg-red-500/15 disabled:opacity-60 cursor-pointer">
                                    <span wire:loading.remove wire:target="logout">Logout</span>
                                    <span wire:loading wire:target="logout">Logging out...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))"
                        class="glass-chip cursor-pointer rounded-full px-5 py-2.5 font-medium text-blue-50 transition hover:bg-white/20">
                        Sign In
                    </button>

                    <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'register' } }))"
                        class="cursor-pointer rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-5 py-2.5 font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:scale-[1.02]">
                        Get Started
                    </button>
                @endauth
            </div>

            <button @click="mobileMenu = !mobileMenu"
                class="glass-chip flex h-11 w-11 items-center justify-center rounded-xl text-white lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div x-show="mobileMenu" x-transition style="display: none;"
            class="mt-4 border-t border-white/10 pt-4 lg:hidden">
            <div class="flex flex-col gap-3 text-sm text-blue-50/85">
                <a href="{{ route('home') }}" wire:navigate class="glass-soft rounded-xl px-4 py-3">Home</a>
                <a href="{{ route('client.services') }}" wire:navigate
                    class="glass-soft rounded-xl px-4 py-3">Services</a>
                <a href="{{ route('client.tools.index') }}" wire:navigate
                    class="glass-soft rounded-xl px-4 py-3">Tools</a>
                <a href="{{ route('client.blogs.index') }}" wire:navigate
                    class="glass-soft rounded-xl px-4 py-3">Blogs</a>
                <a href="{{ route('client.about') }}" wire:navigate class="glass-soft rounded-xl px-4 py-3">About</a>
                <a href="{{ route('client.contact') }}" wire:navigate
                    class="glass-soft rounded-xl px-4 py-3">Contact</a>

                @auth
                    <div class="mt-2 border-t border-white/10 pt-3">
                        <div class="flex items-center gap-3 rounded-xl px-2 py-2 text-white">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="text-sm font-semibold">
                                {{ auth()->user()->name }}
                            </div>
                        </div>

                        <div class="mt-2 flex flex-col gap-2">
                            <a href="#" class="glass-soft rounded-xl px-4 py-3">Profile</a>

                            <a href="{{ route('account.dashboard') }}" wire:navigate
                                class="glass-soft rounded-xl px-4 py-3">
                                Dashboard
                            </a>

                            <form wire:submit.prevent="logout">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="w-full rounded-xl bg-red-500/15 px-4 py-3 text-left text-red-300 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="logout">Logout</span>
                                    <span wire:loading wire:target="logout">Logging out...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))"
                            class="glass-soft rounded-xl px-4 py-3 text-center font-medium">
                            Sign In
                        </button>

                        <button
                            @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'register' } }))"
                            class="rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-4 py-3 text-center font-semibold">
                            Get Started
                        </button>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
</div>
