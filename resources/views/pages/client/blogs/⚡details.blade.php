<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="relative text-white">
    <!-- Hero -->
    <section class="relative overflow-hidden py-10 sm:py-20 lg:pt-28">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[6%] top-8 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            {{-- <div class="absolute right-[8%] top-12 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div> --}}
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl">
                <div class="flex flex-wrap items-center gap-3 text-xs text-blue-100/55 sm:text-sm">
                    <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-cyan-200">
                        Cyber Security
                    </span>
                    <span>May 20, 2026</span>
                    <span>•</span>
                    <span>8 min read</span>
                </div>

                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                    Why proactive cybersecurity matters
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        more than reactive fixes
                    </span>
                </h1>

                <p class="mt-6 max-w-3xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                    Modern businesses need stronger protection, better user awareness, and a more proactive mindset to
                    reduce risk before problems become costly incidents.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="relative overflow-hidden pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_350px] xl:grid-cols-[1fr_390px]">
                <!-- Article -->
                <article class="space-y-8">
                    <!-- Featured Image -->
                    <div
                        class="overflow-hidden rounded-[32px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)]">
                        <div class="overflow-hidden rounded-[26px] border border-white/10">
                            <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1600&q=80"
                                alt="Blog details image"
                                class="h-[280px] w-full object-cover sm:h-[420px] lg:h-[520px]">
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="blog-details-card">
                        <p class="blog-paragraph">
                            Cybersecurity is no longer something businesses can treat as a secondary issue. In today’s
                            connected environment, even small organizations are exposed to phishing attempts, account
                            compromise, ransomware, weak passwords, unpatched systems, and user mistakes.
                        </p>

                        <p class="blog-paragraph">
                            Waiting until something goes wrong is usually more expensive than building a stronger
                            preventive structure from the beginning. A proactive approach reduces downtime, protects
                            data, and gives teams more confidence in their day-to-day operations.
                        </p>

                        <h2 class="blog-heading">Why reactive security creates bigger problems</h2>

                        <p class="blog-paragraph">
                            Many businesses only start taking cybersecurity seriously after an incident. At that stage,
                            they may already be dealing with system outages, reputational issues, loss of data, or
                            unexpected recovery costs. Reactive action often means pressure, confusion, and urgent
                            decisions under risk.
                        </p>

                        <div class="blog-highlight-box">
                            <p class="text-sm leading-7 text-blue-50/88 sm:text-base">
                                Strong cybersecurity is not just about technology. It is about preparation, visibility,
                                user behavior, recovery readiness, and continuous improvement.
                            </p>
                        </div>

                        <h2 class="blog-heading">What proactive cybersecurity looks like</h2>

                        <p class="blog-paragraph">
                            A proactive security model focuses on prevention, monitoring, and readiness. This includes
                            better endpoint protection, stronger access control, regular patching, staff awareness,
                            email security, backup strategy, and basic security reviews.
                        </p>

                        <ul class="blog-list">
                            <li>Keeping devices and systems updated</li>
                            <li>Using stronger password and access policies</li>
                            <li>Improving employee awareness of phishing and suspicious activity</li>
                            <li>Reviewing backup and recovery readiness</li>
                            <li>Monitoring for unusual behavior early</li>
                        </ul>

                        <h2 class="blog-heading">A smarter way forward</h2>

                        <p class="blog-paragraph">
                            The best approach is to treat cybersecurity as part of the business foundation. It should
                            support operations, protect trust, and reduce disruption. Even basic improvements can make a
                            meaningful difference when they are applied consistently.
                        </p>

                        <p class="blog-paragraph">
                            Businesses that plan early are usually better positioned to avoid avoidable issues, respond
                            more calmly, and continue operating with less risk.
                        </p>
                    </div>

                    <!-- Tags + Share -->
                    <div class="blog-details-card">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Share Article</h3>
                            <div class="mt-4 flex items-center gap-3">
                                <a href="#" class="share-btn">Fb</a>
                                <a href="#" class="share-btn">In</a>
                                <a href="#" class="share-btn">X</a>
                                <a href="#" class="share-btn">Wa</a>
                            </div>
                        </div>
                    </div>
                </article>

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
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Recent Posts</h3>

                        <div class="mt-6 space-y-4">
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

                            <a href="#" class="recent-post-item">
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=600&q=80"
                                    alt="Recent post" class="h-18 w-18 rounded-2xl object-cover">
                                <div>
                                    <h4 class="text-sm font-semibold leading-6 text-white">
                                        Why professional business email still matters
                                    </h4>
                                    <p class="mt-1 text-xs text-blue-100/50">May 12, 2026</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Keyword Tags -->
                    <div class="blog-sidebar-card">
                        <h3 class="text-2xl font-bold text-white">Keyword Tags</h3>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <a href="#" class="blog-tag">Cyber Security</a>
                            <a href="#" class="blog-tag">IT Support</a>
                            <a href="#" class="blog-tag">Web Design</a>
                            <a href="#" class="blog-tag">Cloud Email</a>
                            <a href="#" class="blog-tag">Business Growth</a>
                            <a href="#" class="blog-tag">Productivity</a>
                            <a href="#" class="blog-tag">Networking</a>
                            <a href="#" class="blog-tag">Security Audit</a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
