<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services | Techwave')] class extends Component {
    //
};
?>

<div>
    <div class="relative text-white">

        <!-- Main Services -->
        <section id="service-list" class="relative overflow-hidden py-20 sm:py-24">
            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center lg:mb-18">
                    <div
                        class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Core Service Areas
                    </div>

                    <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                        Tailored services for
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            growth, security, and stability
                        </span>
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                        From foundational IT support to advanced enterprise protection, we design solutions that fit
                        your
                        business stage and operational needs.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article class="service-page-card">
                        <div class="service-page-icon bg-cyan-500/15 text-cyan-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Cyber Security</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Secure your business with proactive protection, endpoint defense, vulnerability reviews, and
                            modern security architecture.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">Firewall & endpoint security</li>
                            <li class="service-bullet">Threat monitoring & hardening</li>
                            <li class="service-bullet">Security audits & best practices</li>
                        </ul>
                    </article>

                    <article class="service-page-card">
                        <div class="service-page-icon bg-blue-500/15 text-blue-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Managed IT Support</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Keep your business running smoothly with responsive support, troubleshooting, system setup,
                            and
                            day-to-day IT assistance.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">Office networking support</li>
                            <li class="service-bullet">Windows & device setup</li>
                            <li class="service-bullet">Monitoring and issue resolution</li>
                        </ul>
                    </article>

                    <article class="service-page-card">
                        <div class="service-page-icon bg-sky-500/15 text-sky-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.5h16.5v10.5H3.75zM7.5 20.25h9" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Website & Web Apps</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            We design and build modern websites and business systems that are fast, secure, and crafted
                            to
                            support real growth.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">Company websites</li>
                            <li class="service-bullet">Custom business systems</li>
                            <li class="service-bullet">Responsive premium UI/UX</li>
                        </ul>
                    </article>

                    <article class="service-page-card">
                        <div class="service-page-icon bg-violet-500/15 text-violet-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Cloud & Email Systems</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Build scalable communication and collaboration systems with professional cloud email and
                            secure
                            hosted infrastructure.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">Google Workspace / Microsoft 365</li>
                            <li class="service-bullet">Business email setup</li>
                            <li class="service-bullet">Hosting and cloud services</li>
                        </ul>
                    </article>

                    <article class="service-page-card">
                        <div class="service-page-icon bg-emerald-500/15 text-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Office Infrastructure</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Create dependable physical and network foundations with office systems, CCTV, attendance
                            devices, and structured connectivity.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">CCTV camera installation</li>
                            <li class="service-bullet">Attendance device setup</li>
                            <li class="service-bullet">Network and print solutions</li>
                        </ul>
                    </article>

                    <article class="service-page-card">
                        <div class="service-page-icon bg-pink-500/15 text-pink-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-2xl font-bold text-white">Growth & Digital Presence</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Expand your digital reach through branding, social media setup, SEO, and business-facing
                            design
                            systems.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                            <li class="service-bullet">Graphics and visual content</li>
                            <li class="service-bullet">SEO and online visibility</li>
                            <li class="service-bullet">Social media business setup</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <!-- Process -->
        <section class="relative overflow-hidden py-20 sm:py-24">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute left-[8%] top-10 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute right-[10%] bottom-8 h-52 w-52 rounded-full bg-blue-500/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center lg:mb-18">
                    <div
                        class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        How We Work
                    </div>

                    <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                        A refined process that turns
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            complexity into clarity
                        </span>
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                        We combine business understanding, technical precision, and structured execution to deliver
                        solutions
                        that feel smooth from planning to long-term support.
                    </p>
                </div>

                <div class="relative">
                    <div
                        class="absolute left-1/2 top-0 hidden h-full w-px -translate-x-1/2 bg-gradient-to-b from-cyan-300/0 via-cyan-300/30 to-cyan-300/0 lg:block">
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="lg:pr-8">
                            <div class="process-premium-card lg:mr-8">
                                <div class="process-premium-top">
                                    <div class="process-premium-step">01</div>
                                    <div class="process-premium-icon bg-cyan-500/15 text-cyan-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 12h9m-9 4.5h5.25M6 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75z" />
                                        </svg>
                                    </div>
                                </div>

                                <h3 class="mt-6 text-2xl font-bold text-white">Assess & Understand</h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    We review your current setup, risks, pain points, business priorities, and growth
                                    objectives to understand the full picture before making decisions.
                                </p>
                            </div>
                        </div>

                        <div class="lg:pt-16 lg:pl-8">
                            <div class="process-premium-card lg:ml-8">
                                <div class="process-premium-top">
                                    <div class="process-premium-step">02</div>
                                    <div class="process-premium-icon bg-blue-500/15 text-blue-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 6.75h16.5M3.75 12h10.5M3.75 17.25h6.75" />
                                        </svg>
                                    </div>
                                </div>

                                <h3 class="mt-6 text-2xl font-bold text-white">Plan & Architect</h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    We design the right structure, choose the best-fit technologies, and define the
                                    implementation flow for stability, usability, and scale.
                                </p>
                            </div>
                        </div>

                        <div class="lg:-mt-2 lg:pr-8">
                            <div class="process-premium-card lg:mr-8">
                                <div class="process-premium-top">
                                    <div class="process-premium-step">03</div>
                                    <div class="process-premium-icon bg-sky-500/15 text-sky-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                                        </svg>
                                    </div>
                                </div>

                                <h3 class="mt-6 text-2xl font-bold text-white">Implement & Optimize</h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    Our team builds, configures, secures, and tests the solution carefully so it
                                    performs well
                                    in real business conditions.
                                </p>
                            </div>
                        </div>

                        <div class="lg:pt-14 lg:pl-8">
                            <div class="process-premium-card lg:ml-8">
                                <div class="process-premium-top">
                                    <div class="process-premium-step">04</div>
                                    <div class="process-premium-icon bg-violet-500/15 text-violet-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M18 8.25V6.75A2.25 2.25 0 0015.75 4.5h-7.5A2.25 2.25 0 006 6.75v10.5A2.25 2.25 0 008.25 19.5h7.5A2.25 2.25 0 0018 17.25v-1.5" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 12H9m0 0 2.25-2.25M9 12l2.25 2.25" />
                                        </svg>
                                    </div>
                                </div>

                                <h3 class="mt-6 text-2xl font-bold text-white">Support & Evolve</h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    After launch, we stay involved with monitoring, improvements, maintenance, and
                                    strategic
                                    guidance so your systems stay reliable as you grow.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="relative overflow-hidden py-20 sm:py-24">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute left-[5%] bottom-10 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute right-[8%] top-8 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center">
                    <div
                        class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Why Choose Us
                    </div>

                    <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                        More than service delivery —
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            we build dependable partnerships
                        </span>
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                        We bring together business thinking, execution quality, and technical depth so your company gets
                        solutions that are practical, secure, and built to last.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div class="why-premium-card">
                        <div class="why-premium-icon bg-cyan-500/15 text-cyan-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-xl font-bold text-white">Business-Driven Strategy</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            We shape every solution around business performance, operational clarity, and future growth.
                        </p>
                    </div>

                    <div class="why-premium-card why-premium-card-featured">
                        <div class="why-premium-icon bg-blue-500/15 text-blue-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-xl font-bold text-white">Reliable Delivery</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Clear communication, disciplined execution, and dependable support from first discussion to
                            final rollout.
                        </p>
                    </div>

                    <div class="why-premium-card">
                        <div class="why-premium-icon bg-sky-500/15 text-sky-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-xl font-bold text-white">Security by Design</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Security is built into planning, access control, systems architecture, and support
                            workflows.
                        </p>
                    </div>

                    <div class="why-premium-card">
                        <div class="why-premium-icon bg-violet-500/15 text-violet-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-xl font-bold text-white">Scalable Thinking</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Our work is designed to support where your business is today and where it needs to go next.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact CTA Form -->
        <section class="relative overflow-hidden py-20 sm:py-24">

            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden rounded-[36px] border border-white/15 bg-white/8 shadow-[0_30px_100px_rgba(0,0,0,0.28)] backdrop-blur-2xl">
                    <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr]">
                        <div class="px-6 py-10 sm:px-8 sm:py-12 lg:px-12 lg:py-14">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                                <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                                Contact Us
                            </div>

                            <h2 class="mt-6 text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                                Let’s talk about your
                                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                    next IT solution
                                </span>
                            </h2>

                            <p class="mt-5 max-w-xl text-sm leading-7 text-blue-100/70 sm:text-base">
                                Tell us what you need — whether it is support, infrastructure, cybersecurity, web
                                development,
                                or a full business IT setup — and our team will get back to you.
                            </p>

                            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                <div class="contact-info-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Email</p>
                                    <a href="mailto:info@techwave.com" class="mt-2 text-sm font-semibold text-white">info@techwave.com</a>
                                </div>

                                <div class="contact-info-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Phone</p>
                                    <a href="tel:+8809638101601" class="mt-2 text-sm font-semibold text-white">+8809638-101601</a>
                                </div>

                                <div class="contact-info-card sm:col-span-2">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Support Hours</p>
                                    <p class="mt-2 text-sm font-semibold text-white">Business hours support with
                                        priority response options</p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="border-t border-white/10 bg-slate-950/20 px-6 py-10 sm:px-8 sm:py-12 lg:border-l lg:border-t-0 lg:px-10 lg:py-14">
                            <form class="space-y-5">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-blue-50/85">Full Name</label>
                                        <input type="text" placeholder="Enter your name" class="contact-input">
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-blue-50/85">Phone</label>
                                        <input type="text" placeholder="Enter your phone" class="contact-input">
                                    </div>
                                </div>

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-blue-50/85">Email
                                            Address</label>
                                        <input type="email" placeholder="Enter your email" class="contact-input">
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-blue-50/85">
                                            Service Needed
                                        </label>

                                        <div class="relative">
                                            <select class="contact-input contact-select appearance-none pr-12">
                                                <option value="" selected disabled>Choose a service</option>
                                                <option>Managed IT Support</option>
                                                <option>Cyber Security</option>
                                                <option>Website Development</option>
                                                <option>Cloud & Email Setup</option>
                                                <option>Office Infrastructure</option>
                                            </select>

                                            <!-- Custom Arrow -->
                                            <div
                                                class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-blue-100/60">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Name</label>
                                    <input type="text" placeholder="Enter your company name"
                                        class="contact-input">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-blue-50/85">Your Message</label>
                                    <textarea rows="5" placeholder="Tell us about your requirements" class="contact-input resize-none"></textarea>
                                </div>

                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                                    Send Inquiry
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
