<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">

    <title>{{ $title ?? config('app.name') }}</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body x-data="{ sidebarOpen: false, sidebarCollapsed: false }" class="min-h-screen text-white">

    <!-- Background Video -->
    <div class="fixed inset-0 -z-20">
        <video autoplay muted loop playsinline preload="metadata" poster="{{ asset('assets/images/matrix.webp') }}"
            class="w-full h-full object-cover">
            <source src="{{ asset('assets/videos/matrix1c.mp4') }}" type="video/mp4">
        </video>
    </div>

    <!-- Global Overlay -->
    <div
        class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.30),rgba(15,23,42,0.88)_55%,rgba(2,6,23,0.95)_100%)]">
    </div>
    <div class="fixed inset-0 -z-10 bg-slate-950/30"></div>

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

    <!-- Main Wrapper -->
    <div :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"
        class="flex flex-col min-h-screen transition-all duration-300">

        <!-- Topbar -->
        <livewire:shared.account-header />

        {{-- Sidebar --}}
        <livewire:shared.user-sidebar />

        <!-- Content -->
        <main class="p-4 sm:p-6 lg:p-stack-lg max-w-425 mx-auto w-full">
            {{ $slot }}
        </main>

        {{-- Toast Notifications --}}
        <livewire:common.toast-notification />

    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>
