<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body>
    <!-- Full Website Background Video -->
    <div class="fixed inset-0 -z-20">
        <video autoplay muted loop playsinline preload="metadata" poster="{{ asset('images/video-fallback.jpg') }}"
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
        {{-- @persist('site-navbar')
            
        @endpersist --}}
        <livewire:shared.navbar />
    </div>


    {{-- Main content --}}
    <main>
        {{ $slot }}
    </main>


    {{-- Footer --}}
    <x-layouts::partials.footer />

    {{-- Auth Modal --}}
    <livewire:auth.auth-modal wire:key="global-auth-modal" />

    @stack('scripts')

    @livewireScripts
</body>

</html>
