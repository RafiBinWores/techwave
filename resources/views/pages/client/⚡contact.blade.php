<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="relative text-white">
    <!-- Hero -->
    <section class="relative overflow-hidden py-20 sm:py-24 lg:py-28">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[6%] top-10 h-48 w-48 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[8%] top-8 h-60 w-60 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute left-1/2 bottom-0 h-72 w-72 -translate-x-1/2 rounded-full bg-sky-400/8 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8 text-center">
            <div
                class="mx-auto inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                Contact Us
            </div>

            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-7xl">
                Let’s talk about your
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                    next digital move
                </span>
            </h1>

            <p class="mx-auto mt-6 max-w-3xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                Whether you need IT support, cybersecurity, websites, cloud solutions, or a custom business system,
                our team is ready to help.
            </p>
        </div>
    </section>

    <!-- Contact Info Cards -->
    <section class="pb-12 sm:pb-16">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="contact-info-premium-card">
                    <div class="contact-info-premium-icon bg-cyan-500/15 text-cyan-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 4.5A2.25 2.25 0 014.5 2.25h2.1c.966 0 1.8.683 1.992 1.63l.383 1.915a1.125 1.125 0 01-.62 1.219l-1.412.706a11.036 11.036 0 005.318 5.318l.706-1.412a1.125 1.125 0 011.219-.62l1.915.383A2.025 2.025 0 0121.75 17.4v2.1a2.25 2.25 0 01-2.25 2.25h-.75C9.507 21.75 2.25 14.493 2.25 5.25V4.5z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Call Us</h3>
                    <p class="mt-2 text-sm text-blue-100/65">Talk directly with our team.</p>
                    <a href="tel:+8801000000000" class="mt-4 inline-block text-sm font-semibold text-cyan-200 hover:text-white">
                        +880 1XXX-XXXXXX
                    </a>
                </div>

                <div class="contact-info-premium-card">
                    <div class="contact-info-premium-icon bg-blue-500/15 text-blue-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 7.5v9a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0-8.69 5.794a1.5 1.5 0 01-1.662 0L2.25 7.5" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Email Us</h3>
                    <p class="mt-2 text-sm text-blue-100/65">Send us your requirement anytime.</p>
                    <a href="mailto:hello@yourcompany.com" class="mt-4 inline-block text-sm font-semibold text-cyan-200 hover:text-white">
                        hello@yourcompany.com
                    </a>
                </div>

                <div class="contact-info-premium-card">
                    <div class="contact-info-premium-icon bg-emerald-500/15 text-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487A9.958 9.958 0 0012 3.25c-5.385 0-9.75 4.253-9.75 9.5 0 1.676.445 3.25 1.224 4.618L2.25 21.75l4.567-1.194A9.819 9.819 0 0012 22.25c5.385 0 9.75-4.253 9.75-9.5 0-3.116-1.54-5.885-3.888-7.63z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">WhatsApp</h3>
                    <p class="mt-2 text-sm text-blue-100/65">Fast responses for quick inquiries.</p>
                    <a href="https://wa.me/8801000000000" class="mt-4 inline-block text-sm font-semibold text-cyan-200 hover:text-white">
                        Chat on WhatsApp
                    </a>
                </div>

                <div class="contact-info-premium-card">
                    <div class="contact-info-premium-icon bg-violet-500/15 text-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Working Hours</h3>
                    <p class="mt-2 text-sm text-blue-100/65">We are available during business hours.</p>
                    <p class="mt-4 text-sm font-semibold text-cyan-200">Sat - Thu : 9:00 AM - 8:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form + Map -->
    <section class="pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_0.95fr]">
                <!-- Contact Form -->
                <div
                    class="overflow-hidden rounded-[34px] border border-white/10 bg-white/6 p-6 sm:p-8 backdrop-blur-2xl shadow-[0_20px_70px_rgba(0,0,0,0.18)]">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">Send Message</p>
                        <h2 class="mt-4 text-3xl font-bold sm:text-4xl">Get in touch with us</h2>
                        <p class="mt-4 text-sm leading-7 text-blue-100/70 sm:text-base">
                            Tell us what you need and our team will get back to you with the right direction.
                        </p>
                    </div>

                    <form class="mt-8 space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Full Name</label>
                                <input type="text" placeholder="Enter your name" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Phone Number</label>
                                <input type="text" placeholder="Enter your phone number" class="contact-input">
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Email Address</label>
                                <input type="email" placeholder="Enter your email" class="contact-input">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-blue-50/85">Subject</label>
                                <input type="text" placeholder="Subject" class="contact-input">
                            </div>
                        </div>

                        {{-- <div>
                            <label class="mb-2 block text-sm font-medium text-blue-50/85">Service Needed</label>
                            <div class="relative">
                                <select class="contact-input contact-select appearance-none pr-12">
                                    <option value="" selected disabled>Choose a service</option>
                                    <option>Managed IT Support</option>
                                    <option>Cyber Security</option>
                                    <option>Website Development</option>
                                    <option>Cloud & Email Setup</option>
                                    <option>Office Infrastructure</option>
                                </select>

                                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-blue-100/60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>
                        </div> --}}

                        <div>
                            <label class="mb-2 block text-sm font-medium text-blue-50/85">Your Message</label>
                            <textarea rows="6" placeholder="Write your message here..."
                                class="contact-input resize-none"></textarea>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 cursor-pointer">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Map + Address -->
                <div class="space-y-6">
                    <div
                        class="overflow-hidden rounded-[34px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl shadow-[0_20px_70px_rgba(0,0,0,0.18)]">
                        <div class="overflow-hidden rounded-[28px] border border-white/10">
                            <iframe
                                src="https://www.google.com/maps?q=Dhaka%20Bangladesh&z=13&output=embed"
                                width="100%"
                                height="420"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                class="block w-full">
                            </iframe>
                        </div>
                    </div>

                    <div
                        class="rounded-[34px] border border-white/10 bg-white/6 p-6 sm:p-7 backdrop-blur-2xl shadow-[0_20px_70px_rgba(0,0,0,0.18)]">
                        <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">Office Location</p>
                        <h3 class="mt-4 text-2xl font-bold text-white">Visit our office</h3>
                        <p class="mt-4 text-sm leading-7 text-blue-100/68 sm:text-base">
                            House #00, Road #00, Area Name, Dhaka, Bangladesh
                        </p>

                        <div class="mt-6 flex flex-wrap gap-4">
                            <a href="https://maps.google.com/?q=Dhaka%20Bangladesh"
                                class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/12">
                                Open in Google Maps
                            </a>

                            <a href="https://wa.me/8801000000000"
                                class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-emerald-500 to-green-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                WhatsApp Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>