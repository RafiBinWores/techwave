<?php

use App\Models\Product;
use App\Services\NetworkLicensing;
use App\Services\Products\ProductDownloads;
use App\Services\Products\ProductPresentation;
use App\Services\Products\ProductPricing;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Locked]
    public Product $product;

    public function mount(string $slug): void
    {
        $this->product = Product::published()->where('slug', $slug)->firstOrFail();
    }

    public function downloadSoftware(): void
    {
        abort_unless(auth()->check(), 403);
        $product = Product::published()->whereKey($this->product->id)->firstOrFail();
        abort_unless(app(NetworkLicensing::class)->isProduct($product), 404);
        $prepared = app(NetworkLicensing::class)->prepareDownload($product, false);
        // Pass the one-time key through the user's session, never through a URL or a public file.
        session()->flash('netwatch_download_prepared', [
            'user_id' => auth()->id(), 'product_id' => $product->id,
            'activation_key' => $prepared['key'], 'prepared_at' => now()->timestamp,
        ]);
        $this->redirectRoute('client.products.download.setup', ['slug' => $product->slug]);
    }

    public function with(): array
    {
        // Publication and pricing visibility are rechecked for an already-open page.
        $product = Product::published()->whereKey($this->product->id)->firstOrFail();

        $plans = $product->pricing_enabled
            ? $product->publishedPlans()
                ->with('product', 'toolPlan')
                ->whereIn('pricing_mode', ['subscription', 'one_time', 'quotation'])
                ->get()
            : collect();

        $features = $plans
            ->flatMap(fn ($plan) => $plan->features ?? [])
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        return [
            'product' => $product,
            'plans' => $plans,
            'features' => $features,
            'isNetwatch' => app(NetworkLicensing::class)->isProduct($product),
            'hasRelease' => app(ProductDownloads::class)->available($product),
            'canDownload' => app(ProductDownloads::class)->eligible(auth()->user(), $product),
            'overview' => app(ProductPresentation::class)->overview($product->description),
            'demo' => $product->demo_url && ProductPricing::safeUrl($product->demo_url)
                ? $product->demo_url
                : null,
            'related' => Product::published()
                ->whereKeyNot($product->id)
                ->orderBy('sort_order')
                ->limit(3)
                ->get(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return $this->view()->title($this->product->name.' | Techwave');
    }
};
?>

<div class="relative isolate text-white">
    @push('meta')
    <meta name="description" content="{{ $product->summary }}">
    <meta property="og:title" content="{{ $product->name }} | Techwave">
    <meta property="og:description" content="{{ $product->summary }}">

    @if ($product->cover_path)
    <meta property="og:image" content="{{ \App\Services\UploadStorage::url($product->cover_path) }}">
    @endif
    @endpush

    {{-- An open hero gives the product name and its actual screenshot room to lead. --}}
    <header class="relative overflow-hidden">
        <div class="mx-auto w-[calc(100%-40px)] max-w-7xl sm:w-[calc(100%-64px)] relative">
            <div class="grid items-center gap-11 pt-11 pb-13 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.15fr)] lg:gap-16 lg:pt-16 lg:pb-19">
                <div class="relative z-10">
                    <h1 class="
                        mt-6 max-w-[680px] text-[clamp(40px,11vw,58px)] leading-[1.06] font-bold tracking-[-0.055em] text-blue-50
                        [overflow-wrap:anywhere] sm:text-[clamp(46px,8vw,72px)] lg:text-[clamp(48px,5.2vw,76px)]
                    ">{{ $product->name }}</h1>

                    <p class="mt-6.5 max-w-[600px] text-[15px] leading-[1.85] text-blue-100/60 sm:text-base lg:max-w-[460px]">{{ $product->summary }}</p>

                    @error('license')
                        <p role="alert" class="mt-5 rounded-xl border border-red-300/20 bg-red-400/10 p-4 text-sm text-red-200">{{ $message }}</p>
                    @enderror
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @if ($plans->isNotEmpty())
                        <a href="#pricing" class="
                        inline-flex min-h-[50px] items-center justify-center gap-5 rounded-[10px] border border-blue-300/25
                        bg-linear-[110deg,#2879ed,#06a8dc] px-5.5 py-3.5 text-[13px] font-semibold text-white
                        shadow-[0_8px_28px_rgb(14_114_223/0.18)] transition duration-180 hover:-translate-y-0.5
                        hover:shadow-[0_12px_30px_rgb(14_114_223/0.3)] motion-reduce:transition-none
                    ">
                            Find your plan
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">
                                arrow_forward
                            </span>
                        </a>
                        @elseif ($overview)
                        <a href="#overview" class="
                        inline-flex min-h-[50px] items-center justify-center gap-5 rounded-[10px] border border-blue-300/25
                        bg-linear-[110deg,#2879ed,#06a8dc] px-5.5 py-3.5 text-[13px] font-semibold text-white
                        shadow-[0_8px_28px_rgb(14_114_223/0.18)] transition duration-180 hover:-translate-y-0.5
                        hover:shadow-[0_12px_30px_rgb(14_114_223/0.3)] motion-reduce:transition-none
                    ">
                            Discover the product
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">
                                arrow_forward
                            </span>
                        </a>
                        @elseif ($product->installation_steps)
                        <a href="#installation" class="
                        inline-flex min-h-[50px] items-center justify-center gap-5 rounded-[10px] border border-blue-300/25
                        bg-linear-[110deg,#2879ed,#06a8dc] px-5.5 py-3.5 text-[13px] font-semibold text-white
                        shadow-[0_8px_28px_rgb(14_114_223/0.18)] transition duration-180 hover:-translate-y-0.5
                        hover:shadow-[0_12px_30px_rgb(14_114_223/0.3)] motion-reduce:transition-none
                    ">
                            Get started
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">
                                arrow_forward
                            </span>
                        </a>
                        @else
                        <a href="{{ route('client.contact') }}" wire:navigate class="
                        inline-flex min-h-[50px] items-center justify-center gap-5 rounded-[10px] border border-blue-300/25
                        bg-linear-[110deg,#2879ed,#06a8dc] px-5.5 py-3.5 text-[13px] font-semibold text-white
                        shadow-[0_8px_28px_rgb(14_114_223/0.18)] transition duration-180 hover:-translate-y-0.5
                        hover:shadow-[0_12px_30px_rgb(14_114_223/0.3)] motion-reduce:transition-none
                    ">
                            Talk to us
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">
                                arrow_forward
                            </span>
                        </a>
                        @endif

                        {{-- Guests use the shared login modal; the download route independently enforces access. --}}
                        @if ($hasRelease)
                        @guest
                        <button
                            type="button"
                            x-data
                            @click="window.dispatchEvent(new CustomEvent('open-auth', { detail: { mode: 'login' } }))"
                            class="inline-flex min-h-[50px] items-center justify-center gap-2 rounded-[10px]
                                        border border-cyan-300/30 bg-cyan-400/10 px-5 py-3 text-sm font-semibold
                                        text-cyan-100 transition-colors hover:bg-cyan-400/20">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">lock</span>
                            Log in to download
                        </button>
                        @else
                        @if ($canDownload)
                        @if ($isNetwatch)
                        <button
                            type="button" wire:click="downloadSoftware" wire:loading.attr="disabled" wire:target="downloadSoftware"
                            class="inline-flex min-h-[50px] items-center justify-center gap-2 rounded-[10px]
                                            border border-cyan-300/30 bg-cyan-400/10 px-5 py-3 text-sm font-semibold
                                            text-cyan-100 transition-colors hover:bg-cyan-400/20">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">download</span>
                            Free Download
                        </button>
                        @else
                        <a
                            href="{{ route('client.products.download', $product->slug) }}"
                            class="inline-flex min-h-[50px] items-center justify-center gap-2 rounded-[10px]
                                            border border-cyan-300/30 bg-cyan-400/10 px-5 py-3 text-sm font-semibold
                                            text-cyan-100 transition-colors hover:bg-cyan-400/20">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">download</span>
                            Free Download
                        </a>
                        @endif
                        @else
                        <p class="text-xs leading-6 text-blue-100/60">
                            Sign in to download this software.
                        </p>
                        @endif
                        @endguest
                        @endif

                        @if ($demo)
                        <a
                            href="{{ $demo }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2.5 text-xs font-medium text-blue-100">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-blue-200/20 text-cyan-200" aria-hidden="true">
                                <span class="material-symbols-outlined text-lg">play_arrow</span>
                            </span>
                            Explore live demo
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Decorative blue light grounds the angled preview without changing the uploaded image. --}}
                <div
                    x-data="{
                        openPreview() {
                            this.$refs.previewDialog.showModal();
                            document.documentElement.classList.add('overflow-hidden');
                        },
                        closePreview() {
                            this.$refs.previewDialog.close();
                        },
                        destroy() {
                            document.documentElement.classList.remove('overflow-hidden');
                        }
                    }"
                    class="relative isolate min-w-0 max-w-[780px] pt-5 pb-12 sm:pt-8 sm:pb-[70px]">
                    {{-- A lit grid and layered glass give the screenshot a technical showcase of its own. --}}
                    <!-- <div
                        aria-hidden="true"
                        class="pointer-events-none absolute -inset-x-[8%] inset-y-0 z-0">
                        <div class="absolute -inset-5 rounded-[3rem] bg-slate-950/65 backdrop-blur-xl
                            [mask-image:radial-gradient(ellipse,black_25%,transparent_72%)]"></div>
                        <div class="absolute inset-0
                            bg-[radial-gradient(ellipse_at_15%_25%,rgb(37_99_235/0.4),transparent_55%),radial-gradient(ellipse_at_85%_70%,rgb(6_182_212/0.35),transparent_55%)]
                            blur-3xl"></div>
                        <div class="absolute inset-x-0 top-[8%] bottom-[4%]
                            bg-[linear-gradient(rgb(56_189_248/0.12)_1px,transparent_1px),linear-gradient(90deg,rgb(56_189_248/0.12)_1px,transparent_1px)]
                            bg-size-[36px_36px] [transform:perspective(900px)_rotateY(-12deg)_rotateZ(-3deg)]
                            [mask-image:radial-gradient(ellipse,black_30%,transparent_72%)]"></div>
                    </div> -->

                    {{-- These rear panels share the screenshot's plane without changing its position. --}}
                    <div
                        aria-hidden="true"
                        class="pointer-events-none absolute inset-x-0 top-5 bottom-12 z-0 sm:top-8 sm:bottom-[70px]
                            [transform:perspective(1400px)_rotateY(-10deg)_rotateX(3deg)_rotateZ(-3deg)]">
                        <div class="absolute inset-0 translate-x-5 translate-y-7 rounded-2xl border border-blue-300/15
                            bg-linear-to-br from-blue-500/10 to-slate-950/35 backdrop-blur-md sm:translate-x-7 sm:translate-y-9"></div>
                        <div class="absolute inset-0 translate-x-2.5 translate-y-3.5 rounded-2xl border border-cyan-200/30
                            bg-linear-to-br from-blue-400/15 via-slate-900/40 to-cyan-400/10 backdrop-blur-lg
                            shadow-[12px_20px_45px_-20px_rgb(6_182_212/0.5)] sm:translate-x-4 sm:translate-y-5">
                            <span class="absolute inset-x-[15%] -bottom-px h-px bg-linear-to-r
                                from-transparent via-cyan-200 to-transparent shadow-[0_0_16px_2px_rgb(34_211_238/0.45)]"></span>
                            <span class="absolute -right-px inset-y-[18%] w-px bg-linear-to-b
                                from-transparent via-blue-300/80 to-transparent"></span>
                        </div>
                    </div>

                    <div class="
                        relative z-10 origin-center overflow-hidden rounded-2xl border border-white/25
                        bg-linear-[135deg,rgb(186_230_253/0.2),rgb(30_58_138/0.12)_50%,rgb(34_211_238/0.15)] p-[5px]
                        shadow-[0_30px_70px_-20px_rgb(0_0_0/0.65),0_0_45px_-15px_rgb(56_189_248/0.2),inset_0_1px_0_rgb(255_255_255/0.15)]
                        [transform:perspective(900px)_rotateY(-4deg)_rotateZ(-2deg)] sm:p-[7px]
                        sm:[transform:perspective(1200px)_rotateY(-6deg)_rotateZ(-2deg)]
                        lg:[transform:perspective(1400px)_translate(8px,-8px)_rotateY(-10deg)_rotateX(3deg)_rotateZ(-3deg)]
                    ">
                        @if ($product->cover_path)
                        <button
                            type="button"
                            @click="openPreview()"
                            aria-label="Open full-size {{ $product->name }} image"
                            aria-haspopup="dialog"
                            class="group relative block w-full cursor-zoom-in rounded-[10px]
                                    focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300">
                            <img
                                src="{{ \App\Services\UploadStorage::url($product->cover_path) }}"
                                alt="{{ $product->name }} product preview"
                                fetchpriority="high"
                                class="block h-auto max-h-[460px] w-full rounded-[10px] object-contain">
                        </button>
                        @else
                        <div class="flex min-h-[330px] flex-col items-center justify-center rounded-[10px] bg-linear-[145deg,#11233e,#0a1427] p-8 text-center">
                            <span class="material-symbols-outlined text-6xl text-cyan-200" aria-hidden="true">
                                deployed_code
                            </span>
                            <p class="mt-5 text-2xl font-semibold">{{ $product->name }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.2em] text-blue-100/40">
                                Software by Techwave
                            </p>
                        </div>
                        @endif
                    </div>

                    @if ($product->cover_path)
                    {{-- Native dialogs provide focus trapping, Escape dismissal, and focus restoration. --}}
                    <dialog
                        x-ref="previewDialog"
                        aria-label="{{ $product->name }} image preview"
                        @click.self="closePreview()"
                        @close="document.documentElement.classList.remove('overflow-hidden')"
                        class="fixed inset-0 m-0 h-dvh max-h-none w-screen max-w-none border-0 bg-transparent p-4
                                text-white backdrop:bg-black/30 backdrop:backdrop-blur-xl
                                open:flex open:items-center open:justify-center sm:p-10">
                        <button
                            type="button"
                            @click="closePreview()"
                            aria-label="Close image preview"
                            autofocus
                            class="absolute top-4 right-4 z-10 flex size-11 items-center justify-center rounded-full
                                    border border-white/30 bg-white/10 text-white shadow-lg backdrop-blur-xl
                                    transition-colors hover:bg-white/25
                                    focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-300
                                    sm:top-6 sm:right-6">
                            <span class="material-symbols-outlined text-2xl" aria-hidden="true">close</span>
                        </button>
                        <figure class="pointer-events-none w-full max-w-7xl pt-12">
                            <img
                                src="{{ \App\Services\UploadStorage::url($product->cover_path) }}"
                                alt="{{ $product->name }} full-size product preview"
                                loading="lazy"
                                class="pointer-events-auto mx-auto max-h-[calc(100dvh-160px)] w-auto max-w-full
                                        rounded-xl border border-white/20 object-contain shadow-2xl shadow-black/30">
                            <figcaption class="mt-4 text-center text-sm text-white/90 drop-shadow-lg">
                                {{ $product->name }}
                            </figcaption>
                        </figure>
                    </dialog>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- Section navigation stays available while visitors explore longer product pages. --}}
    <div class="sticky top-[88px] z-20 border-y border-white/10 backdrop-blur-lg sm:top-24">
        <div class="mx-auto w-[calc(100%-40px)] max-w-7xl sm:w-[calc(100%-64px)] flex items-center justify-between gap-6">
            <span class="hidden shrink-0 text-sm font-semibold text-white/80 sm:block">
                {{ $product->name }}
            </span>

            <nav aria-label="Product sections" class="flex min-w-0 items-center gap-7 overflow-x-auto py-5 sm:gap-9">
                @if ($overview)
                <a href="#overview" class="shrink-0 text-xs font-medium text-blue-100/50 transition-colors hover:text-cyan-200">Overview</a>
                @endif

                @if ($product->installation_steps)
                <a href="#installation" class="shrink-0 text-xs font-medium text-blue-100/50 transition-colors hover:text-cyan-200">How to install</a>
                @endif

                @if ($plans->isNotEmpty())
                <a href="#pricing" class="shrink-0 text-xs font-medium text-blue-100/50 transition-colors hover:text-cyan-200">Plans & pricing</a>
                @endif

                @if ($demo)
                <a
                    href="{{ $demo }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 text-xs font-medium text-blue-100/50 transition-colors hover:text-cyan-200 inline-flex items-center gap-1.5">
                    Live demo
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">north_east</span>
                </a>
                @endif
            </nav>
        </div>
    </div>

    <div class="mx-auto w-[calc(100%-40px)] max-w-7xl sm:w-[calc(100%-64px)] pb-20">
        {{-- Published plan features form a compact product highlights strip. --}}
        @if ($features->isNotEmpty())
        <section aria-label="Product highlights" class="grid grid-cols-1 gap-7 border-b border-white/10 py-9 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as $feature)
            <div class="flex items-start gap-3">
                <span class="mt-px inline-flex size-[21px] shrink-0 items-center justify-center rounded-full border border-cyan-300/20 bg-cyan-300/5 text-cyan-300" aria-hidden="true">
                    <span class="material-symbols-outlined text-sm">check</span>
                </span>
                <p class="text-sm leading-relaxed text-blue-100/75">{{ $feature }}</p>
            </div>
            @endforeach
        </section>
        @endif

        {{-- Match the service options page's translucent panels; the overview HTML is sanitized before rendering. --}}
        @if ($overview)
        <section
            id="overview"
            class="my-8 grid scroll-mt-44 gap-8 rounded-3xl border border-white/10 bg-white/5
                p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-2xl
                sm:mt-12 sm:mb-6 sm:p-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)] lg:gap-16 lg:p-12">
            <div>
                <p class="flex items-center gap-2.5 text-[10px] leading-[1.6] font-semibold tracking-[0.18em] text-cyan-300 uppercase">Meet your software</p>
                <h2 class="text-[clamp(30px,3vw,44px)] leading-[1.2] font-semibold tracking-[-0.04em] text-blue-50 mt-4">
                    The details.<br>
                    <span class="text-blue-200/55">The possibilities.</span>
                </h2>
                <p class="mt-5 max-w-xs text-sm leading-7 text-blue-100/45">
                    Get to know {{ $product->name }} and see how it fits into your work.
                </p>
            </div>

            <div
                class="
                        [overflow-wrap:anywhere] [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:border [&_pre]:border-white/10
                        [&_pre]:bg-black/25 [&_pre]:p-4 [&_pre]:font-mono [&_pre]:text-cyan-100
                        [&_.ql-code-block-container]:overflow-x-auto [&_.ql-code-block-container]:rounded-xl
                        [&_.ql-code-block-container]:border [&_.ql-code-block-container]:border-white/10
                        [&_.ql-code-block-container]:bg-black/25 [&_.ql-code-block-container]:p-4
                        [&_.ql-code-block-container]:font-mono [&_.ql-code-block-container]:text-cyan-100
                        [&_.ql-code-block]:whitespace-pre [&_.ql-code-block]:min-h-[1.5em] [&_.ql-align-center]:text-center
                        [&_.ql-align-right]:text-right [&_.ql-align-justify]:text-justify [&_.ql-direction-rtl]:[direction:rtl]
                        [&_.ql-font-serif]:font-serif [&_.ql-font-monospace]:font-mono [&_.ql-size-small]:text-[0.8em]
                        [&_.ql-size-large]:text-[1.4em] [&_.ql-size-huge]:text-[1.8em] [&_li[data-list=bullet]]:list-disc
                        [&_li[data-list=ordered]]:list-decimal [&_.ql-indent-1]:ps-[2em] [&_.ql-indent-2]:ps-[4em]
                        [&_.ql-indent-3]:ps-[6em] [&_.ql-indent-4]:ps-[8em] [&_.ql-indent-5]:ps-[10em] [&_.ql-indent-6]:ps-[12em]
                        [&_.ql-indent-7]:ps-[14em] [&_.ql-indent-8]:ps-[16em] prose prose-invert prose-blue min-w-0 max-w-none
                        text-base leading-8 text-blue-100/65 prose-headings:font-semibold prose-headings:tracking-tight
                        prose-headings:text-white prose-a:text-cyan-300 prose-strong:text-blue-50
                    ">
                {!! $overview !!}
            </div>
        </section>
        @endif

        @if ($product->gallery)
        <section aria-label="Product screenshots" class="grid gap-6 pb-4 md:grid-cols-2">
            @foreach ($product->gallery as $entry)
            <figure class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-2xl">
                <img
                    src="{{ \App\Services\UploadStorage::url($entry['path']) }}"
                    alt="{{ $entry['alt'] ?? $product->name }}"
                    loading="lazy"
                    class="w-full object-contain">

                @if (!empty($entry['caption']))
                <figcaption class="px-5 py-4 text-sm text-blue-100/50">
                    {{ $entry['caption'] }}
                </figcaption>
                @endif
            </figure>
            @endforeach
        </section>
        @endif

        {{-- Native disclosure elements keep longer setup guides easy to scan and keyboard-accessible. --}}
        @if ($product->installation_steps)
        <section id="installation" class="scroll-mt-44 pt-0 pb-13 sm:pb-20 [&+section]:pt-6">
            <div class="grid gap-9 rounded-3xl border border-white/10 bg-white/5
                p-6 shadow-2xl shadow-blue-950/20 backdrop-blur-2xl
                sm:p-11 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)] lg:gap-18">
                <div>
                    <p class="flex items-center gap-2.5 text-[10px] leading-[1.6] font-semibold tracking-[0.18em] text-cyan-300 uppercase">From download to done</p>
                    <h2 class="text-[clamp(30px,3vw,44px)] leading-[1.2] font-semibold tracking-[-0.04em] text-blue-50 mt-4">
                        Let's get<br>you set up.
                    </h2>
                    <p class="mt-5 max-w-xs text-sm leading-7 text-blue-100/45">
                        Follow the installation guide and start exploring {{ $product->name }}.
                    </p>
                </div>

                <ol class="min-w-0">
                    @foreach ($product->installation_steps as $step)
                    <li class="border-b border-white/10 last:border-b-0">
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-4 py-5.5
                                group-open:pb-2 [&::-webkit-details-marker]:hidden">
                                <span class="font-mono text-xs text-cyan-300">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="flex-1 text-base font-medium">{{ $step['title'] }}</span>
                                <span class="material-symbols-outlined text-blue-100/40 transition-transform duration-150 group-open:rotate-180 motion-reduce:transition-none text-xl" aria-hidden="true">
                                    expand_more
                                </span>
                            </summary>

                            <div class="min-w-0 pb-6 pl-8">
                                @if (!empty($step['text']))
                                {{-- Inline content avoids preserving template indentation as blank lines. --}}
                                <p class="whitespace-pre-line text-sm leading-7 text-blue-100/55">{{ trim($step['text']) }}</p>
                                @endif

                                @if (!empty($step['command']))
                                <div
                                    x-data="{ copied: false, failed: false }"
                                    class="mt-5 overflow-hidden rounded-xl border border-white/10 bg-black/25">
                                    <div class="flex items-center justify-between border-b border-white/8 px-4 py-2.5">
                                        <span class="text-[10px] uppercase tracking-[0.15em] text-blue-100/35">
                                            Terminal
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                            if (!navigator.clipboard) {
                                                                failed = true;
                                                            } else {
                                                                navigator.clipboard.writeText($refs.command.textContent)
                                                                    .then(() => {
                                                                        copied = true;
                                                                        failed = false;
                                                                        setTimeout(() => copied = false, 2000);
                                                                    })
                                                                    .catch(() => failed = true);
                                                            }
                                                        "
                                            class="inline-flex items-center gap-1.5 text-xs text-cyan-200">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">
                                                content_copy
                                            </span>
                                            <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                        </button>
                                    </div>

                                    {{-- Keep command content inline so copied text gains no indentation. --}}
                                    <pre class="overflow-x-auto p-4 text-xs leading-7 text-cyan-100"><code x-ref="command">{{ $step['command'] }}</code></pre>

                                    <p x-show="failed" x-cloak role="status" class="px-4 pb-4 text-xs text-blue-100/50">
                                        Select and copy the command above.
                                    </p>
                                </div>
                                @endif

                                @if (!empty($step['screenshot_path']))
                                <img
                                    src="{{ \App\Services\UploadStorage::url($step['screenshot_path']) }}"
                                    alt="{{ $step['caption'] ?? $step['title'] }}"
                                    loading="lazy"
                                    class="mt-5 w-full rounded-xl border border-white/10">
                                @endif
                            </div>
                        </details>
                    </li>
                    @endforeach
                </ol>
            </div>
        </section>
        @endif

        {{-- Pricing remains driven by the saved plan data and saved product pricing. --}}
        @if ($plans->isNotEmpty())
        @php
            $planPrices = $plans->mapWithKeys(fn ($plan) => [
                $plan->id => array_filter($plan->effectivePricing(), fn ($price) => $price !== null),
            ]);
            $hasMonthly = $planPrices->contains(fn ($prices) => isset($prices['monthly']));
            $hasYearly = $planPrices->contains(fn ($prices) => isset($prices['yearly']));
            $hasOneTime = $planPrices->contains(fn ($prices) => isset($prices['one_time']));
            $showGlobalBillingToggle = $hasMonthly && $hasYearly;
            $defaultBilling = $hasMonthly ? 'monthly' : ($hasYearly ? 'yearly' : 'one_time');
        @endphp
        <section
            id="pricing"
            x-data="{ billing: @js($defaultBilling) }"
            class="scroll-mt-44 py-13 sm:py-20 [&+section]:pt-6"
        >
            {{-- Match service pricing with a single billing control shared by every subscription card. --}}
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-4 py-2 text-xs text-blue-100/80 backdrop-blur-xl">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Product Plans
                </div>
                <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Choose the right package</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-blue-100/66">
                            Select a suitable plan for {{ $product->name }} based on the features and allowances you need.
                        </p>
                    </div>
                    @if ($showGlobalBillingToggle)
                        <div role="group" aria-label="Product billing period" class="inline-grid grid-cols-2 rounded-full border border-white/10 bg-white/8 p-1 backdrop-blur-xl">
                            <button type="button" @click="billing = 'monthly'"
                                class="rounded-full px-5 py-2.5 text-sm font-semibold transition sm:px-6"
                                :class="billing === 'monthly'
                                    ? 'bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-400/20'
                                    : 'text-blue-100/70 hover:text-white'">
                                Monthly
                            </button>
                            <button type="button" @click="billing = 'yearly'"
                                class="rounded-full px-5 py-2.5 text-sm font-semibold transition sm:px-6"
                                :class="billing === 'yearly'
                                    ? 'bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-400/20'
                                    : 'text-blue-100/70 hover:text-white'">
                                Yearly
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div
                @class([ 'grid gap-6 pt-6',
                    'md:grid-cols-1 md:max-w-md md:mx-auto' => $plans->count() === 1,
                    'md:grid-cols-2 md:max-w-5xl md:mx-auto' => $plans->count() === 2,
                    'md:grid-cols-2 xl:grid-cols-3' => $plans->count() > 2,
                ])
            >
                @foreach ($plans as $plan)
                @php
                    $prices = $planPrices[$plan->id] ?? [];
                    $isPopular = $plan->badge && str_contains(strtolower($plan->badge), 'popular');
                    $cardClass = filled($plan->badge)
                        ? 'border-cyan-300/25 bg-linear-to-b from-blue-500/12 to-white/8 shadow-[0_25px_80px_rgba(0,0,0,0.24)]'
                        : 'border-white/10 bg-white/6 shadow-[0_20px_60px_rgba(0,0,0,0.18)]';
                @endphp
                @continue(empty($prices) && $plan->pricing_mode !== 'quotation')

                <div
                    class="group relative rounded-[30px] border {{ $cardClass }} p-6 backdrop-blur-2xl transition duration-300 hover:-translate-y-1 hover:border-cyan-300/25"
                >
                    <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-cyan-300/70 to-transparent"></div>

                    @if ($plan->badge)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex rounded-full border border-cyan-300/70 bg-cyan-400 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-950 shadow-lg shadow-cyan-400/20">
                                {{ $plan->badge }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-4 {{ filled($plan->badge) ? 'pt-4' : '' }}">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.22em] text-cyan-200/80">{{ $product->name }}</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">{{ $plan->name }}</h3>
                        </div>
                    </div>

                    @if ($plan->description)
                        <p class="mt-4 text-sm leading-7 text-blue-100/68">{{ $plan->description }}</p>
                    @endif

                    {{-- Custom subscriptions use a contact action rather than a fixed checkout price. --}}
                    @if ($plan->pricing_mode === 'quotation')
                        <div class="mt-6">
                            <p class="text-3xl font-bold text-white">Custom pricing</p>
                            <p class="mt-3 text-sm leading-7 text-blue-100/66">Contact us for a subscription tailored to your requirements.</p>
                        </div>
                        <a href="{{ route('client.contact', ['product' => $product->slug, 'plan' => $plan->id]) }}"
                            wire:navigate
                            class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 backdrop-blur-xl transition hover:-translate-y-0.5">
                            Contact us
                        </a>
                    @endif
                    <div class="mt-6">
                        @if (isset($prices['monthly']))
                            <div x-show="billing === 'monthly'" x-cloak>
                                <div class="flex items-end gap-2">
                                    <span class="text-4xl font-bold text-white">৳ {{ number_format((float) $prices['monthly'], 2) }}</span>
                                    <span class="pb-1 text-sm text-blue-100/60">/ month</span>
                                </div>
                            </div>
                        @endif

                        @if (isset($prices['yearly']))
                            <div x-show="billing === 'yearly'" x-cloak>
                                <div class="flex items-end gap-2">
                                    <span class="text-4xl font-bold text-white">৳ {{ number_format((float) $prices['yearly'], 2) }}</span>
                                    <span class="pb-1 text-sm text-blue-100/60">/ year</span>
                                </div>
                            </div>
                        @endif

                        @if (isset($prices['monthly']) && !isset($prices['yearly']))
                            <div x-show="billing === 'yearly'" x-cloak>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-lg font-bold text-white">Yearly unavailable</p>
                                    <p class="mt-1 text-sm leading-6 text-blue-100/60">This plan is available only as a monthly package.</p>
                                </div>
                            </div>
                        @endif

                        @if (isset($prices['yearly']) && !isset($prices['monthly']))
                            <div x-show="billing === 'monthly'" x-cloak>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-lg font-bold text-white">Monthly unavailable</p>
                                    <p class="mt-1 text-sm leading-6 text-blue-100/60">This plan is available only as a yearly package.</p>
                                </div>
                            </div>
                        @endif

                        @if (isset($prices['one_time']) && !isset($prices['monthly']) && !isset($prices['yearly']))
                            <div class="flex items-end gap-2">
                                <span class="text-4xl font-bold text-white">৳ {{ number_format((float) $prices['one_time'], 2) }}</span>
                            </div>
                        @endif
                    </div>

                    @if (isset($prices['monthly']))
                        @php($checkout = app(\App\Services\Products\ProductPresentation::class)->checkoutUrl($plan, 'monthly'))
                        <div x-show="billing === 'monthly'" x-cloak>
                            @if ($checkout)
                                <a href="{{ route('client.products.purchase', ['slug' => $product->slug, 'plan' => $plan->id, 'cycle' => 'monthly']) }}"
                                    class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 backdrop-blur-xl transition hover:-translate-y-0.5">
                                    Choose Plan
                                </a>
                            @else
                                <button type="button" disabled class="mt-6 inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 font-semibold text-blue-100/45 backdrop-blur-xl">
                                    Online checkout coming soon
                                </button>
                            @endif
                        </div>
                    @endif

                    @if (isset($prices['yearly']))
                        @php($checkout = app(\App\Services\Products\ProductPresentation::class)->checkoutUrl($plan, 'yearly'))
                        <div x-show="billing === 'yearly'" x-cloak>
                            @if ($checkout)
                                <a href="{{ route('client.products.purchase', ['slug' => $product->slug, 'plan' => $plan->id, 'cycle' => 'yearly']) }}"
                                    class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 backdrop-blur-xl transition hover:-translate-y-0.5">
                                    Choose Plan
                                </a>
                            @else
                                <button type="button" disabled class="mt-6 inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 font-semibold text-blue-100/45 backdrop-blur-xl">
                                    Online checkout coming soon
                                </button>
                            @endif
                        </div>
                    @endif

                    @if (isset($prices['monthly']) && !isset($prices['yearly']))
                        <div x-show="billing === 'yearly'" x-cloak>
                            <button type="button" disabled class="mt-6 inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 font-semibold text-blue-100/45 backdrop-blur-xl">
                                Choose Plan
                            </button>
                        </div>
                    @endif

                    @if (isset($prices['yearly']) && !isset($prices['monthly']))
                        <div x-show="billing === 'monthly'" x-cloak>
                            <button type="button" disabled class="mt-6 inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 font-semibold text-blue-100/45 backdrop-blur-xl">
                                Choose Plan
                            </button>
                        </div>
                    @endif

                    @if (isset($prices['one_time']) && !isset($prices['monthly']) && !isset($prices['yearly']))
                        @php($checkout = app(\App\Services\Products\ProductPresentation::class)->checkoutUrl($plan, 'one_time'))
                        @if ($checkout)
                            <a href="{{ route('client.products.purchase', ['slug' => $product->slug, 'plan' => $plan->id, 'cycle' => 'one_time']) }}"
                                class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 backdrop-blur-xl transition hover:-translate-y-0.5">
                                Buy now
                            </a>
                        @else
                            <button type="button" disabled class="mt-6 inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 font-semibold text-blue-100/45 backdrop-blur-xl">
                                Online checkout coming soon
                            </button>
                        @endif
                    @endif

                    <ul class="mt-7 space-y-3 text-sm text-blue-50/85">
                        @foreach ($plan->effectiveLimits() as $limit)
                            <li class="flex items-start gap-3 leading-6">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cyan-400/15 text-cyan-200">
                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                </span>
                                <span>
                                    <strong class="font-medium text-white">{{ !empty($limit['unlimited']) ? 'Unlimited' : number_format($limit['amount']) }}</strong>
                                    {{ $limit['label'] }} {{ $limit['unit'] ?? '' }}
                                </span>
                            </li>
                        @endforeach

                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-start gap-3 leading-6">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cyan-400/15 text-cyan-200">
                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                </span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        @if ($product->tutorials)
        <section class="scroll-mt-44 py-13 sm:py-20 [&+section]:pt-6">
            <p class="flex items-center gap-2.5 text-[10px] leading-[1.6] font-semibold tracking-[0.18em] text-cyan-300 uppercase">See it in action</p>
            <h2 class="mt-4 text-3xl font-semibold tracking-tight">A little guidance goes a long way.</h2>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($product->tutorials as $tutorial)
                @if (\App\Services\Products\ProductPricing::safeUrl($tutorial['url']))
                <a
                    href="{{ $tutorial['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-4 rounded-xl border border-white/10 bg-white/5 p-5
                        backdrop-blur-2xl transition hover:border-cyan-300/30 hover:bg-white/8">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-blue-200/20 text-cyan-200" aria-hidden="true">
                        <span class="material-symbols-outlined text-xl">play_arrow</span>
                    </span>
                    <span class="flex-1 text-sm font-medium">{{ $tutorial['title'] }}</span>
                    <span class="material-symbols-outlined text-base text-cyan-200" aria-hidden="true">north_east</span>
                </a>
                @endif
                @endforeach
            </div>
        </section>
        @endif

        {{-- One clear closing action helps visitors who need a conversation before choosing. --}}
        <section class="
                        flex flex-col items-start justify-between gap-8 overflow-hidden rounded-3xl border border-white/10
                        bg-white/5 p-7 shadow-2xl shadow-blue-950/20 backdrop-blur-2xl
                        sm:flex-row sm:items-center sm:p-[46px]
                    ">
            <div class="relative">
                <p class="flex items-center gap-2.5 text-[10px] leading-[1.6] font-semibold tracking-[0.18em] text-cyan-300 uppercase">Good software starts with the right fit</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Let's find yours.</h2>
                <p class="mt-4 max-w-lg text-sm leading-7 text-blue-100/55">
                    Have a question about {{ $product->name }}? Talk it through with the Techwave team.
                </p>
            </div>
            <a href="{{ route('client.contact') }}" wire:navigate class="
                        inline-flex min-h-[50px] items-center justify-center gap-5 rounded-[10px] border border-blue-300/25
                        bg-linear-[110deg,#2879ed,#06a8dc] px-5.5 py-3.5 text-[13px] font-semibold text-white
                        shadow-[0_8px_28px_rgb(14_114_223/0.18)] transition duration-180 hover:-translate-y-0.5
                        hover:shadow-[0_12px_30px_rgb(14_114_223/0.3)] motion-reduce:transition-none relative shrink-0
                    ">
                Let's talk
                <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span>
            </a>
        </section>

        @if ($related->isNotEmpty())
        <section class="mt-16">
            <div class="mb-6 flex items-center justify-between gap-4">
                <h2 class="text-lg font-medium">Explore more from Techwave</h2>
                <a href="{{ route('client.products.index') }}" wire:navigate class="text-xs text-cyan-200">
                    All products →
                </a>
            </div>

            <div class="grid gap-6 border-t border-white/10 pt-6 md:grid-cols-3">
                @foreach ($related as $other)
                <a
                    href="{{ route('client.products.show', $other->slug) }}"
                    wire:navigate
                    class="group flex items-start gap-4">
                    @if ($other->cover_path)
                    <img
                        src="{{ \App\Services\UploadStorage::url($other->cover_path) }}"
                        alt=""
                        loading="lazy"
                        class="h-14 w-14 shrink-0 rounded-lg border border-white/10 object-cover">
                    @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-cyan-300">
                        <span class="material-symbols-outlined" aria-hidden="true">deployed_code</span>
                    </span>
                    @endif
                    <span class="min-w-0">
                        <span class="block text-sm font-medium transition group-hover:text-cyan-200">{{ $other->name }}</span>
                        <span class="mt-2 line-clamp-2 block text-xs leading-6 text-blue-100/40">{{ $other->summary }}</span>
                    </span>
                </a>
                @endforeach
            </div>
        </section>
        @endif
    </div>
</div>
