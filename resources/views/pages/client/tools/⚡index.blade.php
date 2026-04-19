<?php

use Livewire\Component;

new class extends Component
{
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
                        Smart Tools
                    </div>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                        Powerful AI & business tools
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            built for speed
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        Explore a growing collection of AI-powered and productivity tools designed to help users write
                        better, work faster, build smarter, and manage everyday tasks with ease.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="#featured-tools"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                            Explore Tools
                        </a>

                        <a href="#all-tools"
                            class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-6 py-3.5 font-semibold text-white backdrop-blur-xl transition hover:bg-white/12">
                            View Categories
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[30px] border border-white/15 bg-white/8 p-3 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                        <div class="absolute left-8 top-8 h-24 w-24 rounded-full bg-cyan-400/12 blur-3xl"></div>
                        <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>

                        <div class="rounded-[24px] border border-white/10 bg-slate-950/30 p-5 sm:p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="tool-mini-card">
                                    <div class="tool-mini-icon bg-cyan-500/15 text-cyan-200">AI</div>
                                    <h3 class="mt-4 text-base font-semibold text-white">AI Writer</h3>
                                    <p class="mt-2 text-sm leading-6 text-blue-100/62">Generate fast content ideas and text.</p>
                                </div>

                                <div class="tool-mini-card">
                                    <div class="tool-mini-icon bg-blue-500/15 text-blue-200">CV</div>
                                    <h3 class="mt-4 text-base font-semibold text-white">CV Builder</h3>
                                    <p class="mt-2 text-sm leading-6 text-blue-100/62">Build clean and professional resumes.</p>
                                </div>

                                <div class="tool-mini-card">
                                    <div class="tool-mini-icon bg-sky-500/15 text-sky-200">SEO</div>
                                    <h3 class="mt-4 text-base font-semibold text-white">SEO Tool</h3>
                                    <p class="mt-2 text-sm leading-6 text-blue-100/62">Improve visibility with smart suggestions.</p>
                                </div>

                                <div class="tool-mini-card">
                                    <div class="tool-mini-icon bg-violet-500/15 text-violet-200">IMG</div>
                                    <h3 class="mt-4 text-base font-semibold text-white">Image Tools</h3>
                                    <p class="mt-2 text-sm leading-6 text-blue-100/62">Compress, enhance, and optimize media.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Tools -->
    {{-- <section id="featured-tools" class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center lg:mb-18">
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Featured Tools
                </div>

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Explore our most useful
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        AI & productivity tools
                    </span>
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    Hand-picked tools designed for professionals, businesses, creators, and job seekers.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-6 auto-rows-[250px]">
                <!-- AI Writer -->
                <article
                    class="group relative overflow-hidden rounded-[30px] border border-white/10 bg-white/6 p-5 backdrop-blur-2xl shadow-[0_18px_60px_rgba(0,0,0,0.18)] md:col-span-4 md:row-span-2">
                    <div class="absolute -left-10 top-0 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
                    <div class="absolute right-0 bottom-0 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>

                    <div class="relative flex h-full flex-col justify-between rounded-[24px] border border-white/8 bg-slate-950/20 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span
                                class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.18em] text-cyan-200">
                                AI Tool
                            </span>

                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-cyan-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.5 8.25h9m-9 3h6m-9 8.25h12A2.25 2.25 0 0018.75 17.25V6.75A2.25 2.25 0 0016.5 4.5h-9A2.25 2.25 0 005.25 6.75v10.5A2.25 2.25 0 007.5 19.5z" />
                                </svg>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            <h3 class="text-2xl font-bold text-white sm:text-3xl">AI Content Writer</h3>
                            <p class="mt-3 text-sm leading-7 text-blue-100/72 sm:text-base">
                                Generate blog content, captions, email drafts, product descriptions, and professional
                                written copy with speed and consistency.
                            </p>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="tool-tag">Blog Writing</span>
                                <span class="tool-tag">Email Drafts</span>
                                <span class="tool-tag">Marketing Copy</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <a href="#"
                                class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:-translate-y-0.5">
                                Open Tool
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.25 6.75L6.75 17.25M8.25 6.75h9v9" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>

                <!-- CV Builder -->
                <article class="tool-feature-card md:col-span-2">
                    <span class="tool-pill">Career Tool</span>
                    <h3 class="mt-4 text-2xl font-bold text-white">CV Builder</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Create elegant, ATS-friendly resumes with structured sections and professional formatting.
                    </p>
                    <a href="#" class="tool-link mt-6">Build CV</a>
                </article>

                <!-- AI Image -->
                <article class="tool-feature-card md:col-span-2">
                    <span class="tool-pill">Creative Tool</span>
                    <h3 class="mt-4 text-2xl font-bold text-white">Image Enhancer</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Improve sharpness, clean images, optimize assets, and prepare visuals for web or social use.
                    </p>
                    <a href="#" class="tool-link mt-6">Use Tool</a>
                </article>

                <!-- SEO Tool -->
                <article class="tool-feature-card md:col-span-2">
                    <span class="tool-pill">Marketing Tool</span>
                    <h3 class="mt-4 text-2xl font-bold text-white">SEO Assistant</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Get title, keyword, and content suggestions to improve search visibility and content quality.
                    </p>
                    <a href="#" class="tool-link mt-6">Optimize Now</a>
                </article>
            </div>
        </div>
    </section> --}}

    <!-- Tool Categories -->
    <section id="all-tools" class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center">
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Tool Categories
                </div>

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Tools for writing, career, media,
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        and business productivity
                    </span>
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="tool-category-card">
                    <div class="tool-category-icon bg-cyan-500/15 text-cyan-200">AI</div>
                    <h3 class="mt-5 text-xl font-bold text-white">AI Tools</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Smart generators, writing assistants, automation helpers, and idea tools.
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-blue-50/82">
                        <li class="tool-bullet">AI Content Writer</li>
                        <li class="tool-bullet">AI Email Writer</li>
                        <li class="tool-bullet">Prompt Generator</li>
                    </ul>
                </div>

                <div class="tool-category-card">
                    <div class="tool-category-icon bg-blue-500/15 text-blue-200">CV</div>
                    <h3 class="mt-5 text-xl font-bold text-white">Career Tools</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Practical tools to help job seekers create stronger professional materials.
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-blue-50/82">
                        <li class="tool-bullet">CV Builder</li>
                        <li class="tool-bullet">Cover Letter Builder</li>
                        <li class="tool-bullet">Portfolio Outline Tool</li>
                    </ul>
                </div>

                <div class="tool-category-card">
                    <div class="tool-category-icon bg-sky-500/15 text-sky-200">IMG</div>
                    <h3 class="mt-5 text-xl font-bold text-white">Media Tools</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Improve and optimize images and digital assets for better performance.
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-blue-50/82">
                        <li class="tool-bullet">Image Enhancer</li>
                        <li class="tool-bullet">Image Compressor</li>
                        <li class="tool-bullet">Social Resize Tool</li>
                    </ul>
                </div>

                <div class="tool-category-card">
                    <div class="tool-category-icon bg-violet-500/15 text-violet-200">BIZ</div>
                    <h3 class="mt-5 text-xl font-bold text-white">Business Tools</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Useful helpers for everyday office work, documentation, and client-facing output.
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-blue-50/82">
                        <li class="tool-bullet">Invoice Generator</li>
                        <li class="tool-bullet">Proposal Builder</li>
                        <li class="tool-bullet">Document Formatter</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- More Tools Grid -->
    <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white sm:text-4xl">More Tools</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-blue-100/68 sm:text-base">
                        Expand your workflow with tools designed for faster execution and better output.
                    </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-cyan-500/15 text-cyan-200">CL</div>
                        <span class="tool-small-pill">Career</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">Cover Letter Builder</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Draft tailored cover letters faster with guided structure and clean formatting.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>

                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-blue-500/15 text-blue-200">EM</div>
                        <span class="tool-small-pill">AI</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">AI Email Generator</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Write clearer business emails, responses, and outreach messages in seconds.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>

                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-sky-500/15 text-sky-200">SM</div>
                        <span class="tool-small-pill">Media</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">Social Caption Tool</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Generate engaging captions and social copy with the right tone and clarity.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>

                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-violet-500/15 text-violet-200">PR</div>
                        <span class="tool-small-pill">Business</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">Proposal Builder</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Prepare structured business proposals and service outlines more efficiently.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>

                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-emerald-500/15 text-emerald-200">IN</div>
                        <span class="tool-small-pill">Business</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">Invoice Generator</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Create clean invoice layouts quickly for clients, freelancers, and businesses.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>

                <article class="tool-grid-card">
                    <div class="tool-grid-top">
                        <div class="tool-grid-icon bg-pink-500/15 text-pink-200">SE</div>
                        <span class="tool-small-pill">SEO</span>
                    </div>
                    <h3 class="mt-5 text-2xl font-bold text-white">Meta Tag Generator</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Create page titles, descriptions, and structured metadata faster and more cleanly.
                    </p>
                    <a href="#" class="tool-link mt-6">Open Tool</a>
                </article>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center">
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    How It Works
                </div>

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Simple workflow.
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        Powerful results.
                    </span>
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="tool-flow-card">
                    <div class="tool-flow-step">01</div>
                    <h3 class="mt-6 text-2xl font-bold text-white">Choose a Tool</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Select the right AI or utility tool based on your task, goal, or workflow.
                    </p>
                </div>

                <div class="tool-flow-card">
                    <div class="tool-flow-step">02</div>
                    <h3 class="mt-6 text-2xl font-bold text-white">Input Your Data</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Enter content, details, or prompts and let the tool process everything smoothly.
                    </p>
                </div>

                <div class="tool-flow-card">
                    <div class="tool-flow-step">03</div>
                    <h3 class="mt-6 text-2xl font-bold text-white">Get Better Output</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        Receive faster, cleaner, and more useful results you can directly use or refine.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    {{-- <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.30),rgba(15,23,42,0.88)_55%,rgba(2,6,23,0.96)_100%)]"></div>
        <div class="absolute left-[10%] top-[10%] h-52 w-52 rounded-full bg-cyan-400/12 blur-3xl"></div>
        <div class="absolute right-[8%] bottom-[10%] h-72 w-72 rounded-full bg-blue-500/18 blur-3xl"></div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div
                class="overflow-hidden rounded-[34px] border border-white/15 bg-white/8 px-6 py-10 sm:px-8 sm:py-12 lg:px-14 lg:py-14 backdrop-blur-2xl shadow-[0_30px_100px_rgba(0,0,0,0.28)]">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                        Want a custom tool
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            for your business?
                        </span>
                    </h2>

                    <p class="mt-5 text-sm leading-7 text-blue-100/70 sm:text-base">
                        We can build tailored business tools, AI workflows, productivity systems, and internal
                        utilities based on your exact operational needs.
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <a href="#contact"
                            class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                            Request Custom Tool
                        </a>

                        <a href="{{ route('client.services') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-6 py-3.5 font-semibold text-white backdrop-blur-xl transition hover:bg-white/12">
                            Explore Services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}
</div>