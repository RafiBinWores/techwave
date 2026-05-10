<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/logo/logo.png') }}" type="image/x-icon">

    <title>{{ $title ?? config('app.name') }}</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/css/intlTelInput.css">

    <style>
    .iti {
        width: 100%;
    }

    /* Keep selected country button glassy */
    .iti--separate-dial-code .iti__selected-flag {
        border-radius: 1rem 0 0 1rem;
        background: rgba(255, 255, 255, 0.08) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.10);
        transition: all 0.25s ease;
    }

    .iti--separate-dial-code .iti__selected-flag:hover {
        background: rgba(255, 255, 255, 0.14) !important;
    }

    .iti__selected-dial-code {
        color: rgba(219, 234, 254, 0.9);
        font-size: 0.875rem;
        font-weight: 600;
    }

    .iti__arrow {
        border-top-color: rgba(219, 234, 254, 0.75);
    }

    .iti__arrow--up {
        border-bottom-color: rgba(219, 234, 254, 0.75);
    }

    .iti input#customer_phone {
        padding-left: 110px !important;
    }

    /* Solid dropdown panel - no see-through */
    .iti__country-container .iti__dropdown-content,
    .iti__country-list {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        background: #0f172a !important;
        box-shadow: 0 24px 80px rgba(2, 6, 23, 0.65);
        overflow: hidden;
    }

    /* Search box solid */
    .iti__search-input {
        margin: 10px;
        width: calc(100% - 20px);
        border-radius: 0.9rem;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #111827;
        color: #ffffff;
        padding: 0.75rem 0.9rem;
        outline: none;
        font-size: 0.875rem;
    }

    .iti__search-input::placeholder {
        color: rgba(191, 219, 254, 0.45);
    }

    .iti__search-input:focus {
        border-color: rgba(103, 232, 249, 0.7);
        background: #1e293b;
    }

    .iti__country {
        padding: 10px 14px;
        background: #0f172a;
        color: rgba(219, 234, 254, 0.82);
        transition: all 0.2s ease;
    }

    .iti__country:hover,
    .iti__highlight {
        background: #164e63 !important;
        color: #ffffff;
    }

    .iti__country-name {
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.875rem;
    }

    .iti__dial-code {
        color: rgba(125, 211, 252, 0.9);
        font-size: 0.8125rem;
    }

    .iti__divider {
        border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        margin: 6px 0;
    }

    .iti__country-list::-webkit-scrollbar {
        width: 8px;
    }

    .iti__country-list::-webkit-scrollbar-track {
        background: #0f172a;
    }

    .iti__country-list::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: #334155;
    }

    .iti__country-list::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }

    /* Mobile fullscreen dropdown solid */
    .iti--fullscreen-popup .iti__dropdown-content {
        background: #0f172a !important;
    }
</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    <!-- Full Website Background Video -->
    <div class="fixed inset-0 -z-20">
        <video autoplay muted loop playsinline preload="metadata" poster="{{ asset('assets/images/logo/logo.png') }}"
            class="w-full h-full object-cover">
            <source src="{{ asset('assets/videos/matrix1.mp4') }}" type="video/mp4">
        </video>
    </div>

    <!-- Global Overlay -->
    <div
        class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.30),rgba(15,23,42,0.88)_55%,rgba(2,6,23,0.95)_100%)]">
    </div>
    <div class="fixed inset-0 -z-10 bg-slate-950/30"></div>

    <!-- Decorative Blur -->
    {{-- <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-30 -left-20 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl">
            </div>
            <div class="absolute top-[20%] -right-25 w-90 h-90 bg-sky-400/20 rounded-full blur-3xl">
            </div>
            <div class="absolute -bottom-30 left-[10%] w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl">
            </div>
        </div> --}}


    {{-- Navbar --}}
    <div class="max-w-350 mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <livewire:shared.navbar />
    </div>

    <div x-data="{ show: false, message: '' }"
        x-on:auth-success.window="
        show=true;
        message=$event.detail.message;
        setTimeout(() => show=false, 3000)
    "
        x-show="show" x-transition
        class="fixed top-5 right-5 z-9999 rounded-2xl bg-emerald-500 px-5 py-3 text-white shadow-xl"
        style="display:none;">
        <span x-text="message"></span>
    </div>


    {{-- Main content --}}
    <main>
        {{ $slot }}
    </main>


    {{-- Footer --}}
    <x-layouts::partials.footer />

    {{-- Auth Modal --}}
    <livewire:auth.auth-modal wire:key="global-auth-modal" />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);

            if (params.get('openAuth') === 'login') {
                window.dispatchEvent(new CustomEvent('open-auth', {
                    detail: {
                        mode: 'login'
                    }
                }));
            }
        });
    </script>

    @stack('scripts')

    @livewireScripts
</body>

</html>
