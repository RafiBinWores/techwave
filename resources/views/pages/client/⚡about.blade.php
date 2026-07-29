<?php

use App\Models\AboutPageSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('About Us | Techwave')] class extends Component
{
    public array $page = [];

    public function mount(): void
    {
        $this->page = AboutPageSetting::resolved();
    }

    public function imageUrl(?string $value, string $fallback = ''): string
    {
        if (blank($value)) {
            return $fallback;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
};
?>

<div class="relative text-white">
    <!-- Hero -->
    @if ($page['hero']['enabled'] ?? true)
    <section class="relative overflow-hidden py-20 sm:py-24 lg:py-30">

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        {{ $page['hero']['badge'] ?? '' }}
                    </div>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-7xl">
                        {{ $page['hero']['title'] ?? '' }}
                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                            {{ $page['hero']['highlighted_title'] ?? '' }}
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                        {{ $page['hero']['description'] ?? '' }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        @if (filled($page['hero']['primary_button_text'] ?? null))
                        <a href="{{ $page['hero']['primary_button_url'] ?? '#' }}"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                            {{ $page['hero']['primary_button_text'] }}
                        </a>
                        @endif

                        @if (filled($page['hero']['secondary_button_text'] ?? null))
                        <a href="{{ $page['hero']['secondary_button_url'] ?? '#' }}"
                            class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-6 py-3.5 font-semibold text-white backdrop-blur-xl transition hover:bg-white/12">
                            {{ $page['hero']['secondary_button_text'] }}
                        </a>
                        @endif
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[24px] border border-white/15 bg-white/8 p-3 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl sm:rounded-[30px] sm:p-4 lg:rounded-[34px]">
                        <div class="pointer-events-none absolute left-6 top-6 h-20 w-20 rounded-full bg-cyan-400/12 blur-3xl sm:left-8 sm:top-8 sm:h-24 sm:w-24"></div>
                        <div class="pointer-events-none absolute bottom-6 right-6 h-24 w-24 rounded-full bg-blue-500/12 blur-3xl sm:bottom-8 sm:right-8 sm:h-32 sm:w-32"></div>

                        <div class="relative grid grid-cols-2 auto-rows-[142px] gap-3 sm:auto-rows-[180px] sm:gap-4 lg:auto-rows-[220px]">
                            <img
                                src="{{ $this->imageUrl($page['hero']['top_left_image'] ?? null, 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1000&q=80') }}"
                                class="h-full w-full rounded-[16px] object-cover sm:rounded-[20px] lg:rounded-[24px]"
                                alt="Technology strategy">

                            <img
                                src="{{ $this->imageUrl($page['hero']['top_right_image'] ?? null, 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1000&q=80') }}"
                                class="h-full w-full rounded-[16px] object-cover sm:rounded-[20px] lg:rounded-[24px]"
                                alt="Modern infrastructure">

                            <img
                                src="{{ $this->imageUrl($page['hero']['bottom_left_image'] ?? null, 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80') }}"
                                class="h-full w-full rounded-[16px] object-cover sm:rounded-[20px] lg:rounded-[24px]"
                                alt="Business execution">

                            <div
                                class="relative flex h-full min-w-0 flex-col justify-center overflow-hidden rounded-[16px] border border-white/10 bg-white/8 p-3 backdrop-blur-xl sm:rounded-[20px] sm:p-5 lg:rounded-[24px] lg:p-6">
                                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-cyan-400/10 blur-3xl"></div>

                                <div class="relative">
                                    <p class="text-[7px] font-semibold uppercase tracking-[0.14em] text-blue-100/45 sm:text-[10px] sm:tracking-[0.18em] lg:text-xs lg:tracking-[0.22em]">
                                        {{ $page['hero']['info_eyebrow'] ?? '' }}
                                    </p>

                                    <h3 class="mt-1.5 text-[11px] font-bold leading-[1.25] text-white sm:mt-2 sm:text-lg sm:leading-tight lg:mt-4 lg:text-2xl">
                                        {{ $page['hero']['info_title'] ?? '' }}
                                    </h3>

                                    <p class="mt-1.5 text-[8px] leading-3.5 text-blue-100/60 sm:mt-2 sm:text-xs sm:leading-5 lg:mt-3 lg:text-sm lg:leading-7">
                                        {{ Str::limit($page['hero']['info_description'] ?? '', 120) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-8 grid grid-cols-2 gap-3 sm:mt-10 sm:gap-4 xl:grid-cols-4">
                @foreach ($page['stats'] ?? [] as $stat)
                <div class="about-stat-card !p-4 sm:!p-6">
                    <p class="text-2xl font-bold leading-none text-white sm:text-3xl lg:text-4xl">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-[10px] leading-4 text-blue-100/60 sm:text-sm sm:leading-5">{{ $stat['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Who We Are -->
    @if ($page['who_we_are']['enabled'] ?? true)
    <section id="who-we-are" class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="about-panel">
                <div class="grid gap-10 lg:grid-cols-[1fr_420px] items-center">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs text-blue-100/80 backdrop-blur-xl">
                            <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                            {{ $page['who_we_are']['badge'] ?? '' }}
                        </div>

                        <h2 class="mt-6 text-3xl font-bold sm:text-4xl lg:text-5xl">
                            {{ $page['who_we_are']['title'] ?? '' }}
                        </h2>

                        @foreach ($page['who_we_are']['paragraphs'] ?? [] as $i => $paragraph)
                        <p @class(['mt-5 about-text'=> $i === 0, 'mt-4 about-text' => $i > 0])>
                            {{ $paragraph }}
                        </p>
                        @endforeach
                    </div>

                    <div class="rounded-[30px] border border-white/10 bg-white/6 md:p-4 backdrop-blur-2xl">
                        <img src="{{ $this->imageUrl($page['who_we_are']['image_url'] ?? null, 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80') }}"
                            class="h-[300px] md:h-[340px] w-full rounded-[24px] object-cover" alt="Who we are">
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Mission & Vision -->
    @if ($page['mission_vision']['enabled'] ?? true)
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="about-mv-card group relative overflow-hidden">
                    <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl transition duration-500 group-hover:bg-cyan-400/16"></div>
                    <div class="pointer-events-none absolute bottom-0 left-0 h-28 w-40 bg-linear-to-tr from-cyan-500/6 to-transparent"></div>

                    <div class="relative">
                        <div class="flex items-start gap-5">
                            <div class="grid size-14 shrink-0 place-items-center rounded-2xl border border-cyan-300/20 bg-cyan-400/12 text-cyan-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_12px_30px_rgba(34,211,238,0.08)]">
                                <span class="material-symbols-outlined block text-[28px] leading-none">
                                    {{ $page['mission_vision']['mission']['icon'] ?? 'home' }}
                                </span>
                            </div>

                            <div class="min-w-0 pt-0.5">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-200/55">Our purpose</p>
                                <h3 class="mt-1.5 text-2xl font-bold leading-tight text-white">
                                    {{ $page['mission_vision']['mission']['title'] ?? '' }}
                                </h3>
                            </div>
                        </div>

                        <p class="mt-6 text-sm leading-7 text-blue-100/70 sm:text-base">
                            {{ $page['mission_vision']['mission']['description'] ?? '' }}
                        </p>
                    </div>
                </article>

                <article class="about-mv-card group relative overflow-hidden">
                    <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-blue-500/10 blur-3xl transition duration-500 group-hover:bg-blue-500/16"></div>
                    <div class="pointer-events-none absolute bottom-0 left-0 h-28 w-40 bg-linear-to-tr from-blue-500/6 to-transparent"></div>

                    <div class="relative">
                        <div class="flex items-start gap-5">
                            <div class="grid size-14 shrink-0 place-items-center rounded-2xl border border-blue-300/20 bg-blue-500/12 text-blue-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_12px_30px_rgba(59,130,246,0.08)]">
                                <span class="material-symbols-outlined block text-[28px] leading-none">
                                    {{ $page['mission_vision']['vision']['icon'] ?? 'visibility' }}
                                </span>
                            </div>

                            <div class="min-w-0 pt-0.5">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-200/55">Our direction</p>
                                <h3 class="mt-1.5 text-2xl font-bold leading-tight text-white">
                                    {{ $page['mission_vision']['vision']['title'] ?? '' }}
                                </h3>
                            </div>
                        </div>

                        <p class="mt-6 text-sm leading-7 text-blue-100/70 sm:text-base">
                            {{ $page['mission_vision']['vision']['description'] ?? '' }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>
    @endif

    <!-- Why Choose Us -->
    @if ($page['why_choose_us']['enabled'] ?? true)
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    {{ $page['why_choose_us']['section_title'] ?? '' }}
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $page['why_choose_us']['highlighted_title'] ?? '' }}
                    </span>
                </h2>
                @if (filled($page['why_choose_us']['subtitle'] ?? null))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">
                    {{ $page['why_choose_us']['subtitle'] }}
                </p>
                @endif
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($page['why_choose_us']['items'] ?? [] as $i => $item)
                <div @class(['why-upgrade-card'=> true, 'why-upgrade-card-featured' => $i === 1])>
                    <h3 class="why-upgrade-title">{{ $item['title'] }}</h3>
                    <p class="why-upgrade-text">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Expertise Bento Grid -->
    @if ($page['expertise']['enabled'] ?? true)
    @php
    $expertiseLayout = [
    'md:col-span-2 xl:col-span-7 xl:row-span-2',
    'xl:col-span-5',
    'xl:col-span-5',
    'md:col-span-2 xl:col-span-12',
    ];
    $expertiseIcons = ['support_agent', 'shield_lock', 'code', 'cloud'];
    @endphp
    <section class="relative overflow-hidden pb-18 sm:pb-22">

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-3xl text-center">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    {{ $page['expertise']['section_title'] ?? '' }}
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $page['expertise']['highlighted_title'] ?? '' }}
                    </span>
                </h2>

                @if (filled($page['expertise']['subtitle'] ?? null))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">
                    {{ $page['expertise']['subtitle'] }}
                </p>
                @endif
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-12 xl:auto-rows-[300px]">
                @foreach ($page['expertise']['items'] ?? [] as $index => $item)
                <article class="group relative overflow-hidden rounded-[30px] border border-white/10 bg-slate-950/45 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.32)] backdrop-blur-2xl transition duration-500 hover:-translate-y-1.5 hover:border-cyan-300/30 hover:bg-slate-950/55 sm:p-8 {{ $expertiseLayout[$index] ?? 'xl:col-span-4' }}">
                    <div class="absolute inset-0 bg-linear-to-br from-white/9 via-white/[0.025] to-transparent"></div>
                    <div class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-cyan-400/14 blur-3xl transition duration-500 group-hover:scale-110 group-hover:bg-cyan-400/22"></div>
                    <div class="absolute -left-10 bottom-8 h-40 w-40 rounded-full bg-blue-500/10 blur-3xl transition duration-500 group-hover:bg-blue-500/16"></div>
                    <div class="absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgba(255,255,255,0.7)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.7)_1px,transparent_1px)] [background-size:30px_30px] [mask-image:radial-gradient(circle_at_top_right,black,transparent_72%)]"></div>

                    <div class="relative flex h-full flex-col">
                        <div class="flex items-start justify-between gap-5">
                            <div class="flex items-center gap-4">
                                <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/12 text-cyan-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12)]">
                                    <span class="material-symbols-outlined text-[28px]">
                                        {{ $expertiseIcons[$index] ?? 'settings_suggest' }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/65">
                                        {{ ['Managed Services', 'Protection Layer', 'Digital Experience', 'Cloud Infrastructure'][$index] ?? 'Technology Service' }}
                                    </p>
                                    <h3 @class([ 'mt-1 font-bold text-white' , 'text-2xl sm:text-3xl'=> $index === 0,
                                        'text-xl sm:text-2xl' => $index !== 0,
                                        ])>
                                        {{ $item['title'] ?? '' }}
                                    </h3>
                                </div>
                            </div>

                            <span class="rounded-full border border-white/10 bg-white/6 px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-blue-100/55">
                                {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>

                        <p class="mt-5 max-w-2xl text-sm leading-7 text-blue-100/65 sm:text-base">
                            {{ $item['description'] ?? '' }}
                        </p>

                        @if ($index === 0)
                        <div class="mt-7 flex flex-1 flex-col rounded-[24px] border border-white/10 bg-black/20 p-5 shadow-inner shadow-black/20 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-100/45">Operations overview</p>
                                    <p class="mt-1 text-sm font-semibold text-white sm:text-base">Managed technology environment</p>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-cyan-300/15 bg-cyan-400/10 px-3 py-1 text-[10px] font-semibold text-cyan-100">
                                    <span class="material-symbols-outlined text-[14px]">settings_suggest</span>
                                    Managed systems
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                @foreach ([
                                ['monitor_heart', 'System monitoring', 'Visibility across essential systems'],
                                ['inventory_2', 'Asset management', 'Organized devices and software'],
                                ['system_update_alt', 'Updates & maintenance', 'Planned upkeep and improvements'],
                                ] as [$icon, $label, $value])
                                <div class="rounded-2xl border border-white/8 bg-white/[0.035] p-4">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-300/15 bg-cyan-400/10 text-cyan-200">
                                        <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                                    </span>
                                    <p class="mt-4 text-sm font-semibold text-white/90">{{ $label }}</p>
                                    <p class="mt-1 text-xs leading-5 text-blue-100/45">{{ $value }}</p>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                @foreach (['Endpoint management', 'Network care', 'Patch planning', 'Technology maintenance'] as $tag)
                                <span class="rounded-full border border-white/10 bg-white/[0.035] px-3 py-1.5 text-[10px] font-medium text-blue-100/55">
                                    {{ $tag }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @elseif ($index === 1)
                        <div class="mt-6 grid flex-1 grid-cols-[1fr_118px] items-center gap-5">
                            <div class="space-y-2.5">
                                @foreach (['Threat prevention', 'Access protection', 'Operational resilience'] as $feature)
                                <div class="flex items-center gap-2.5 text-xs text-blue-100/65">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/10 text-cyan-200">
                                        <span class="material-symbols-outlined text-[13px]">check</span>
                                    </span>
                                    {{ $feature }}
                                </div>
                                @endforeach
                            </div>

                            <div class="relative flex aspect-square w-full items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/8 shadow-[inset_0_0_35px_rgba(34,211,238,0.08)]">
                                <div class="absolute inset-2 rounded-full border border-dashed border-cyan-200/20 animate-[spin_14s_linear_infinite]"></div>
                                <div class="absolute inset-5 rounded-full border border-white/10 bg-slate-950/55"></div>
                                <div class="relative text-center">
                                    <span class="material-symbols-outlined text-[30px] text-cyan-200">verified_user</span>
                                    <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-100/50">Protected</p>
                                </div>
                            </div>
                        </div>
                        @elseif ($index === 2)
                        <div class="mt-auto pt-5">
                            <div class="overflow-hidden rounded-[20px] border border-white/10 bg-black/25 shadow-inner shadow-black/20">
                                <div class="flex items-center gap-1.5 border-b border-white/8 px-4 py-3">
                                    <span class="h-2 w-2 rounded-full bg-red-300/70"></span>
                                    <span class="h-2 w-2 rounded-full bg-amber-300/70"></span>
                                    <span class="h-2 w-2 rounded-full bg-emerald-300/70"></span>
                                    <div class="ml-3 h-2 flex-1 rounded-full bg-white/7"></div>
                                </div>
                                <div class="grid grid-cols-[88px_1fr] gap-4 p-4">
                                    <div class="rounded-xl bg-linear-to-br from-cyan-400/14 to-blue-500/8"></div>
                                    <div class="space-y-2.5 py-1">
                                        <div class="h-2.5 w-2/3 rounded-full bg-white/15"></div>
                                        <div class="h-2 w-full rounded-full bg-white/8"></div>
                                        <div class="h-2 w-4/5 rounded-full bg-white/8"></div>
                                        <div class="flex gap-2 pt-1">
                                            <div class="h-6 w-16 rounded-full bg-cyan-400/20"></div>
                                            <div class="h-6 w-12 rounded-full border border-white/10"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (['Responsive', 'Fast', 'Scalable'] as $tag)
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-medium text-blue-100/55">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                        @elseif ($index === 3)
                        <div class="mt-auto grid gap-5 pt-6 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200/60">Connected operations</p>
                                <p class="mt-2 max-w-md text-sm leading-6 text-blue-100/55">
                                    One flexible foundation for communication, storage, hosting, and future growth.
                                </p>
                            </div>

                            <div class="relative grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="absolute left-[12%] right-[12%] top-1/2 hidden h-px -translate-y-1/2 sm:block"></div>
                                @foreach ([
                                ['dns', 'Hosting'],
                                ['mail', 'Business Email'],
                                ['cloud_sync', 'Backup'],
                                ['trending_up', 'Scaling'],
                                ] as [$icon, $label])
                                <div class="relative rounded-[20px] border border-white/10 bg-slate-950/65 p-4 text-center shadow-[0_12px_28px_rgba(0,0,0,0.22)]">
                                    <span class="material-symbols-outlined text-[24px] text-cyan-200">{{ $icon }}</span>
                                    <p class="mt-2 text-xs font-semibold text-white">{{ $label }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Timeline -->
    @if ($page['timeline']['enabled'] ?? true)
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    {{ $page['timeline']['section_title'] ?? '' }}
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $page['timeline']['highlighted_title'] ?? '' }}
                    </span>
                </h2>
                @if (filled($page['timeline']['subtitle'] ?? null))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">
                    {{ $page['timeline']['subtitle'] }}
                </p>
                @endif
            </div>

            <div class="relative">
                <div class="absolute left-5 top-0 h-full w-px bg-gradient-to-b from-cyan-300/0 via-cyan-300/25 to-cyan-300/0 sm:left-1/2 sm:-translate-x-1/2"></div>

                <div class="space-y-6">
                    @foreach ($page['timeline']['items'] ?? [] as $i => $item)
                    <div @class(['timeline-card sm:mr-auto sm:max-w-[48%]'=> $i % 2 === 0, 'timeline-card sm:ml-auto sm:max-w-[48%]' => $i % 2 !== 0])>
                        <div class="timeline-dot"></div>
                        <p class="timeline-year">{{ $item['year'] }}</p>
                        <h3 class="timeline-title">{{ $item['title'] }}</h3>
                        <p class="timeline-text">{{ $item['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Leadership Message -->
    @if ($page['leadership']['enabled'] ?? true)
    <section class="pb-18 sm:pb-22">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="about-panel">
                <div class="grid gap-10 lg:grid-cols-[300px_1fr] items-center">
                    <div class="rounded-[30px] border border-white/10 bg-white/6 md:p-4 backdrop-blur-2xl">
                        <img src="{{ $this->imageUrl($page['leadership']['image_url'] ?? null, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80') }}"
                            class="h-[300px] md:h-[340px] w-full rounded-[24px] object-cover" alt="CEO">
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">{{ $page['leadership']['badge'] ?? '' }}</p>
                        <h2 class="mt-4 text-3xl font-bold sm:text-4xl">{{ $page['leadership']['title'] ?? '' }}</h2>

                        @foreach ($page['leadership']['paragraphs'] ?? [] as $i => $paragraph)
                        <p @class(['mt-5 about-text'=> $i === 0, 'mt-4 about-text' => $i > 0])>
                            {{ $paragraph }}
                        </p>
                        @endforeach

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-white">{{ $page['leadership']['name'] ?? '' }}</h3>
                            <p class="mt-1 text-sm text-blue-100/55">{{ $page['leadership']['role'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

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

    <!-- Experts Slider -->
    @if ($page['experts']['enabled'] ?? true)
    @php $expertItems = array_values($page['experts']['items'] ?? []); @endphp
    <section class="relative overflow-hidden pb-18 sm:pb-22">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-1/2 top-1/3 h-72 w-72 -translate-x-1/2 rounded-full bg-blue-500/8 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-3xl text-center">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                    {{ $page['experts']['section_title'] ?? '' }}
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        {{ $page['experts']['highlighted_title'] ?? '' }}
                    </span>
                </h2>

                @if (filled($page['experts']['subtitle'] ?? null))
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">
                    {{ $page['experts']['subtitle'] }}
                </p>
                @endif
            </div>

            @if (count($expertItems))
            <div
                x-data="{
                        current: 0,
                        perView: 1,
                        total: {{ count($expertItems) }},
                        timer: null,
                        isDragging: false,
                        dragStartX: 0,
                        dragOffset: 0,
                        get max() { return Math.max(0, this.total - this.perView) },
                        updatePerView() {
                            this.perView = window.innerWidth >= 1280 ? 4 : (window.innerWidth >= 640 ? 2 : 1);
                            this.current = Math.min(this.current, this.max);
                        },
                        next() { this.current = this.current >= this.max ? 0 : this.current + 1 },
                        previous() { this.current = this.current <= 0 ? this.max : this.current - 1 },
                        goTo(index) { this.current = Math.max(0, Math.min(index, this.max)); },
                        start() {
                            if (this.total <= 1) return;
                            this.stop();
                            this.timer = setInterval(() => this.next(), 5000);
                        },
                        stop() {
                            if (this.timer) clearInterval(this.timer);
                            this.timer = null;
                        },
                        dragStart(event) {
                            if (this.total <= this.perView) return;
                            this.isDragging = true;
                            this.dragStartX = event.clientX;
                            this.dragOffset = 0;
                            this.stop();
                        },
                        dragMove(event) {
                            if (!this.isDragging) return;
                            this.dragOffset = event.clientX - this.dragStartX;
                        },
                        dragEnd() {
                            if (!this.isDragging) return;
                            if (this.dragOffset <= -60) {
                                this.next();
                            } else if (this.dragOffset >= 60) {
                                this.previous();
                            }
                            this.isDragging = false;
                            this.dragOffset = 0;
                            this.start();
                        }
                    }"
                x-init="updatePerView(); start(); window.addEventListener('resize', () => updatePerView())"
                @mouseenter="stop()"
                @mouseleave="start()"
                class="relative">
                <!-- <div class="mb-4 flex items-center justify-between gap-3 px-2">
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-blue-100/45">Grab and slide to explore our experts</p>
                    <div class="hidden items-center gap-2 text-[11px] text-blue-100/45 sm:flex">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5">
                            <span class="material-symbols-outlined text-[18px]">drag_pan</span>
                        </span>
                        Drag to slide
                    </div>
                </div> -->

                <div class="overflow-hidden px-1 select-none cursor-grab active:cursor-grabbing [touch-action:pan-y]"
                    @pointerdown="dragStart($event)"
                    @pointermove="dragMove($event)"
                    @pointerup="dragEnd()"
                    @pointerleave="dragEnd()"
                    @pointercancel="dragEnd()">
                    <div
                        :class="isDragging ? 'duration-0' : 'duration-500'"
                        class="flex ease-out"
                        :style="'transform: translateX(calc(-' + (current * (100 / perView)) + '% + ' + dragOffset + 'px))'">
                        @foreach ($expertItems as $member)
                        <div class="shrink-0 px-2.5" :style="'width:' + (100 / perView) + '%'">
                            <article class="team-card h-full">
                                <img
                                    src="{{ $this->imageUrl($member['image_url'] ?? null, 'https://placehold.co/800x900/0f172a/94a3b8?text=Expert') }}"
                                    class="team-img pointer-events-none"
                                    alt="{{ $member['name'] ?? 'Team member' }}">
                                <h3 class="team-name">{{ $member['name'] ?? '' }}</h3>
                                <p class="team-role">{{ $member['role'] ?? '' }}</p>
                            </article>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if (count($expertItems) > 1)
                <div class="mt-7 flex justify-center gap-2">
                    @foreach ($expertItems as $index => $member)
                    <button
                        type="button"
                        x-show="{{ $index }} <= max"
                        @click="goTo({{ $index }})"
                        :class="current === {{ $index }} ? 'w-7 bg-cyan-300' : 'w-2 bg-white/20 hover:bg-white/35'"
                        class="h-2 rounded-full transition-all duration-300"
                        aria-label="Go to expert slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>
    </section>
    @endif
</div>