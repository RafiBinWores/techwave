<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Software Products | Techwave')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'products' => Product::published()
                ->with('publishedPlans')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(12),
        ];
    }
};
?>

<div class="mx-auto max-w-350 px-4 pb-24 pt-8 text-white sm:px-6 lg:px-8">
    {{-- Catalog introduction, using the shared client-site color theme. --}}
    <header
        class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-950/45 px-6 py-14 text-center sm:px-12 sm:py-20"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-blue-500/20 blur-3xl"
        >
        </div>
        <span
            class="relative inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/5 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-cyan-200"
        >
            <span class="material-symbols-outlined text-base">
                deployed_code
            </span>
            Software by Techwave
        </span>
        <h1 class="relative mt-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
            Find your next
            <span class="bg-linear-to-r from-blue-300 to-cyan-300 bg-clip-text text-transparent">
                software solution.
            </span>
        </h1>
        <p class="relative mx-auto mt-5 max-w-xl text-base leading-relaxed text-blue-100/60">
            Explore our products, take a closer look at their features, and get started with the tools that fit your work.
        </p>
    </header>
    {{-- Published software cards, with optional images and starting prices. --}}
    <section aria-label="Software products" class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            @php($summary = \App\Services\Products\ProductPricing::summary($product))
            <article
                class="group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-slate-950/55 backdrop-blur-sm transition duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-xl hover:shadow-blue-950/30"
            >
                <a
                    href="{{ route('client.products.show', $product->slug) }}"
                    wire:navigate
                    tabindex="-1"
                    aria-hidden="true"
                    class="relative flex aspect-video items-center justify-center overflow-hidden border-b border-white/8 bg-linear-to-br from-blue-900/25 to-slate-950"
                >
                    @if ($product->cover_path)
                        <img
                            src="{{ \App\Services\UploadStorage::url($product->cover_path) }}"
                            alt=""
                            loading="lazy"
                            class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-105"
                        >
                    @else
                        <span class="material-symbols-outlined text-7xl text-cyan-300/50">
                            deployed_code
                        </span>
                    @endif
                </a>
                <div class="flex flex-1 flex-col p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-cyan-300/70">
                        Techwave software
                    </p>
                    <h2 class="mt-3 text-xl font-semibold">
                        <a
                            href="{{ route('client.products.show', $product->slug) }}"
                            wire:navigate
                            class="transition hover:text-cyan-200"
                        >
                            {{ $product->name }}
                        </a>
                    </h2>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-blue-100/55">
                        {{ $product->summary }}
                    </p>
                    <div class="mt-6 flex items-end justify-between gap-3 border-t border-white/8 pt-5">
                        @if ($summary && $summary['price'])
                            <p class="text-xs text-blue-100/45">
                                From
                                <span class="block text-lg font-semibold text-white">
                                    ৳{{ number_format((float) $summary['price'], 2) }}
                                    <span class="text-xs font-normal text-blue-100/50">
                                        / {{ $summary['period'] }}
                                    </span>
                                </span>
                            </p>
                        @else
                            <span class="text-xs text-blue-100/40">
                                Explore the product
                            </span>
                        @endif
                        <a
                            href="{{ route('client.products.show', $product->slug) }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 text-sm font-semibold text-cyan-300 transition hover:text-white"
                        >
                            View details
                            <span class="material-symbols-outlined text-lg">
                                arrow_forward
                            </span>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div
                class="col-span-full rounded-2xl border border-dashed border-white/15 bg-white/5 px-6 py-20 text-center"
            >
                <span class="material-symbols-outlined text-5xl text-cyan-300/50">
                    deployed_code
                </span>
                <h2 class="mt-5 text-xl font-semibold">
                    New software is on the way
                </h2>
                <p class="mt-3 text-sm text-blue-100/50">
                    Check back soon to explore our products.
                </p>
                <a
                    href="{{ route('client.contact') }}"
                    wire:navigate
                    class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-cyan-300"
                >
                    Talk to Techwave
                    <span class="material-symbols-outlined text-base">
                        arrow_forward
                    </span>
                </a>
            </div>
        @endforelse
    </section>
    @if ($products->hasPages())
        <div class="mt-8 rounded-2xl bg-white p-4">
            {{ $products->links() }}
        </div>
    @endif
</div>
