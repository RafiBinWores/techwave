<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <!-- Full Website Background Video -->
    <div class="fixed inset-0 -z-20">
        <video autoplay muted loop playsinline preload="metadata" poster="{{ asset('images/video-fallback.jpg') }}"
            class="w-full h-full object-cover">
            <source src="{{ asset('assets/videos/matrix.mp4') }}" type="video/mp4">
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

    <section x-data="{ mobileMenu: false }" class="relative min-h-[100vh - 100px] md:min-h-screen overflow-hidden text-white">

        <div class="relative z-10">
            <div class="max-w-350 mx-auto px-4 sm:px-6 py-4">

                <!-- Hero -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">

                    <!-- Left -->
                    <div class="lg:max-w-162.5 order-1 lg:order-1 text-center lg:text-left">
                        <h1
                            class="text-[34px] leading-[1.05] sm:text-[48px] md:text-[64px] lg:text-[78px] font-extrabold tracking-tight text-white">
                            Smarter IT for<br>
                            Smart <span class="text-blue-300">Businesses.</span>
                        </h1>

                        <p class="mt-6 sm:mt-8 leading-[1.7] text-blue-50/80 max-w-2xl">
                            We deliver secure, cloud-ready, and future-proof technology solutions tailored to your
                            business needs.
                        </p>

                        <div class="mt-8 sm:mt-10 flex flex-wrap items-center justify-center lg:justify-start gap-4">
                            <a href="#"
                                class="inline-flex items-center justify-center px-6 py-3 md:px-8 md:py-4 rounded-full bg-gradient-to-r from-blue-500 to-sky-400 text-white font-semibold shadow-lg shadow-blue-500/30 hover:-translate-y-0.5 transition">
                                Get Started
                            </a>

                            <a href="#"
                                class="inline-flex items-center justify-center px-6 py-3 md:px-8 md:py-4 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 text-white font-semibold hover:bg-white/15 transition">
                                View all Service
                            </a>
                        </div>
                    </div>

                    <!-- Right Animated Images -->
                    <div class="relative order-1 lg:order-2">
                        <div class="relative mx-auto h-125 w-full max-w-155 sm:h-140">
                            <!-- main image -->
                            <div
                                class="absolute left-1/2 top-1/2 w-[80%] md:w-[72%] -translate-x-1/2 -translate-y-1/2 animate-[floatY_6s_ease-in-out_infinite] rounded-[28px] border border-white/15 bg-white/10 p-3 shadow-[0_20px_60px_rgba(0,0,0,0.35)] backdrop-blur-2xl">
                                <div class="overflow-hidden rounded-[22px]">
                                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80"
                                        alt="Team collaboration" class="h-70 w-full object-cover sm:h-100" />
                                </div>
                            </div>

                            <!-- top left card -->
                            <div
                                class="absolute left-0 top-10 w-32 animate-[floatY_5s_ease-in-out_infinite] rounded-xl md:rounded-[22px] border border-white/15 bg-white/10 p-2 shadow-[0_15px_40px_rgba(0,0,0,0.28)] backdrop-blur-xl sm:w-48">
                                <div class="overflow-hidden rounded-xl md:rounded-[18px]">
                                    <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80"
                                        alt="Creative workspace" class="h-20 w-full object-cover sm:h-32" />
                                </div>
                                <div class="md:p-3">
                                    <p class="text-xs md:text-sm font-semibold text-white">Creative Strategy</p>
                                    <p class="mt-1 text-[10px] md:text-xs text-blue-100/65">Strong ideas. Clear
                                        direction.</p>
                                </div>
                            </div>

                            <!-- top right card -->
                            <div
                                class="absolute right-0 top-4 w-30 md:w-44 animate-[floatY_7s_ease-in-out_infinite] rounded-xl lg:rounded-[22px] border border-white/15 bg-white/10 p-2 shadow-[0_15px_40px_rgba(0,0,0,0.28)] backdrop-blur-xl sm:w-52">
                                <div class="overflow-hidden rounded-lg md:rounded-[18px]">
                                    <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=800&q=80"
                                        alt="Business planning" class="h-20 w-full object-cover sm:h-36" />
                                </div>
                                <div class="md:p-3">
                                    <p class="text-xs md:text-sm font-semibold text-white">Business Planning</p>
                                    <p class="mt-1 text-[10px] md:text-xs text-blue-100/65">Built around real growth
                                        goals.</p>
                                </div>
                            </div>

                            <!-- bottom left small -->
                            <div
                                class="absolute bottom-10 left-2 md:left-6 w-28 md:w-36 animate-[floatY_6.5s_ease-in-out_infinite] rounded-xl md:rounded-[20px] border border-white/15 bg-white/10 p-2 shadow-[0_15px_35px_rgba(0,0,0,0.25)] backdrop-blur-xl sm:w-40">
                                <div class="overflow-hidden rounded-lg md:rounded-2xl">
                                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80"
                                        alt="Analytics dashboard" class="h-18 w-full object-cover sm:h-28" />
                                </div>
                            </div>

                            <!-- bottom right small -->
                            <div
                                class="absolute bottom-6 right-3 md:right-8 w-30 animate-[floatY_5.5s_ease-in-out_infinite] rounded-xl md:rounded-[20px] border border-white/15 bg-white/10 p-2 shadow-[0_15px_35px_rgba(0,0,0,0.25)] backdrop-blur-xl sm:w-40">
                                <div class="overflow-hidden rounded-lg md:rounded-2xl">
                                    <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80"
                                        alt="Technology development" class="h-20 w-full object-cover sm:h-28" />
                                </div>
                            </div>

                            <!-- decorative glow -->
                            <div class="absolute left-10 top-24 h-24 w-24 rounded-full bg-cyan-400/20 blur-3xl"></div>
                            <div class="absolute bottom-16 right-10 h-28 w-28 rounded-full bg-blue-500/20 blur-3xl">
                            </div>

                            <!-- dots -->
                            <div class="absolute left-[20%] top-[20%] h-3 w-3 rounded-full bg-cyan-300 animate-ping">
                            </div>
                            <div
                                class="absolute right-[18%] top-[32%] h-3 w-3 rounded-full bg-blue-400 animate-ping [animation-delay:0.7s]">
                            </div>
                            <div
                                class="absolute bottom-[22%] left-[30%] h-3 w-3 rounded-full bg-sky-300 animate-ping [animation-delay:1s]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trusted By -->
        <div class="mt-16 sm:mt-20" x-data="logoMarquee()" x-init="init()">
            <p class="text-center text-blue-100/65 text-xs sm:text-sm font-semibold tracking-[0.28em] uppercase mb-6">
                Trusted by Global Innovators
            </p>

            <div class="relative" x-ref="slider" @mouseenter="paused = true" @mouseleave="paused = false">
                <!-- left fade -->
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 z-10 w-16 bg-gradient-to-r from-slate-950/90 to-transparent">
                </div>

                <!-- right fade -->
                <div
                    class="pointer-events-none absolute inset-y-0 right-0 z-10 w-16 bg-gradient-to-l from-slate-950/90 to-transparent">
                </div>

                <div class="">
                    <div x-ref="track" class="flex w-max items-center gap-4 will-change-transform">

                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/google/wordmark.svg"
                                alt="Google" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/medium/default.svg"
                                alt="Medium" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/meta/default.svg"
                                alt="Meta" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/microsoft/default.svg"
                                alt="Microsoft" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/stripe/default.svg"
                                alt="Stripe" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/amazon/default.svg"
                                alt="Amazon" class="logo-img">
                        </div>
                        <div class="logo-card group">
                            <img src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/discord/default.svg"
                                alt="Discord" class="logo-img">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section class="pt-20 sm:py-24">
        <div class="max-w-350 mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14 lg:mb-20">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-chip text-xs sm:text-sm text-blue-100/85 mb-5">
                    <span class="w-2 h-2 bg-cyan-300 rounded-full animate-pulse"></span>
                    Our services
                </div>

                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white md-4 md:mb-5">
                    Precision-Engineered Services
                </h2>
                <p class="text-blue-100/70 text-sm md:text-base max-w-2xl mx-auto">
                    Elite digital solutions designed to work in symphony with your growth strategy.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                <!-- Card 1 -->
                <div class="md:col-span-3 service-card group">
                    <span class="shine-border"></span>
                    <span class="service-glow -top-14 -left-12"></span>

                    <div class="relative z-10">
                        <div
                            class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center mb-6 border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-cyan-200" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>

                        <h3 class="text-2xl font-bold text-white mb-4">Ironclad Security</h3>
                        <p class="text-blue-100/70 mb-6 leading-7">
                            Protect your systems with advanced security layers, stable cloud architecture, and
                            premium performance monitoring.
                        </p>

                        <ul class="space-y-3 text-sm font-medium text-blue-50/90">
                            <li class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-300"></span>
                                256-bit data protection
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-300"></span>
                                Smart access control
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-300"></span>
                                Real-time system monitoring
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="md:col-span-3 service-card group">
                    <span class="shine-border"></span>
                    <span class="service-glow -bottom-14 -right-10"></span>

                    <div class="relative z-10">
                        <div
                            class="w-14 h-14 rounded-2xl bg-sky-500/20 flex items-center justify-center mb-6 border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-cyan-200" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="22" x2="2" y1="6" y2="6" />
                                <line x1="22" x2="2" y1="18" y2="18" />
                                <line x1="6" x2="6" y1="2" y2="22" />
                                <line x1="18" x2="18" y1="2" y2="22" />
                            </svg>
                        </div>

                        <h3 class="text-2xl font-bold text-white mb-4">Avant-Garde Web Design</h3>
                        <p class="text-blue-100/70 mb-6 leading-7">
                            Create a memorable digital presence with premium layouts, smooth motion, and
                            futuristic glassmorphism styling.
                        </p>

                        <a href="#" class="inline-flex items-center gap-2 text-cyan-200 font-semibold">
                            Explore Portfolio
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="md:col-span-2 service-card group">
                    <span class="shine-border"></span>

                    <div class="relative z-10">
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center mb-5 border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-200"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="8" x="2" y="2" rx="2" ry="2" />
                                <rect width="20" height="8" x="2" y="14" rx="2" ry="2" />
                                <line x1="6" x2="6.01" y1="6" y2="6" />
                                <line x1="6" x2="6.01" y1="18" y2="18" />
                            </svg>
                        </div>

                        <h3 class="text-xl font-bold text-white mb-2">Managed Hosting</h3>
                        <p class="text-sm text-blue-100/70 leading-7">
                            Fast infrastructure, CDN-ready setup, and modern deployment support.
                        </p>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="md:col-span-4 service-card group flex flex-col md:flex-row gap-8 items-center">
                    <span class="shine-border"></span>
                    <span class="service-glow bottom-0 right-6"></span>

                    <div class="relative z-10 flex-1">
                        <div
                            class="w-12 h-12 rounded-2xl bg-cyan-500/20 flex items-center justify-center mb-5 border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-cyan-200" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M16 7h6v6" />
                                <path d="m22 7-8.5 8.5-5-5L2 17" />
                            </svg>
                        </div>

                        <h3 class="text-xl font-bold text-white mb-2">Growth & Marketing</h3>
                        <p class="text-sm text-blue-100/70 leading-7">
                            Data-driven visuals, conversion-focused layouts, and modern user journeys that
                            help brands grow faster.
                        </p>
                    </div>

                    <div
                        class="hidden md:relative z-10 shrink-0 w-32 h-32 rounded-3xl glass-soft md:flex items-center justify-center border border-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-200" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M11 13H7" />
                            <path d="M19 9h-4" />
                            <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                            <rect x="15" y="5" width="4" height="12" rx="1" />
                            <rect x="7" y="8" width="4" height="9" rx="1" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Show All Services Button -->
            <div class="mt-10 flex justify-center">
                <a href="#all-services" class="service-btn">
                    Show All Services
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Company Overview -->
    <section class="relative overflow-hidden py-20 md:py-15 lg:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
                <!-- Left Content -->
                <div class="relative order-2 lg:order-1">
                    <div class="mb-5 flex justify-center lg:justify-start">
                        <div
                            class="inline-flex items-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                            <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            Get To Know Us
                        </div>
                    </div>

                    <h2 class="md:mt-6 text-3xl font-bold text-white sm:text-4xl lg:text-5xl text-center lg:text-left">
                        We build modern digital experiences
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            that move businesses forward
                        </span>
                    </h2>

                    <p class="mt-4 md:mt-6 lg:max-w-2xl text-sm md:text-base text-blue-100/70 text-center lg:text-left">
                        Our team blends strategy, design, and technology to deliver scalable solutions for fast-growing
                        brands. From websites and SaaS platforms to automation and digital transformation, we prioritize
                        performance, usability, and long-term value. <br> <br>
                        With 12+ years of experience, we offer end-to-end
                        IT services, including Virtual IT Department, Web Development, Cybersecurity, Email, Hosting,
                        CCTV, Networking, and Digital Marketing.
                    </p>

                    {{-- <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:bg-white/10">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/20 text-cyan-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white">Smart Solutions</h3>
                            <p class="mt-2 text-sm leading-6 text-blue-100/65">
                                Tailored digital systems built for real growth, efficiency, and results.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:bg-white/10">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-sky-500/20 text-sky-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 6.75v10.5m-4.5-7.5v7.5m-4.5-4.5v4.5M4.5 19.5h15" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white">Scalable Growth</h3>
                            <p class="mt-2 text-sm leading-6 text-blue-100/65">
                                Flexible and future-ready systems that grow with your business.
                            </p>
                        </div>
                    </div> --}}

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-3 gap-4 text-center md:text-left">
                        <div class="rounded-2xl border border-white/10 bg-white/6 px-5 py-4 backdrop-blur-xl">
                            <p class="text-2xl sm:text-3xl font-bold text-white">120+</p>
                            <p class="mt-1 text-xs sm:text-sm text-blue-100/60">Projects delivered</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/6 px-5 py-4 backdrop-blur-xl">
                            <p class="text-2xl sm:text-3xl font-bold text-white">98%</p>
                            <p class="mt-1 text-xs sm:text-sm text-blue-100/60">Client satisfaction</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/6 px-5 py-4 backdrop-blur-xl">
                            <p class="text-2xl sm:text-3xl font-bold text-white">24/7</p>
                            <p class="mt-1 text-xs sm:text-sm text-blue-100/60">Support availability</p>
                        </div>
                    </div>
                </div>

                <!-- Right Animated Images -->
                <div class="relative order-2 lg:order-2">
                    <div
                        class="relative bg-white/10 backdrop-blur-xl rounded-[20px] sm:rounded-[28px] p-2.5 sm:p-5 md:p-6 shadow-[0_20px_60px_rgba(0,0,0,0.25)] border border-white/20 max-w155 mx-auto overflow-hidden">

                        <!-- soft glow -->
                        <div
                            class="absolute top-4 left-4 sm:top-10 sm:left-10 w-20 sm:w-32 h-20 sm:h-32 bg-blue-300/20 rounded-full blur-3xl animate-pulse">
                        </div>
                        <div
                            class="absolute bottom-4 right-4 sm:bottom-10 sm:right-10 w-24 sm:w-40 h-24 sm:h-40 bg-sky-300/20 rounded-full blur-3xl animate-pulse">
                        </div>

                        <div
                            class="relative rounded-[18px] sm:rounded-[22px] bg-slate-900/30 backdrop-blur-md p-2.5 sm:p-5 md:p-8 border border-white/10 min-h-65 sm:min-h-105 md:min-h-117.5 flex items-center justify-center overflow-hidden">

                            <div
                                class="relative w-full max-w-[320px] sm:max-w-115 h-57.5 sm:h-85 md:h-90 scale-[0.84] xs:scale-[0.9] sm:scale-100 origin-center">

                                <!-- SVG lines -->
                                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 460 360" fill="none"
                                    aria-hidden="true">
                                    <path d="M230 180 L110 80" stroke="#93C5FD" stroke-width="2"
                                        stroke-dasharray="8 8">
                                        <animate attributeName="stroke-dashoffset" from="16" to="0"
                                            dur="1.5s" repeatCount="indefinite" />
                                    </path>
                                    <path d="M230 180 L350 90" stroke="#93C5FD" stroke-width="2"
                                        stroke-dasharray="8 8">
                                        <animate attributeName="stroke-dashoffset" from="16" to="0"
                                            dur="1.8s" repeatCount="indefinite" />
                                    </path>
                                    <path d="M230 180 L120 280" stroke="#93C5FD" stroke-width="2"
                                        stroke-dasharray="8 8">
                                        <animate attributeName="stroke-dashoffset" from="16" to="0"
                                            dur="1.7s" repeatCount="indefinite" />
                                    </path>
                                    <path d="M230 180 L355 270" stroke="#93C5FD" stroke-width="2"
                                        stroke-dasharray="8 8">
                                        <animate attributeName="stroke-dashoffset" from="16" to="0"
                                            dur="2s" repeatCount="indefinite" />
                                    </path>
                                    <path d="M230 180 L230 50" stroke="#BFDBFE" stroke-width="2"
                                        stroke-dasharray="8 8">
                                        <animate attributeName="stroke-dashoffset" from="16" to="0"
                                            dur="1.6s" repeatCount="indefinite" />
                                    </path>
                                </svg>

                                <!-- center -->
                                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <div class="relative flex items-center justify-center">
                                        <div
                                            class="absolute w-20 sm:w-32 md:w-36 h-20 sm:h-32 md:h-36 rounded-full bg-blue-200/20 blur-2xl animate-pulse">
                                        </div>
                                        <div
                                            class="absolute w-16 sm:w-24 h-16 sm:h-24 rounded-full border border-blue-300/40 animate-spin [animation-duration:10s]">
                                        </div>
                                        <div
                                            class="absolute w-11 sm:w-16 h-11 sm:h-16 rounded-full border border-sky-300/50 animate-spin [animation-duration:7s] [animation-direction:reverse]">
                                        </div>

                                        <div
                                            class="relative z-10 w-11 sm:w-18 md:w-20 h-11 sm:h-18 md:h-20 rounded-2xl bg-linear-to-br from-blue-600 to-sky-400 shadow-xl flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-6 sm:w-5 md:w-10 h-6 sm:h-5 md:h-10 text-white"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.75 3v2.25m4.5-2.25v2.25M4.5 9.75h15M6.75 19.5h10.5A2.25 2.25 0 0019.5 17.25V8.25A2.25 2.25 0 0017.25 6H6.75A2.25 2.25 0 004.5 8.25v9A2.25 2.25 0 006.75 19.5z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- top -->
                                <div
                                    class="absolute left-1/2 top-0 -translate-x-1/2 bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border border-white/70 px-2.5 sm:px-5 py-2 sm:py-4 animate-bounce [animation-duration:3s] max-w-33 sm:max-w-none">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 sm:w-10 h-7 sm:h-10 rounded-lg sm:rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 sm:w-5 h-3.5 sm:h-5 text-blue-600" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] sm:text-sm font-semibold text-slate-800 leading-tight">
                                                Cloud Ready</p>
                                            <p class="text-[9px] sm:text-xs text-slate-500 leading-tight">
                                                Modern
                                                infrastructure</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- left -->
                                <div
                                    class="absolute left-0 top-12 sm:top-14 bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border border-white/70 px-2.5 sm:px-4 py-2 sm:py-4 animate-pulse max-w-31.5 sm:max-w-none">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 sm:w-10 h-7 sm:h-10 rounded-lg sm:rounded-xl bg-sky-50 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 sm:w-5 h-3.5 sm:h-5 text-sky-600" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4" />
                                                <path d="M14 13.12c0 2.38 0 6.38-1 8.88" />
                                                <path d="M17.29 21.02c.12-.6.43-2.3.5-3.02" />
                                                <path d="M2 12a10 10 0 0 1 18-6" />
                                                <path d="M2 16h.01" />
                                                <path d="M21.8 16c.2-2 .131-5.354 0-6" />
                                                <path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2" />
                                                <path d="M8.65 22c.21-.66.45-1.32.57-2" />
                                                <path d="M9 6.8a6 6 0 0 1 9 5.2v2" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] sm:text-sm font-semibold text-slate-800 leading-tight">
                                                Secure System</p>
                                            <p class="text-[9px] sm:text-xs text-slate-500 leading-tight">
                                                Protected
                                                workflow</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- right -->
                                <div
                                    class="absolute right-0 top-14 sm:top-16 bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border border-white/70 px-2.5 sm:px-4 py-2 sm:py-4 animate-pulse max-w-31.5 sm:max-w-none">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 sm:w-10 h-7 sm:h-10 rounded-lg sm:rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 sm:w-5 h-3.5 sm:h-5 text-indigo-600" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7.5 14.25l4.5-4.5 4.5 4.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7.5 9.75l4.5 4.5 4.5-4.5" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] sm:text-sm font-semibold text-slate-800 leading-tight">
                                                Scalable Stack</p>
                                            <p class="text-[9px] sm:text-xs text-slate-500 leading-tight">Built
                                                to
                                                grow</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- bottom left -->
                                <div
                                    class="absolute left-1 sm:left-4 bottom-1 sm:bottom-4 bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border border-white/70 px-2.5 sm:px-4 py-2 sm:py-4 animate-bounce [animation-duration:3.5s] max-w-31.5 sm:max-w-none">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 sm:w-10 h-7 sm:h-10 rounded-lg sm:rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 sm:w-5 h-3.5 sm:h-5 text-emerald-600" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12l2 2 4-4" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] sm:text-sm font-semibold text-slate-800 leading-tight">
                                                Reliable Support</p>
                                            <p class="text-[9px] sm:text-xs text-slate-500 leading-tight">
                                                Always
                                                connected</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- bottom right -->
                                <div
                                    class="absolute right-1 sm:right-2 bottom-4 sm:bottom-8 bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border border-white/70 px-2.5 sm:px-4 py-2 sm:py-4 animate-bounce [animation-duration:4s] max-w-31.5 sm:max-w-none">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-7 sm:w-10 h-7 sm:h-10 rounded-lg sm:rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 sm:w-5 h-3.5 sm:h-5 text-violet-600" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] sm:text-sm font-semibold text-slate-800 leading-tight">
                                                Fast Delivery</p>
                                            <p class="text-[9px] sm:text-xs text-slate-500 leading-tight">Quick
                                                implementation</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dots -->
                                <div
                                    class="absolute left-[23%] top-[32%] w-2 sm:w-3 h-2 sm:h-3 bg-blue-500 rounded-full animate-ping">
                                </div>
                                <div
                                    class="absolute right-[25%] top-[35%] w-2 sm:w-3 h-2 sm:h-3 bg-sky-400 rounded-full animate-ping [animation-delay:0.4s]">
                                </div>
                                <div
                                    class="absolute left-[26%] bottom-[24%] w-2 sm:w-3 h-2 sm:h-3 bg-indigo-400 rounded-full animate-ping [animation-delay:0.7s]">
                                </div>
                                <div
                                    class="absolute right-[24%] bottom-[25%] w-2 sm:w-3 h-2 sm:h-3 bg-emerald-400 rounded-full animate-ping [animation-delay:1s]">
                                </div>
                            </div>
                        </div>

                        <div
                            class="absolute -top-5 -right-5 w-14 sm:w-16 h-14 sm:h-16 bg-blue-200/30 rounded-2xl blur-xl opacity-70 animate-pulse">
                        </div>
                        <div
                            class="absolute -bottom-4 -left-4 w-16 sm:w-20 h-16 sm:h-20 bg-sky-200/30 rounded-full blur-2xl opacity-80 animate-pulse">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>





    @push('scripts')
        <script>
            function logoMarquee() {
                return {
                    logos: [{
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/google/wordmark.svg',
                            alt: 'Google'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/medium/default.svg',
                            alt: 'Medium'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/meta/default.svg',
                            alt: 'Meta'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/microsoft/default.svg',
                            alt: 'Microsoft'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/stripe/default.svg',
                            alt: 'Stripe'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/amazon/default.svg',
                            alt: 'Amazon'
                        },
                        {
                            src: 'https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/discord/default.svg',
                            alt: 'Discord'
                        }
                    ],

                    paused: false,
                    offset: 0,
                    speed: 0.6,
                    animationFrame: null,
                    loopWidth: 0,

                    init() {
                        this.$nextTick(() => {
                            this.waitForImages().then(() => {
                                this.build();
                                this.animate();

                                let resizeTimer;
                                window.addEventListener('resize', () => {
                                    clearTimeout(resizeTimer);
                                    resizeTimer = setTimeout(() => {
                                        this.build();
                                    }, 120);
                                });
                            });
                        });
                    },

                    waitForImages() {
                        const images = Array.from(this.$refs.track.querySelectorAll('img'));

                        return Promise.all(
                            images.map(img => {
                                if (img.complete) return Promise.resolve();

                                return new Promise(resolve => {
                                    img.addEventListener('load', resolve, {
                                        once: true
                                    });
                                    img.addEventListener('error', resolve, {
                                        once: true
                                    });
                                });
                            })
                        );
                    },

                    build() {
                        const track = this.$refs.track;
                        const slider = this.$refs.slider;

                        cancelAnimationFrame(this.animationFrame);

                        // remove old clones
                        track.querySelectorAll('[data-clone="true"]').forEach(el => el.remove());

                        const originalItems = Array.from(track.children);
                        const gap = parseFloat(getComputedStyle(track).gap) || 0;

                        this.loopWidth = originalItems.reduce((sum, item) => sum + item.offsetWidth, 0) +
                            gap * (originalItems.length - 1);

                        // clone until enough width exists for seamless loop
                        while (track.scrollWidth < slider.offsetWidth + this.loopWidth * 2) {
                            originalItems.forEach(item => {
                                const clone = item.cloneNode(true);
                                clone.setAttribute('data-clone', 'true');
                                clone.setAttribute('aria-hidden', 'true');
                                track.appendChild(clone);
                            });
                        }

                        this.offset = 0;
                        track.style.transform = `translate3d(0,0,0)`;
                    },

                    animate() {
                        const step = () => {
                            if (!this.paused) {
                                this.offset += this.speed;

                                if (this.offset >= this.loopWidth) {
                                    this.offset = 0;
                                }

                                this.$refs.track.style.transform = `translate3d(-${this.offset}px, 0, 0)`;
                            }

                            this.animationFrame = requestAnimationFrame(step);
                        };

                        step();
                    }
                }
            }
        </script>
    @endpush
</div>
