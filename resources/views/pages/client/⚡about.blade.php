<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('About Us | Techwave')] class extends Component
{
    //
};
?>

<div class="relative text-white">
    <!-- Hero -->
    <section class="relative overflow-hidden py-20 sm:py-24 lg:py-30">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[6%] top-10 h-52 w-52 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[8%] top-10 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute left-1/2 bottom-0 h-72 w-72 -translate-x-1/2 rounded-full bg-sky-400/8 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        About Our Company
                    </div>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-7xl">
                        We build smarter digital
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            foundations for modern business
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        We combine technology, execution, and business understanding to help companies run more
                        efficiently, stay protected, and grow with confidence.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="#who-we-are"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                            Learn More
                        </a>

                        <a href="#contact-us"
                            class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-6 py-3.5 font-semibold text-white backdrop-blur-xl transition hover:bg-white/12">
                            Contact Us
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[34px] border border-white/15 bg-white/8 p-4 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                        <div class="absolute left-8 top-8 h-24 w-24 rounded-full bg-cyan-400/12 blur-3xl"></div>
                        <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>

                        <div class="grid grid-cols-2 gap-4">
                            <img src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1000&q=80"
                                class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                            <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1000&q=80"
                                class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                            <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80"
                                class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                            <div
                                class="flex h-52 flex-col justify-center rounded-[24px] border border-white/10 bg-white/8 p-6 backdrop-blur-xl sm:h-64">
                                <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">Built Around</p>
                                <h3 class="mt-4 text-2xl font-bold text-white">Trust, Performance, and Long-Term Value</h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/65">
                                    We create systems that are practical, polished, and ready for growth.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="about-stat-card">
                    <p class="text-3xl font-bold text-white sm:text-4xl">10+</p>
                    <p class="mt-2 text-sm text-blue-100/60">Years of combined experience</p>
                </div>

                <div class="about-stat-card">
                    <p class="text-3xl font-bold text-white sm:text-4xl">250+</p>
                    <p class="mt-2 text-sm text-blue-100/60">Projects and engagements</p>
                </div>

                <div class="about-stat-card">
                    <p class="text-3xl font-bold text-white sm:text-4xl">98%</p>
                    <p class="mt-2 text-sm text-blue-100/60">Client satisfaction</p>
                </div>

                <div class="about-stat-card">
                    <p class="text-3xl font-bold text-white sm:text-4xl">24/7</p>
                    <p class="mt-2 text-sm text-blue-100/60">Support mindset</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Who We Are -->
    <section id="who-we-are" class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="about-panel">
                <div class="grid gap-10 lg:grid-cols-[1fr_420px] items-center">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs text-blue-100/80 backdrop-blur-xl">
                            <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                            Who We Are
                        </div>

                        <h2 class="mt-6 text-3xl font-bold sm:text-4xl lg:text-5xl">
                            A technology partner focused on practical outcomes
                        </h2>

                        <p class="mt-5 about-text">
                            We are a modern IT and digital solutions company helping businesses strengthen operations,
                            reduce technical friction, and create more reliable digital systems.
                        </p>

                        <p class="mt-4 about-text">
                            Our work combines managed support, cybersecurity, cloud systems, infrastructure, web
                            development, and business-focused technology planning into one practical service ecosystem.
                        </p>

                        <p class="mt-4 about-text">
                            We do not believe in unnecessary complexity. We believe in clean execution, clear
                            communication, and solutions that truly support growth.
                        </p>
                    </div>

                    <div class="rounded-[30px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl">
                        <img src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80"
                            class="h-[340px] w-full rounded-[24px] object-cover" alt="Who we are">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="about-mv-card">
                    <div class="about-mv-icon bg-cyan-500/15 text-cyan-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12 11.204 3.046a1.5 1.5 0 0 1 2.121 0L22.279 12M4.5 9.75v10.5A2.25 2.25 0 0 0 6.75 22.5h10.5a2.25 2.25 0 0 0 2.25-2.25V9.75" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-2xl font-bold text-white">Our Mission</h3>
                    <p class="mt-4 text-sm leading-7 text-blue-100/70 sm:text-base">
                        To help businesses use technology with more clarity, confidence, and efficiency through secure,
                        dependable, and thoughtfully designed digital solutions.
                    </p>
                </div>

                <div class="about-mv-card">
                    <div class="about-mv-icon bg-blue-500/15 text-blue-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75m6 2.25A8.25 8.25 0 1 1 4.5 12a8.25 8.25 0 0 1 16.5 0Z" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-2xl font-bold text-white">Our Vision</h3>
                    <p class="mt-4 text-sm leading-7 text-blue-100/70 sm:text-base">
                        To become a trusted long-term technology partner for growing companies by delivering modern,
                        scalable, and business-focused systems that truly create value.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Why businesses
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        choose us
                    </span>
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="why-upgrade-card">
                    <h3 class="why-upgrade-title">Business First</h3>
                    <p class="why-upgrade-text">We build around outcomes, not only technical tasks.</p>
                </div>

                <div class="why-upgrade-card why-upgrade-card-featured">
                    <h3 class="why-upgrade-title">Reliable Execution</h3>
                    <p class="why-upgrade-text">We focus on consistency, communication, and follow-through.</p>
                </div>

                <div class="why-upgrade-card">
                    <h3 class="why-upgrade-title">Security Mindset</h3>
                    <p class="why-upgrade-text">Protection and resilience are part of everything we design.</p>
                </div>

                <div class="why-upgrade-card">
                    <h3 class="why-upgrade-title">Scalable Systems</h3>
                    <p class="why-upgrade-text">Our solutions are ready for growth, not just today’s needs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Expertise -->
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Our areas of
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        expertise
                    </span>
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="expertise-card">
                    <h3 class="expertise-title">Managed IT Support</h3>
                    <p class="expertise-text">Reliable daily business technology support and maintenance.</p>
                </div>

                <div class="expertise-card">
                    <h3 class="expertise-title">Cyber Security</h3>
                    <p class="expertise-text">Protect systems, users, data, and reduce operational risk.</p>
                </div>

                <div class="expertise-card">
                    <h3 class="expertise-title">Website Development</h3>
                    <p class="expertise-text">Modern responsive websites and business systems.</p>
                </div>

                <div class="expertise-card">
                    <h3 class="expertise-title">Cloud Solutions</h3>
                    <p class="expertise-text">Email, collaboration, hosting, and scalable infrastructure.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Our journey of
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        steady growth
                    </span>
                </h2>
            </div>

            <div class="relative">
                <div class="absolute left-5 top-0 h-full w-px bg-gradient-to-b from-cyan-300/0 via-cyan-300/25 to-cyan-300/0 sm:left-1/2 sm:-translate-x-1/2"></div>

                <div class="space-y-6">
                    <div class="timeline-card sm:mr-auto sm:max-w-[48%]">
                        <div class="timeline-dot"></div>
                        <p class="timeline-year">2020</p>
                        <h3 class="timeline-title">The foundation</h3>
                        <p class="timeline-text">Started with a vision to provide smarter and more practical business IT support.</p>
                    </div>

                    <div class="timeline-card sm:ml-auto sm:max-w-[48%]">
                        <div class="timeline-dot"></div>
                        <p class="timeline-year">2022</p>
                        <h3 class="timeline-title">Expanded capabilities</h3>
                        <p class="timeline-text">Added web development, cloud systems, and stronger cybersecurity-focused services.</p>
                    </div>

                    <div class="timeline-card sm:mr-auto sm:max-w-[48%]">
                        <div class="timeline-dot"></div>
                        <p class="timeline-year">2024</p>
                        <h3 class="timeline-title">Broader business reach</h3>
                        <p class="timeline-text">Worked with more diverse clients and built more complete digital service packages.</p>
                    </div>

                    <div class="timeline-card sm:ml-auto sm:max-w-[48%]">
                        <div class="timeline-dot"></div>
                        <p class="timeline-year">Today</p>
                        <h3 class="timeline-title">Growth with purpose</h3>
                        <p class="timeline-text">Focused on long-term client partnerships, modern tools, and premium service quality.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Message -->
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="about-panel">
                <div class="grid gap-10 lg:grid-cols-[300px_1fr] items-center">
                    <div class="rounded-[30px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=1000&q=80"
                            class="h-[340px] w-full rounded-[24px] object-cover" alt="CEO">
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">Leadership Message</p>
                        <h2 class="mt-4 text-3xl font-bold sm:text-4xl">A note from our leadership</h2>

                        <p class="mt-5 about-text">
                            We believe technology should make business clearer, safer, and more efficient — not more complicated.
                        </p>

                        <p class="mt-4 about-text">
                            Our goal is to become the kind of partner clients can trust for both daily support and
                            long-term growth decisions.
                        </p>

                        <p class="mt-4 about-text">
                            That means staying practical, communicating clearly, and delivering work that holds up in
                            real-world business conditions.
                        </p>

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-white">Ahsan Rahman</h3>
                            <p class="mt-1 text-sm text-blue-100/55">Founder / Technology Lead</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Culture / Gallery -->
    {{-- <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Culture, collaboration, and
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        execution
                    </span>
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1000&q=80"
                    class="about-gallery-img" alt="">
                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1000&q=80"
                    class="about-gallery-img" alt="">
                <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80"
                    class="about-gallery-img" alt="">
                <img src="https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1000&q=80"
                    class="about-gallery-img" alt="">
            </div>
        </div>
    </section> --}}

    <!-- Experts -->
    {{-- <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    Meet our
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        experts
                    </span>
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="team-card">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80"
                        class="team-img" alt="">
                    <h3 class="team-name">Ahsan Rahman</h3>
                    <p class="team-role">IT Infrastructure Lead</p>
                </div>

                <div class="team-card">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=800&q=80"
                        class="team-img" alt="">
                    <h3 class="team-name">Sarah Khan</h3>
                    <p class="team-role">Cyber Security Specialist</p>
                </div>

                <div class="team-card">
                    <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=800&q=80"
                        class="team-img" alt="">
                    <h3 class="team-name">Nafis Ahmed</h3>
                    <p class="team-role">Web Solutions Expert</p>
                </div>

                <div class="team-card">
                    <img src="https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=800&q=80"
                        class="team-img" alt="">
                    <h3 class="team-name">Rima Sultana</h3>
                    <p class="team-role">Cloud Systems Consultant</p>
                </div>
            </div>
        </div>
    </section> --}}
</div>