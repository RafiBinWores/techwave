<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon" href="{{ asset('assets/images/logo/logo.png') }}" type="image/x-icon">

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
<!-- Global Overlay -->
    <div
        class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.30),rgba(15,23,42,0.88)_55%,rgba(2,6,23,0.95)_100%)]">
    </div>
    <div class="fixed inset-0 -z-10 bg-slate-950/30"></div>

        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-30 -left-20 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl">
            </div>
            <div class="absolute top-[20%] -right-25 w-90 h-90 bg-sky-400/20 rounded-full blur-3xl">
            </div>
            <div class="absolute -bottom-30 left-[10%] w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl">
            </div>
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

    @stack('scripts')

    @livewireScripts
</body>

</html>
