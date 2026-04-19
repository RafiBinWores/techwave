<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="relative text-white">
    <!-- Hero -->
    <section class="relative overflow-hidden py-18 sm:py-22 lg:py-26">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[8%] top-10 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[10%] top-16 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-14">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Service Details
                    </div>

                    <h1
                        class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                        Managed IT Support
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            for modern business operations
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        Keep your business running smoothly with reliable day-to-day IT support, device setup,
                        troubleshooting, office networking, system maintenance, and proactive technical guidance.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <span
                            class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">Business
                            IT</span>
                        <span
                            class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">Office
                            Support</span>
                        <span
                            class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">System
                            Setup</span>
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[30px] border border-white/15 bg-white/8 p-3 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                        <div class="absolute left-8 top-8 h-28 w-28 rounded-full bg-cyan-400/12 blur-3xl"></div>
                        <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>

                        <div class="overflow-hidden rounded-[24px] border border-white/10">
                            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80"
                                alt="Managed IT support" class="h-[320px] w-full object-cover sm:h-[400px]">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="relative overflow-hidden pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_380px] xl:grid-cols-[1fr_420px]">
                <!-- Left Content -->
                <div class="space-y-8">
                    <!-- Overview -->
                    <div class="service-detail-card">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Service Overview</h2>
                        <p class="mt-5 text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                            Our Managed IT Support service is designed for companies that need dependable technical
                            assistance without the complexity of handling every issue internally. We help businesses
                            maintain stable operations through system setup, user support, office networking,
                            troubleshooting, maintenance, and ongoing technical guidance.
                        </p>
                        <p class="mt-4 text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                            Whether you need support for workstations, office devices, connectivity, software
                            installations, or daily issue resolution, our team helps create a smoother and more
                            productive working environment.
                        </p>
                    </div>

                    <!-- Benefits -->
                    <div class="service-detail-card">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Key Benefits</h2>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Reduced Downtime</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    Resolve technical issues faster and keep your team productive.
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Reliable Setup</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    Ensure systems, devices, and office tools are configured correctly.
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Scalable Support</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    Support that grows with your business needs and team structure.
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Operational Clarity</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    Cleaner systems, better visibility, and smoother daily workflows.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="service-detail-card">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">What’s Included</h2>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="feature-line">Office networking support</div>
                            <div class="feature-line">Windows installation and setup</div>
                            <div class="feature-line">Email client configuration</div>
                            <div class="feature-line">Printer and device troubleshooting</div>
                            <div class="feature-line">Attendance device assistance</div>
                            <div class="feature-line">Virus and malware scanning</div>
                            <div class="feature-line">Basic endpoint security assistance</div>
                            <div class="feature-line">General business IT troubleshooting</div>
                        </div>
                    </div>

                    <!-- Ideal For -->
                    <div class="service-detail-card">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Who This Service Is For</h2>
                        <div class="mt-5 space-y-4 text-sm leading-7 text-blue-100/72 sm:text-base">
                            <p>Small and medium businesses that need dependable everyday IT support.</p>
                            <p>Companies that want a smoother office environment without hiring a full in-house team.
                            </p>
                            <p>Growing organizations that need technical help with devices, networking, systems, and
                                users.</p>
                        </div>
                    </div>

                    <!-- Other Services -->
                    <div class="service-detail-card">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-white sm:text-3xl">Other Services</h2>
                                <p class="mt-2 text-sm text-blue-100/66">Explore other solutions we offer.</p>
                            </div>
                            <a href="{{ route('client.services') }}" wire:navigate
                                class="hidden sm:inline-flex items-center rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                View All
                            </a>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <a href="#" class="other-service-card">
                                <div class="other-service-icon bg-cyan-500/15 text-cyan-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">Cyber Security</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/64">Protection, hardening, and
                                    security-focused support.</p>
                            </a>

                            <a href="#" class="other-service-card">
                                <div class="other-service-icon bg-sky-500/15 text-sky-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 4.5h16.5v10.5H3.75zM7.5 20.25h9" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">Website Development</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/64">Modern websites and business systems.
                                </p>
                            </a>

                            <a href="#" class="other-service-card sm:col-span-2 xl:col-span-1">
                                <div class="other-service-icon bg-violet-500/15 text-violet-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 7.5l9-4.5 9 4.5m-18 0 9 4.5m-9-4.5V16.5l9 4.5m9-13.5v9l-9 4.5m0-9V21" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-white">Cloud & Email Setup</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/64">Scalable cloud tools and business
                                    communication.</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <aside class="space-y-6">
                    <!-- Contact Card -->
                    <div class="sidebar-service-card">
                        <h3 class="text-2xl font-bold text-white">Need Help Fast?</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Contact our team directly for quick guidance about this service.
                        </p>

                        <div class="mt-6 space-y-4">
                            <div class="contact-side-box">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Call Us</p>
                                <p class="mt-2 text-base font-semibold text-white">+880 96381-01601</p>
                            </div>

                            <div class="contact-side-box">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">WhatsApp</p>
                                <p class="mt-2 text-base font-semibold text-white">+880 96381-01601</p>
                            </div>

                            <a href="https://wa.me/8801000000000"
                                class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-emerald-500 to-green-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                Chat on WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Quote Form -->
                    <div class="sidebar-service-card">
                        <h3 class="text-2xl font-bold text-white">Request a Quote</h3>
                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Share your requirements and we’ll get back to you with the right solution.
                        </p>

                        <form class="mt-6 space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Full Name</label>
                                <input type="text" placeholder="Enter your name" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Phone Number</label>
                                <input type="text" placeholder="Enter your phone" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Email Address</label>
                                <input type="email" placeholder="Enter your email" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Company Name</label>
                                <input type="text" placeholder="Enter your company name" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Project Details</label>
                                <textarea rows="5" placeholder="Tell us what you need" class="contact-input resize-none"></textarea>
                            </div>

                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                                Send Quote Request
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
