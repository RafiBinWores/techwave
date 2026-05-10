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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-[#0f172a] text-slate-100 antialiased">


    <div class="relative min-h-screen overflow-hidden bg-slate-50 dark:bg-[#0f172a]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.16),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.12),transparent_28%)]"></div>

        <main class="relative z-10 min-h-screen">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')

    @livewireScripts
</body>

</html>
