<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="relative text-white">
    <!-- Hero -->
    <section class="relative overflow-hidden py-18 sm:py-24 lg:py-28">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[6%] top-8 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[8%] top-12 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Blog & Insights
                    </div>

                    <h1
                        class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                        Insights, guides, and
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            practical ideas for growth
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        Explore expert articles on IT support, cybersecurity, business systems, websites, cloud tools,
                        productivity, and digital transformation.
                    </p>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[30px] border border-white/15 bg-white/8 p-3 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                        <div class="absolute left-8 top-8 h-24 w-24 rounded-full bg-cyan-400/12 blur-3xl"></div>
                        <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>

                        <div class="overflow-hidden rounded-[24px] border border-white/10">
                            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80"
                                alt="Blog hero" class="h-[320px] w-full object-cover sm:h-[400px]">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Post -->
    <section class="relative overflow-hidden pb-12 sm:pb-16">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <article
                class="group relative overflow-hidden rounded-[34px] border border-white/10 bg-white/6 p-4 sm:p-5 backdrop-blur-2xl shadow-[0_22px_70px_rgba(0,0,0,0.2)]">
                <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div class="overflow-hidden rounded-[26px] border border-white/10">
                        <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1400&q=80"
                            alt="Featured blog"
                            class="h-[280px] w-full object-cover transition duration-700 group-hover:scale-105 sm:h-[360px]">
                    </div>

                    <div class="p-2 sm:p-4">
                        <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                            <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-cyan-200">
                                Featured Post
                            </span>
                            <span>Cyber Security</span>
                            <span>•</span>
                            <span>May 20, 2026</span>
                        </div>

                        <h2 class="mt-5 text-3xl font-bold leading-tight text-white sm:text-4xl">
                            Why proactive cybersecurity matters more than reactive fixes
                        </h2>

                        <p class="mt-5 text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                            Learn why modern businesses need a security-first mindset and how better protection,
                            monitoring, and user awareness can reduce long-term risk.
                        </p>

                        <a href="{{ route('client.blogs.details', ['slug' => 'cyber-security']) }}"
                            class="mt-7 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                            Read Full Article
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.25 6.75L6.75 17.25M8.25 6.75h9v9" />
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <!-- Blog Content -->
    <section class="relative overflow-hidden pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_350px] xl:grid-cols-[1fr_390px]">
                <!-- Main Grid -->
                <div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1496171367470-9ed9a91ea931?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Web Development</span>
                                    <span>May 18, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    How a better website improves trust and conversion
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    Understand how performance, design clarity, and user experience influence customer
                                    trust.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>

                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Business IT</span>
                                    <span>May 16, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    Essential IT systems every growing office should have
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    From networking to cloud tools, here are the systems that improve efficiency and
                                    stability.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>

                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Cloud & Email</span>
                                    <span>May 12, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    Why professional business email still matters
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    Branded email, stronger trust, and better communication control for serious
                                    businesses.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>

                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Productivity</span>
                                    <span>May 10, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    Small workflow changes that create big productivity gains
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    Practical improvements that help teams work faster and with fewer interruptions.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>

                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Security</span>
                                    <span>May 08, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    What makes a company vulnerable to avoidable cyber risk
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    A look at common mistakes businesses make and how to reduce risk more effectively.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>

                        <article class="blog-card">
                            <div class="overflow-hidden rounded-[24px] border border-white/10">
                                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80"
                                    alt="Blog post"
                                    class="h-56 w-full object-cover transition duration-700 hover:scale-105">
                            </div>
                            <div class="pt-5">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55">
                                    <span class="blog-chip">Business Growth</span>
                                    <span>May 06, 2026</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-bold text-white">
                                    Why digital systems are now essential for scaling operations
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-blue-100/68">
                                    Growth needs better systems, stronger structure, and more reliable digital support.
                                </p>
                                <a href="#" class="blog-link mt-5">Read More</a>
                            </div>
                        </article>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-10 flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                        <a href="#" class="pagination-btn">
                            Prev
                        </a>

                        <a href="#" class="pagination-btn pagination-btn-active">
                            1
                        </a>

                        <a href="#" class="pagination-btn">
                            2
                        </a>

                        <a href="#" class="pagination-btn">
                            3
                        </a>

                        <span class="px-2 text-blue-100/45">...</span>

                        <a href="#" class="pagination-btn">
                            8
                        </a>

                        <a href="#" class="pagination-btn">
                            Next
                        </a>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Search -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Search Blog</h3>

                        <form class="mt-6">
                            <div class="relative">
                                <input type="text" placeholder="Search articles..."
                                    class="blog-search-input pr-12" />

                                <button type="submit"
                                    class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-white/8 text-blue-100/70 transition hover:bg-white/12 hover:text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Categories -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Categories</h3>

                        <div class="mt-6 space-y-3">
                            <a href="#" class="blog-category-item">
                                <span>Cyber Security</span>
                                <span>12</span>
                            </a>
                            <a href="#" class="blog-category-item">
                                <span>Web Development</span>
                                <span>8</span>
                            </a>
                            <a href="#" class="blog-category-item">
                                <span>Business IT</span>
                                <span>10</span>
                            </a>
                            <a href="#" class="blog-category-item">
                                <span>Cloud & Email</span>
                                <span>6</span>
                            </a>
                            <a href="#" class="blog-category-item">
                                <span>Productivity</span>
                                <span>7</span>
                            </a>
                            <a href="#" class="blog-category-item">
                                <span>Business Growth</span>
                                <span>5</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Recent Posts</h3>

                        <div class="mt-6 space-y-4">
                            <a href="#" class="recent-post-item">
                                <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=600&q=80"
                                    alt="Recent post" class="h-18 w-18 rounded-2xl object-cover">
                                <div>
                                    <h4 class="text-sm font-semibold leading-6 text-white">
                                        Why proactive cybersecurity matters more than reactive fixes
                                    </h4>
                                    <p class="mt-1 text-xs text-blue-100/50">May 20, 2026</p>
                                </div>
                            </a>

                            <a href="#" class="recent-post-item">
                                <img src="https://images.unsplash.com/photo-1496171367470-9ed9a91ea931?auto=format&fit=crop&w=600&q=80"
                                    alt="Recent post" class="h-18 w-18 rounded-2xl object-cover">
                                <div>
                                    <h4 class="text-sm font-semibold leading-6 text-white">
                                        How a better website improves trust and conversion
                                    </h4>
                                    <p class="mt-1 text-xs text-blue-100/50">May 18, 2026</p>
                                </div>
                            </a>

                            <a href="#" class="recent-post-item">
                                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80"
                                    alt="Recent post" class="h-18 w-18 rounded-2xl object-cover">
                                <div>
                                    <h4 class="text-sm font-semibold leading-6 text-white">
                                        Essential IT systems every growing office should have
                                    </h4>
                                    <p class="mt-1 text-xs text-blue-100/50">May 16, 2026</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Need help with your business IT?</h3>
                        <p class="mt-4 text-sm leading-7 text-blue-100/68">
                            Talk to our team about support, security, websites, cloud systems, and custom solutions.
                        </p>

                        <a href="#contact"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                            Contact Us
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
