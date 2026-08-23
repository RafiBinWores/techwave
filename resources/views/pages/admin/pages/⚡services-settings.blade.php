<?php

use App\Models\ServicePageSetting;
use App\Support\ServicePageLayout;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Services Page Settings')] class extends Component {
    public ServicePageSetting $setting;

    public string $activeTab = 'layout';

    public array $tabs = [
        'layout' => ['label' => 'Layout', 'icon' => 'dashboard_customize'],
        'hero' => ['label' => 'Services Heading', 'icon' => 'view_carousel'],
        'process' => ['label' => 'Process', 'icon' => 'timeline'],
        'why_choose_us' => ['label' => 'Why Choose Us', 'icon' => 'thumb_up'],
        'cta' => ['label' => 'Contact CTA', 'icon' => 'support_agent'],
    ];

    public array $layout = [];

    public array $layoutOptions = ServicePageLayout::STYLES;

    public array $hero = [];

    public array $process = [];

    public array $why_choose_us = [];

    public array $cta = [];

    public function mount(): void
    {
        $this->setting = ServicePageSetting::current();
        $resolved = ServicePageSetting::resolved();

        $this->layout = $resolved['layout'];
        $this->hero = $resolved['hero'];
        $this->process = $resolved['process'];
        $this->why_choose_us = $resolved['why_choose_us'];
        $this->cta = $resolved['cta'];
    }

    public function setTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabs)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function addProcessStep(): void
    {
        if (count($this->process['steps'] ?? []) < 8) {
            $this->process['steps'][] = ['icon' => 'check_circle', 'title' => '', 'description' => ''];
        }
    }

    public function removeProcessStep(int $index): void
    {
        if (count($this->process['steps'] ?? []) <= 1) {
            return;
        }

        unset($this->process['steps'][$index]);
        $this->process['steps'] = array_values($this->process['steps']);
    }

    public function addWhyItem(): void
    {
        if (count($this->why_choose_us['items'] ?? []) < 8) {
            $this->why_choose_us['items'][] = ['icon' => 'check_circle', 'title' => '', 'description' => ''];
        }
    }

    public function removeWhyItem(int $index): void
    {
        if (count($this->why_choose_us['items'] ?? []) <= 1) {
            return;
        }

        unset($this->why_choose_us['items'][$index]);
        $this->why_choose_us['items'] = array_values($this->why_choose_us['items']);
    }

    protected function rules(): array
    {
        return [
            'layout.layout_style' => ['required', 'string', Rule::in(array_keys(ServicePageLayout::STYLES))],

            'hero.enabled' => ['required', 'boolean'],
            'hero.badge' => ['nullable', 'string', 'max:100'],
            'hero.title' => ['required', 'string', 'max:180'],
            'hero.highlighted_title' => ['nullable', 'string', 'max:180'],
            'hero.description' => ['required', 'string', 'max:1200'],

            'process.enabled' => ['required', 'boolean'],
            'process.badge' => ['nullable', 'string', 'max:100'],
            'process.title' => ['required', 'string', 'max:180'],
            'process.highlighted_title' => ['nullable', 'string', 'max:180'],
            'process.description' => ['required', 'string', 'max:1200'],
            'process.steps' => ['required', 'array', 'min:1', 'max:8'],
            'process.steps.*.icon' => ['nullable', 'string', 'max:100'],
            'process.steps.*.title' => ['required', 'string', 'max:120'],
            'process.steps.*.description' => ['required', 'string', 'max:600'],

            'why_choose_us.enabled' => ['required', 'boolean'],
            'why_choose_us.badge' => ['nullable', 'string', 'max:100'],
            'why_choose_us.title' => ['required', 'string', 'max:180'],
            'why_choose_us.highlighted_title' => ['nullable', 'string', 'max:180'],
            'why_choose_us.description' => ['required', 'string', 'max:1200'],
            'why_choose_us.items' => ['required', 'array', 'min:1', 'max:8'],
            'why_choose_us.items.*.icon' => ['nullable', 'string', 'max:100'],
            'why_choose_us.items.*.title' => ['required', 'string', 'max:120'],
            'why_choose_us.items.*.description' => ['required', 'string', 'max:500'],

            'cta.enabled' => ['required', 'boolean'],
            'cta.badge' => ['nullable', 'string', 'max:100'],
            'cta.title' => ['required', 'string', 'max:180'],
            'cta.highlighted_title' => ['nullable', 'string', 'max:180'],
            'cta.description' => ['required', 'string', 'max:1200'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $this->setting->update([
            'layout' => $this->layout,
            'hero' => $this->hero,
            'process' => $this->process,
            'why_choose_us' => $this->why_choose_us,
            'cta' => $this->cta,
        ]);

        $this->setting = $this->setting->fresh();

        $this->dispatch('toast', message: 'Services page settings updated successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-8">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Services Page Settings</h1>
            <p class="mt-1 text-body-md text-secondary">
                Manage the layout and every section of the public services page.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $tab)
                    <button type="button" wire:click="setTab('{{ $key }}')" @class([
                        'inline-flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                        'bg-primary text-white shadow-sm' => $activeTab === $key,
                        'text-slate-600 hover:bg-slate-50 hover:text-primary' => $activeTab !== $key,
                    ])>
                        <span class="material-symbols-outlined text-[20px]">{{ $tab['icon'] }}</span>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-12">
                <div class="space-y-6 xl:col-span-12">
                    {{-- Layout Tab --}}
                    @if ($activeTab === 'layout')
                        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                            <h3 class="flex items-center gap-2 text-h3 font-h2">
                                <span class="material-symbols-outlined text-primary">dashboard_customize</span>
                                Service Page Layout
                            </h3>
                            <p class="mt-2 text-body-sm text-secondary">
                                Select the layout used to display active services on the public services page.
                            </p>

                            <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                @foreach ($layoutOptions as $styleKey => $option)
                                    @php
                                        $selected = ($layout['layout_style'] ?? 'bento') === $styleKey;
                                    @endphp

                                    <label wire:key="layout-{{ $styleKey }}"
                                        class="flex cursor-pointer flex-col rounded-xl border p-4 transition {{ $selected ? 'border-primary bg-blue-50 ring-2 ring-primary/10' : 'border-slate-200 hover:border-primary/40' }}">
                                        <input type="radio" wire:model.live="layout.layout_style"
                                            value="{{ $styleKey }}" class="sr-only">

                                        <div class="flex-1 rounded-lg bg-slate-950 p-3">
                                            <div class="h-full min-h-24">
                                                @if ($styleKey === 'bento_featured')
                                                    <div class="grid h-full grid-cols-3 grid-rows-2 gap-1.5">
                                                        <span class="col-span-2 row-span-2 rounded-md border border-white/10 bg-linear-to-br from-violet-500 to-blue-500"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                    </div>
                                                @elseif ($styleKey === 'bento')
                                                    <div class="grid h-full grid-cols-3 grid-rows-2 gap-1.5">
                                                        <span class="col-span-2 row-span-2 rounded-md border border-white/10 bg-linear-to-br from-violet-500 to-blue-500"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="col-span-2 rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                    </div>
                                                @elseif ($styleKey === 'bento_wide')
                                                    <div class="grid h-full grid-cols-3 grid-rows-2 gap-1.5">
                                                        <span class="col-span-2 rounded-md border border-white/10 bg-linear-to-br from-violet-500 to-blue-500"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="col-span-2 rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                    </div>
                                                @elseif ($styleKey === 'bento_wide_minimal')
                                                    <div class="grid h-full grid-cols-3 grid-rows-2 gap-1.5">
                                                        <span class="col-span-2 rounded-md border border-white/20 bg-linear-to-br from-emerald-500 to-teal-600"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        <span class="col-span-2 rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                        <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                    </div>
                                                @elseif ($styleKey === 'list')
                                                    <div class="flex h-full flex-col justify-between gap-1.5">
                                                        @for ($i = 0; $i < 3; $i++)
                                                            <div class="flex flex-1 gap-1.5">
                                                                <span class="w-1/3 rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                                <span class="flex-1 rounded-md border border-white/10 bg-linear-to-br from-slate-600 to-slate-700"></span>
                                                            </div>
                                                        @endfor
                                                    </div>
                                                @elseif ($styleKey === 'cards_2')
                                                    <div class="grid h-full grid-cols-2 gap-1.5">
                                                        @for ($i = 0; $i < 2; $i++)
                                                            <div class="relative overflow-hidden rounded-md border border-white/10 bg-linear-to-br from-violet-500/50 to-blue-500/20">
                                                                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-slate-950/90 to-transparent"></div>
                                                                <div class="absolute bottom-1 left-1.5 h-1 w-3/4 rounded-full bg-white/60"></div>
                                                                <div class="absolute bottom-2.5 left-1.5 h-1 w-1/2 rounded-full bg-white/30"></div>
                                                            </div>
                                                        @endfor
                                                    </div>
                                                @else
                                                    <div class="grid h-full grid-cols-3 gap-1.5">
                                                        @for ($i = 0; $i < 3; $i++)
                                                            <span class="rounded-md border border-white/10 bg-linear-to-br from-slate-700 to-slate-800"></span>
                                                        @endfor
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-4 flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="font-label-md text-on-surface">{{ $option['label'] }}</h4>
                                                <p class="mt-2 text-xs leading-5 text-secondary">{{ $option['description'] }}</p>
                                            </div>

                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $selected ? 'bg-primary text-white' : 'bg-slate-100 text-slate-500' }}">
                                                <span class="material-symbols-outlined text-[20px]">{{ $option['icon'] }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @error('layout.layout_style')
                                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Hero Tab --}}
                    @if ($activeTab === 'hero')
                        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                            <div class="mb-8 flex items-start justify-between gap-5">
                                <div>
                                    <h3 class="flex items-center gap-2 text-h3 font-h2">
                                        <span class="material-symbols-outlined text-primary">view_carousel</span>
                                        Services Heading
                                    </h3>
                                    <p class="mt-2 text-body-sm text-secondary">Manage the heading shown above the services grid.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="hero.enabled" class="peer sr-only">
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                                    </div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Badge Text</label>
                                    <input type="text" wire:model.live.debounce.300ms="hero.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Main Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="hero.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    @error('hero.title')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="hero.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Description</label>
                                    <textarea wire:model.live.debounce.300ms="hero.description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                    @error('hero.description')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Process Tab --}}
                    @if ($activeTab === 'process')
                        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                            <div class="mb-8 flex items-start justify-between gap-5">
                                <div>
                                    <h3 class="flex items-center gap-2 text-h3 font-h2">
                                        <span class="material-symbols-outlined text-primary">timeline</span>
                                        Process
                                    </h3>
                                    <p class="mt-2 text-body-sm text-secondary">Manage the "How We Work" process section.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="process.enabled" class="peer sr-only">
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                                    </div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Badge Text</label>
                                    <input type="text" wire:model.live.debounce.300ms="process.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Main Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="process.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    @error('process.title')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="process.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Description</label>
                                    <textarea wire:model.live.debounce.300ms="process.description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                    @error('process.description')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-8">
                                <div class="flex items-center justify-between">
                                    <label class="block font-label-md text-on-surface">Process Steps</label>
                                    <button type="button" wire:click="addProcessStep"
                                        class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Step</button>
                                </div>

                                <div class="mt-4 space-y-4">
                                    @foreach ($process['steps'] as $index => $step)
                                        <div wire:key="process-step-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                                            <div class="mb-4 flex items-center justify-between">
                                                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                                                    Step {{ $index + 1 }}
                                                </span>

                                                @if (count($process['steps']) > 1)
                                                    <button type="button" wire:click="removeProcessStep({{ $index }})"
                                                        class="text-slate-400 transition hover:text-red-500 cursor-pointer">
                                                        <span class="material-symbols-outlined">delete</span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block font-label-md text-secondary">Material icon name</label>
                                                    <div class="flex gap-3">
                                                        <div
                                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                                            <span class="material-symbols-outlined text-[24px]">
                                                                {{ $step['icon'] ?: 'timeline' }}
                                                            </span>
                                                        </div>

                                                        <input type="text" wire:model.live.debounce.300ms="process.steps.{{ $index }}.icon" class="flex-1 rounded border border-outline-variant bg-white px-4 py-2.5" placeholder="e.g. timeline, verified">
                                                    </div>
                                                </div>
                                                <div class="space-y-2">
                                                    <label class="block font-label-md text-secondary">Title</label>
                                                    <input type="text" wire:model.live.debounce.300ms="process.steps.{{ $index }}.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                                    @error("process.steps.{$index}.title")
                                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div class="space-y-2 md:col-span-2">
                                                    <label class="block font-label-md text-secondary">Description</label>
                                                    <textarea wire:model.live.debounce.300ms="process.steps.{{ $index }}.description" rows="2" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5"></textarea>
                                                    @error("process.steps.{$index}.description")
                                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Why Choose Us Tab --}}
                    @if ($activeTab === 'why_choose_us')
                        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                            <div class="mb-8 flex items-start justify-between gap-5">
                                <div>
                                    <h3 class="flex items-center gap-2 text-h3 font-h2">
                                        <span class="material-symbols-outlined text-primary">thumb_up</span>
                                        Why Choose Us
                                    </h3>
                                    <p class="mt-2 text-body-sm text-secondary">Manage the reasons clients choose your company.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="why_choose_us.enabled" class="peer sr-only">
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                                    </div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Badge Text</label>
                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Main Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    @error('why_choose_us.title')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Description</label>
                                    <textarea wire:model.live.debounce.300ms="why_choose_us.description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                    @error('why_choose_us.description')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-8">
                                <div class="flex items-center justify-between">
                                    <label class="block font-label-md text-on-surface">Reason Cards</label>
                                    <button type="button" wire:click="addWhyItem"
                                        class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Item</button>
                                </div>

                                <div class="mt-4 space-y-4">
                                    @foreach ($why_choose_us['items'] as $index => $item)
                                        <div wire:key="why-item-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                                            <div class="mb-4 flex items-center justify-between">
                                                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                                                    Item {{ $index + 1 }}
                                                </span>

                                                @if (count($why_choose_us['items']) > 1)
                                                    <button type="button" wire:click="removeWhyItem({{ $index }})"
                                                        class="text-slate-400 transition hover:text-red-500 cursor-pointer">
                                                        <span class="material-symbols-outlined">delete</span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block font-label-md text-secondary">Material icon name</label>
                                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.items.{{ $index }}.icon" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5" placeholder="e.g. flag, shield_lock">
                                                </div>
                                                <div class="space-y-2">
                                                    <label class="block font-label-md text-secondary">Title</label>
                                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.items.{{ $index }}.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                                    @error("why_choose_us.items.{$index}.title")
                                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div class="space-y-2 md:col-span-2">
                                                    <label class="block font-label-md text-secondary">Description</label>
                                                    <textarea wire:model.live.debounce.300ms="why_choose_us.items.{{ $index }}.description" rows="2" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5"></textarea>
                                                    @error("why_choose_us.items.{$index}.description")
                                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Contact CTA Tab --}}
                    @if ($activeTab === 'cta')
                        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                            <div class="mb-8 flex items-start justify-between gap-5">
                                <div>
                                    <h3 class="flex items-center gap-2 text-h3 font-h2">
                                        <span class="material-symbols-outlined text-primary">support_agent</span>
                                        Contact CTA
                                    </h3>
                                    <p class="mt-2 text-body-sm text-secondary">Manage the contact section heading and copy. Contact info comes from Site Settings.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="cta.enabled" class="peer sr-only">
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                                    </div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Badge Text</label>
                                    <input type="text" wire:model.live.debounce.300ms="cta.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Main Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="cta.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    @error('cta.title')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="cta.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Description</label>
                                    <textarea wire:model.live.debounce.300ms="cta.description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                    @error('cta.description')
                                        <p class="text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
