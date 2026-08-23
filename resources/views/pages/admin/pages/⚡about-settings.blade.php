<?php

use App\Models\AboutPageSetting;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('About Page Settings')] class extends Component {
    use WithFileUploads;

    public AboutPageSetting $setting;

    public string $activeTab = 'hero';

    public array $hero = [];
    public array $heroUploads = [];
    public array $stats = [];
    public array $who_we_are = [];
    public array $whoWeAreUploads = [];
    public array $mission_vision = [];
    public array $why_choose_us = [];
    public array $expertise = [];
    public array $timeline = [];
    public array $leadership = [];
    public array $leadershipUploads = [];
    public array $experts = [];
    public array $expertUploads = [];

    public array $tabs = [
        'hero' => ['label' => 'Hero Section', 'icon' => 'view_carousel'],
        'stats' => ['label' => 'Statistics', 'icon' => 'monitoring'],
        'who_we_are' => ['label' => 'Who We Are', 'icon' => 'groups'],
        'mission_vision' => ['label' => 'Mission & Vision', 'icon' => 'visibility'],
        'why_choose_us' => ['label' => 'Why Choose Us', 'icon' => 'thumb_up'],
        'expertise' => ['label' => 'Expertise', 'icon' => 'psychology'],
        'timeline' => ['label' => 'Timeline', 'icon' => 'timeline'],
        'leadership' => ['label' => 'Leadership', 'icon' => 'person'],
        'experts' => ['label' => 'Meet Our Experts', 'icon' => 'groups'],
    ];

    public function mount(): void
    {
        $this->setting = AboutPageSetting::current();
        $resolved = AboutPageSetting::resolved();

        $this->hero = $resolved['hero'];
        $this->stats = $resolved['stats'];
        $this->who_we_are = $resolved['who_we_are'];
        $this->mission_vision = $resolved['mission_vision'];
        $this->why_choose_us = $resolved['why_choose_us'];
        $this->expertise = $resolved['expertise'];
        $this->hydrateExpertiseEditor();
        $this->timeline = $resolved['timeline'];
        $this->leadership = $resolved['leadership'];
        $this->experts = $resolved['experts'];
    }

    private function hydrateExpertiseEditor(): void
    {
        $defaults = [
            [
                'icon' => 'support_agent',
                'eyebrow' => 'Managed Services',
                'number' => '01',
                'panel_title' => 'Operations overview',
                'panel_subtitle' => 'Managed technology environment',
                'panel_icon' => 'settings_suggest',
                'features' => [
                    ['icon' => 'monitor_heart', 'title' => 'System monitoring', 'description' => 'Visibility across essential systems'],
                    ['icon' => 'inventory_2', 'title' => 'Asset management', 'description' => 'Organized devices and software'],
                    ['icon' => 'system_update_alt', 'title' => 'Updates & maintenance', 'description' => 'Planned upkeep and improvements'],
                ],
                'tags' => ['Endpoint management', 'Network care', 'Patch planning', 'Technology maintenance'],
            ],
            [
                'icon' => 'shield_lock',
                'eyebrow' => 'Protection Layer',
                'number' => '02',
                'panel_title' => 'Security status',
                'panel_subtitle' => 'Protected',
                'panel_icon' => 'verified_user',
                'features' => [
                    ['icon' => 'check', 'title' => 'Threat prevention', 'description' => ''],
                    ['icon' => 'check', 'title' => 'Access protection', 'description' => ''],
                    ['icon' => 'check', 'title' => 'Operational resilience', 'description' => ''],
                ],
                'tags' => [],
            ],
            [
                'icon' => 'code',
                'eyebrow' => 'Digital Experience',
                'number' => '03',
                'panel_title' => 'Website preview',
                'panel_subtitle' => 'Modern digital interface',
                'panel_icon' => 'language',
                'features' => [
                    ['icon' => 'responsive_layout', 'title' => 'Responsive', 'description' => ''],
                    ['icon' => 'speed', 'title' => 'Fast', 'description' => ''],
                    ['icon' => 'trending_up', 'title' => 'Scalable', 'description' => ''],
                ],
                'tags' => ['Responsive', 'Fast', 'Scalable'],
            ],
            [
                'icon' => 'cloud',
                'eyebrow' => 'Cloud Infrastructure',
                'number' => '04',
                'panel_title' => 'Connected operations',
                'panel_subtitle' => 'One flexible foundation for communication, storage, hosting, and future growth.',
                'panel_icon' => 'hub',
                'features' => [
                    ['icon' => 'dns', 'title' => 'Hosting', 'description' => ''],
                    ['icon' => 'mail', 'title' => 'Business Email', 'description' => ''],
                    ['icon' => 'cloud_sync', 'title' => 'Backup', 'description' => ''],
                    ['icon' => 'trending_up', 'title' => 'Scaling', 'description' => ''],
                ],
                'tags' => [],
            ],
        ];

        $items = array_values($this->expertise['items'] ?? []);

        foreach ($defaults as $index => $default) {
            $saved = is_array($items[$index] ?? null) ? $items[$index] : [];
            $features = isset($saved['features']) && is_array($saved['features'])
                ? array_values($saved['features'])
                : $default['features'];
            $tags = isset($saved['tags']) && is_array($saved['tags'])
                ? array_values($saved['tags'])
                : $default['tags'];

            $items[$index] = array_replace($default, $saved);
            $items[$index]['features'] = $features ?: $default['features'];
            $items[$index]['tags'] = $tags;
        }

        $this->expertise['items'] = array_slice($items, 0, 4);
    }

    public function updated(string $property, mixed $value): void
    {
        if (str_starts_with($property, 'heroUploads.')) {
            $field = substr($property, strlen('heroUploads.'));
            $this->hero[$field] = '';
        }

        if (preg_match('/^hero\.(top_left_image|top_right_image|bottom_left_image)$/', $property, $matches) && filled($value)) {
            unset($this->heroUploads[$matches[1]]);
        }

        if ($property === 'whoWeAreUploads.image' && $value) {
            $this->who_we_are['image_url'] = '';
        }

        if ($property === 'who_we_are.image_url' && filled($value)) {
            unset($this->whoWeAreUploads['image']);
        }

        if ($property === 'leadershipUploads.image' && $value) {
            $this->leadership['image_url'] = '';
        }

        if ($property === 'leadership.image_url' && filled($value)) {
            unset($this->leadershipUploads['image']);
        }

        if (preg_match('/^expertUploads\.(\d+)$/', $property, $matches) && $value) {
            $index = (int) $matches[1];
            $this->experts['items'][$index]['image_url'] = '';
        }

        if (preg_match('/^experts\.items\.(\d+)\.image_url$/', $property, $matches) && filled($value)) {
            unset($this->expertUploads[(int) $matches[1]]);
        }
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

    public function previewImage(string $field, string $fallback): string
    {
        return $this->temporaryPreview(
            $this->heroUploads[$field] ?? null,
            $this->hero[$field] ?? null,
            $fallback,
        );
    }

    public function whoWeArePreviewImage(): string
    {
        return $this->temporaryPreview(
            $this->whoWeAreUploads['image'] ?? null,
            $this->who_we_are['image_url'] ?? null,
            'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80',
        );
    }

    public function leadershipPreviewImage(): string
    {
        return $this->temporaryPreview(
            $this->leadershipUploads['image'] ?? null,
            $this->leadership['image_url'] ?? null,
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
        );
    }

    public function expertPreviewImage(int $index): string
    {
        return $this->temporaryPreview(
            $this->expertUploads[$index] ?? null,
            $this->experts['items'][$index]['image_url'] ?? null,
            'https://placehold.co/800x900/0f172a/94a3b8?text=Expert',
        );
    }

    public function previewSectionEnabled(): bool
    {
        return match ($this->activeTab) {
            'hero' => (bool) ($this->hero['enabled'] ?? true),
            'who_we_are' => (bool) ($this->who_we_are['enabled'] ?? true),
            'mission_vision' => (bool) ($this->mission_vision['enabled'] ?? true),
            'why_choose_us' => (bool) ($this->why_choose_us['enabled'] ?? true),
            'expertise' => (bool) ($this->expertise['enabled'] ?? true),
            'timeline' => (bool) ($this->timeline['enabled'] ?? true),
            'leadership' => (bool) ($this->leadership['enabled'] ?? true),
            'experts' => (bool) ($this->experts['enabled'] ?? true),
            default => true,
        };
    }

    private function temporaryPreview(mixed $upload, ?string $stored, string $fallback = ''): string
    {
        if ($upload) {
            try {
                return $upload->temporaryUrl();
            } catch (Throwable) {
                // Keep rendering the saved image if the temporary upload is invalid.
            }
        }

        return $this->imageUrl($stored, $fallback);
    }

    protected function rules(): array
    {
        return [
            'hero.enabled' => ['required', 'boolean'],
            'hero.badge' => ['nullable', 'string', 'max:100'],
            'hero.title' => ['required', 'string', 'max:220'],
            'hero.highlighted_title' => ['nullable', 'string', 'max:220'],
            'hero.description' => ['required', 'string', 'max:4000'],
            'hero.primary_button_text' => ['nullable', 'string', 'max:60'],
            'hero.primary_button_url' => ['nullable', 'string', 'max:500'],
            'hero.secondary_button_text' => ['nullable', 'string', 'max:60'],
            'hero.secondary_button_url' => ['nullable', 'string', 'max:500'],
            'hero.top_left_image' => ['nullable', 'string', 'max:500'],
            'hero.top_right_image' => ['nullable', 'string', 'max:500'],
            'hero.bottom_left_image' => ['nullable', 'string', 'max:500'],
            'hero.info_eyebrow' => ['nullable', 'string', 'max:100'],
            'hero.info_title' => ['nullable', 'string', 'max:220'],
            'hero.info_description' => ['nullable', 'string', 'max:1000'],
            'heroUploads.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'stats' => ['required', 'array', 'size:4'],
            'stats.*.value' => ['required', 'string', 'max:30'],
            'stats.*.label' => ['required', 'string', 'max:100'],

            'who_we_are.enabled' => ['required', 'boolean'],
            'who_we_are.badge' => ['nullable', 'string', 'max:100'],
            'who_we_are.title' => ['required', 'string', 'max:300'],
            'who_we_are.image_url' => ['nullable', 'string', 'max:500'],
            'who_we_are.paragraphs' => ['required', 'array', 'min:1', 'max:5'],
            'who_we_are.paragraphs.*' => ['required', 'string', 'max:2000'],
            'whoWeAreUploads.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'mission_vision.enabled' => ['required', 'boolean'],
            'mission_vision.mission.title' => ['required', 'string', 'max:100'],
            'mission_vision.mission.description' => ['required', 'string', 'max:2000'],
            'mission_vision.mission.icon' => ['nullable', 'string', 'max:100'],
            'mission_vision.vision.title' => ['required', 'string', 'max:100'],
            'mission_vision.vision.description' => ['required', 'string', 'max:2000'],
            'mission_vision.vision.icon' => ['nullable', 'string', 'max:100'],

            'why_choose_us.enabled' => ['required', 'boolean'],
            'why_choose_us.section_title' => ['required', 'string', 'max:200'],
            'why_choose_us.highlighted_title' => ['nullable', 'string', 'max:200'],
            'why_choose_us.subtitle' => ['nullable', 'string', 'max:500'],
            'why_choose_us.items' => ['required', 'array', 'size:4'],
            'why_choose_us.items.*.title' => ['required', 'string', 'max:100'],
            'why_choose_us.items.*.description' => ['required', 'string', 'max:500'],

            'expertise.enabled' => ['required', 'boolean'],
            'expertise.section_title' => ['required', 'string', 'max:200'],
            'expertise.highlighted_title' => ['nullable', 'string', 'max:200'],
            'expertise.subtitle' => ['nullable', 'string', 'max:500'],
            'expertise.items' => ['required', 'array', 'size:4'],
            'expertise.items.*.icon' => ['required', 'string', 'max:100'],
            'expertise.items.*.eyebrow' => ['nullable', 'string', 'max:120'],
            'expertise.items.*.number' => ['nullable', 'string', 'max:10'],
            'expertise.items.*.title' => ['required', 'string', 'max:100'],
            'expertise.items.*.description' => ['required', 'string', 'max:800'],
            'expertise.items.*.panel_title' => ['nullable', 'string', 'max:150'],
            'expertise.items.*.panel_subtitle' => ['nullable', 'string', 'max:250'],
            'expertise.items.*.panel_icon' => ['nullable', 'string', 'max:100'],
            'expertise.items.*.features' => ['required', 'array', 'min:1', 'max:6'],
            'expertise.items.*.features.*.icon' => ['nullable', 'string', 'max:100'],
            'expertise.items.*.features.*.title' => ['required', 'string', 'max:120'],
            'expertise.items.*.features.*.description' => ['nullable', 'string', 'max:250'],
            'expertise.items.*.tags' => ['nullable', 'array', 'max:8'],
            'expertise.items.*.tags.*' => ['nullable', 'string', 'max:100'],

            'timeline.enabled' => ['required', 'boolean'],
            'timeline.section_title' => ['required', 'string', 'max:200'],
            'timeline.highlighted_title' => ['nullable', 'string', 'max:200'],
            'timeline.subtitle' => ['nullable', 'string', 'max:500'],
            'timeline.items' => ['required', 'array', 'min:1', 'max:8'],
            'timeline.items.*.year' => ['required', 'string', 'max:30'],
            'timeline.items.*.title' => ['required', 'string', 'max:100'],
            'timeline.items.*.description' => ['required', 'string', 'max:500'],

            'leadership.enabled' => ['required', 'boolean'],
            'leadership.badge' => ['nullable', 'string', 'max:100'],
            'leadership.title' => ['required', 'string', 'max:200'],
            'leadership.paragraphs' => ['required', 'array', 'min:1', 'max:5'],
            'leadership.paragraphs.*' => ['required', 'string', 'max:2000'],
            'leadership.name' => ['required', 'string', 'max:100'],
            'leadership.role' => ['required', 'string', 'max:100'],
            'leadership.image_url' => ['nullable', 'string', 'max:500'],
            'leadershipUploads.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'experts.enabled' => ['required', 'boolean'],
            'experts.section_title' => ['required', 'string', 'max:200'],
            'experts.highlighted_title' => ['nullable', 'string', 'max:200'],
            'experts.subtitle' => ['nullable', 'string', 'max:500'],
            'experts.items' => ['required', 'array', 'min:1', 'max:12'],
            'experts.items.*.name' => ['required', 'string', 'max:100'],
            'experts.items.*.role' => ['required', 'string', 'max:100'],
            'experts.items.*.image_url' => ['nullable', 'string', 'max:500'],
            'expertUploads.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function setTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabs)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function addWhoWeAreParagraph(): void
    {
        if (count($this->who_we_are['paragraphs'] ?? []) < 5) {
            $this->who_we_are['paragraphs'][] = '';
        }
    }

    public function removeWhoWeAreParagraph(int $index): void
    {
        if (count($this->who_we_are['paragraphs'] ?? []) <= 1) {
            return;
        }

        unset($this->who_we_are['paragraphs'][$index]);
        $this->who_we_are['paragraphs'] = array_values($this->who_we_are['paragraphs']);
    }

    public function addLeadershipParagraph(): void
    {
        if (count($this->leadership['paragraphs'] ?? []) < 5) {
            $this->leadership['paragraphs'][] = '';
        }
    }

    public function removeLeadershipParagraph(int $index): void
    {
        if (count($this->leadership['paragraphs'] ?? []) <= 1) {
            return;
        }

        unset($this->leadership['paragraphs'][$index]);
        $this->leadership['paragraphs'] = array_values($this->leadership['paragraphs']);
    }


    public function addExpertiseFeature(int $cardIndex): void
    {
        if (! isset($this->expertise['items'][$cardIndex])) {
            return;
        }

        $features = $this->expertise['items'][$cardIndex]['features'] ?? [];

        if (count($features) >= 6) {
            return;
        }

        $this->expertise['items'][$cardIndex]['features'][] = [
            'icon' => 'check_circle',
            'title' => '',
            'description' => '',
        ];
    }

    public function removeExpertiseFeature(int $cardIndex, int $featureIndex): void
    {
        $features = $this->expertise['items'][$cardIndex]['features'] ?? [];

        if (count($features) <= 1 || ! array_key_exists($featureIndex, $features)) {
            return;
        }

        unset($features[$featureIndex]);
        $this->expertise['items'][$cardIndex]['features'] = array_values($features);
    }

    public function addExpertiseTag(int $cardIndex): void
    {
        if (! isset($this->expertise['items'][$cardIndex])) {
            return;
        }

        $tags = $this->expertise['items'][$cardIndex]['tags'] ?? [];

        if (count($tags) >= 8) {
            return;
        }

        $this->expertise['items'][$cardIndex]['tags'][] = '';
    }

    public function removeExpertiseTag(int $cardIndex, int $tagIndex): void
    {
        $tags = $this->expertise['items'][$cardIndex]['tags'] ?? [];

        if (! array_key_exists($tagIndex, $tags)) {
            return;
        }

        unset($tags[$tagIndex]);
        $this->expertise['items'][$cardIndex]['tags'] = array_values($tags);
    }

    public function addTimelineItem(): void
    {
        if (count($this->timeline['items'] ?? []) >= 8) {
            return;
        }

        $this->timeline['items'][] = ['year' => '', 'title' => '', 'description' => ''];
    }

    public function removeTimelineItem(int $index): void
    {
        if (count($this->timeline['items'] ?? []) <= 1) {
            return;
        }

        unset($this->timeline['items'][$index]);
        $this->timeline['items'] = array_values($this->timeline['items']);
    }

    public function addExpertItem(): void
    {
        if (count($this->experts['items'] ?? []) >= 12) {
            return;
        }

        $this->experts['items'][] = ['name' => '', 'role' => '', 'image_url' => ''];
    }

    public function removeExpertItem(int $index): void
    {
        if (count($this->experts['items'] ?? []) <= 1) {
            return;
        }

        unset($this->experts['items'][$index]);
        $this->experts['items'] = array_values($this->experts['items']);

        $shiftedUploads = [];
        foreach ($this->expertUploads as $uploadIndex => $upload) {
            $uploadIndex = (int) $uploadIndex;

            if ($uploadIndex < $index) {
                $shiftedUploads[$uploadIndex] = $upload;
            } elseif ($uploadIndex > $index) {
                $shiftedUploads[$uploadIndex - 1] = $upload;
            }
        }
        $this->expertUploads = $shiftedUploads;
    }

    public function save(): void
    {
        $this->validate();

        foreach (['top_left_image', 'top_right_image', 'bottom_left_image'] as $field) {
            if ($upload = ($this->heroUploads[$field] ?? null)) {
                $this->hero[$field] = $this->storeReplacement(
                    $upload,
                    $this->hero[$field] ?? null,
                    'about/hero',
                    1200,
                );
            }
        }

        if ($upload = ($this->whoWeAreUploads['image'] ?? null)) {
            $this->who_we_are['image_url'] = $this->storeReplacement(
                $upload,
                $this->who_we_are['image_url'] ?? null,
                'about/who-we-are',
                1200,
            );
        }

        if ($upload = ($this->leadershipUploads['image'] ?? null)) {
            $this->leadership['image_url'] = $this->storeReplacement(
                $upload,
                $this->leadership['image_url'] ?? null,
                'about/leadership',
                1000,
            );
        }

        foreach ($this->expertUploads as $index => $upload) {
            if (! $upload || ! isset($this->experts['items'][$index])) {
                continue;
            }

            $this->experts['items'][$index]['image_url'] = $this->storeReplacement(
                $upload,
                $this->experts['items'][$index]['image_url'] ?? null,
                'about/experts',
                900,
            );
        }

        $this->setting->update([
            'hero' => $this->hero,
            'stats' => array_values($this->stats),
            'who_we_are' => $this->who_we_are,
            'mission_vision' => $this->mission_vision,
            'why_choose_us' => $this->why_choose_us,
            'expertise' => $this->expertise,
            'timeline' => $this->timeline,
            'leadership' => $this->leadership,
            'experts' => $this->experts,
        ]);

        $this->reset('heroUploads', 'whoWeAreUploads', 'leadershipUploads', 'expertUploads');
        $this->setting = $this->setting->fresh();

        $this->dispatch('toast', message: 'About page settings updated successfully.', type: 'success');
    }

    private function storeReplacement(
        mixed $upload,
        ?string $oldImage,
        string $directory,
        int $maxWidth,
    ): string {
        $newPath = app(ImageService::class)->optimizeAndStore(
            $upload,
            $directory,
            maxWidth: $maxWidth,
            quality: 88,
        );

        if (
            filled($oldImage)
            && ! Str::startsWith($oldImage, ['http://', 'https://'])
            && $oldImage !== $newPath
            && Storage::disk('public')->exists($oldImage)
        ) {
            Storage::disk('public')->delete($oldImage);
        }

        return $newPath;
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-8">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">About Page Settings</h1>
            <p class="mt-1 text-body-md text-secondary">
                Edit about page sections and preview your changes before publishing.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $tab)
                <button type="button" wire:click="setTab('{{ $key }}')" @class([ 'inline-flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition' , 'bg-primary text-white shadow-sm'=> $activeTab === $key,
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
                <div class="space-y-6 xl:col-span-7">
                    {{-- Hero Tab --}}
                    @if ($activeTab === 'hero')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">view_carousel</span>
                                    Hero Section
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Manage the hero heading, description and buttons.</p>
                            </div>

                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="hero.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
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

                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">smart_button</span>
                            Hero Buttons
                        </h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            @foreach ([
                            'primary_button_text' => 'Primary Button Text',
                            'primary_button_url' => 'Primary Button URL',
                            'secondary_button_text' => 'Secondary Button Text',
                            'secondary_button_url' => 'Secondary Button URL',
                            ] as $field => $label)
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">{{ $label }}</label>
                                <input type="text" wire:model.live.debounce.300ms="hero.{{ $field }}" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">info</span>
                            Hero Information Card
                        </h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Eyebrow</label>
                                <input type="text" wire:model.live.debounce.300ms="hero.info_eyebrow" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Title</label>
                                <input type="text" wire:model.live.debounce.300ms="hero.info_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Description</label>
                                <textarea wire:model.live.debounce.300ms="hero.info_description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        @php
                        $heroImageFields = [
                        'top_left_image' => 'Top Left Image',
                        'top_right_image' => 'Top Right Image',
                        'bottom_left_image' => 'Bottom Left Image',
                        ];
                        $heroImageFallbacks = [
                        'top_left_image' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1000&q=80',
                        'top_right_image' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1000&q=80',
                        'bottom_left_image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80',
                        ];
                        @endphp
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">imagesmode</span>
                            Hero Images
                        </h3>
                        <p class="mb-6 text-body-sm text-secondary">Upload an image or paste an external URL — not both. Uploading a file clears the URL and vice versa.</p>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            @foreach ($heroImageFields as $field => $label)
                            <div wire:key="hero-image-{{ $field }}">
                                <h4 class="mb-3 font-label-md text-on-surface">{{ $label }}</h4>
                                <label for="about-hero-{{ $field }}" class="flex h-40 cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-outline-variant bg-surface">
                                    <img src="{{ $this->previewImage($field, $heroImageFallbacks[$field]) }}" alt="{{ $label }}" class="h-full w-full object-cover">
                                </label>
                                <input id="about-hero-{{ $field }}" type="file" wire:model="heroUploads.{{ $field }}" accept="image/jpeg,image/png,image/webp" class="hidden">
                                <div wire:loading wire:target="heroUploads.{{ $field }}" class="mt-2 text-sm text-primary">Uploading...</div>
                                @error("heroUploads.{$field}")
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                                <input type="text" wire:model.live.debounce.300ms="hero.{{ $field }}" class="mt-2 w-full rounded border border-outline-variant px-3 py-2 text-xs" placeholder="Or paste image URL">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Stats Tab --}}
                    @if ($activeTab === 'stats')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">monitoring</span>
                            Statistics
                        </h3>
                        <p class="mb-6 text-body-sm text-secondary">Exactly 4 statistics are displayed in the banner.</p>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach ($stats as $index => $stat)
                            <div wire:key="stat-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Value</label>
                                    <input type="text" wire:model.live.debounce.300ms="stats.{{ $index }}.value" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                </div>
                                <div class="mt-4 space-y-2">
                                    <label class="block font-label-md text-on-surface">Label</label>
                                    <input type="text" wire:model.live.debounce.300ms="stats.{{ $index }}.label" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Who We Are Tab --}}
                    @if ($activeTab === 'who_we_are')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">groups</span>
                                    Who We Are
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Company introduction section.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="who_we_are.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Badge Text</label>
                                <input type="text" wire:model.live.debounce.300ms="who_we_are.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Title</label>
                                <input type="text" wire:model.live.debounce.300ms="who_we_are.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                @error('who_we_are.title')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="block font-label-md text-on-surface">Paragraphs</label>
                                <button type="button" wire:click="addWhoWeAreParagraph" class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Paragraph</button>
                            </div>
                            @foreach ($who_we_are['paragraphs'] as $index => $paragraph)
                            <div wire:key="who-paragraph-{{ $index }}" class="flex gap-3">
                                <textarea wire:model.live.debounce.300ms="who_we_are.paragraphs.{{ $index }}" rows="2" class="flex-1 rounded border border-outline-variant px-4 py-2.5"></textarea>
                                @if (count($who_we_are['paragraphs']) > 1)
                                <button type="button" wire:click="removeWhoWeAreParagraph({{ $index }})" class="mt-1 text-slate-400 hover:text-red-500 cursor-pointer">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-6 space-y-2">
                            <label class="block font-label-md text-on-surface">Section Image</label>
                            <label for="who-we-are-image" class="flex h-48 cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-outline-variant bg-surface">
                                <img src="{{ $this->whoWeArePreviewImage() }}" alt="Who We Are" class="h-full w-full object-cover">
                            </label>
                            <input id="who-we-are-image" type="file" wire:model="whoWeAreUploads.image" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <div wire:loading wire:target="whoWeAreUploads.image" class="text-sm text-primary">Uploading...</div>
                            @error('whoWeAreUploads.image')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <input type="text" wire:model.live.debounce.300ms="who_we_are.image_url" class="w-full rounded border border-outline-variant px-4 py-2.5" placeholder="Or paste image URL">
                        </div>
                    </div>
                    @endif
                    @if ($activeTab === 'mission_vision')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">visibility</span>
                                    Mission & Vision
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Manage both mission and vision cards.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="mission_vision.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="space-y-8">
                            <div class="rounded-xl border border-cyan-200/70 bg-cyan-50/50 p-6">
                                <div class="mb-5 flex items-center gap-3">
                                    <div class="grid size-11 shrink-0 place-items-center rounded-xl border border-cyan-200 bg-cyan-100 text-cyan-700">
                                        <span class="material-symbols-outlined block text-[23px] leading-none">
                                            {{ $mission_vision['mission']['icon'] ?? 'home' }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700/60">Our purpose</p>
                                        <h4 class="font-label-md text-on-surface">Mission card</h4>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Title</label>
                                            <input type="text" wire:model.live.debounce.300ms="mission_vision.mission.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Material icon name</label>
                                            <div class="relative">
                                                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] leading-none text-cyan-700">
                                                    {{ $mission_vision['mission']['icon'] ?? 'home' }}
                                                </span>
                                                <input type="text" wire:model.live.debounce.300ms="mission_vision.mission.icon" class="w-full rounded border border-outline-variant bg-white py-2.5 pl-11 pr-4" placeholder="e.g. flag, target, home">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Description</label>
                                        <textarea wire:model.live.debounce.300ms="mission_vision.mission.description" rows="3" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-blue-200/70 bg-blue-50/50 p-6">
                                <div class="mb-5 flex items-center gap-3">
                                    <div class="grid size-11 shrink-0 place-items-center rounded-xl border border-blue-200 bg-blue-100 text-blue-700">
                                        <span class="material-symbols-outlined block text-[23px] leading-none">
                                            {{ $mission_vision['vision']['icon'] ?? 'visibility' }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-700/60">Our direction</p>
                                        <h4 class="font-label-md text-on-surface">Vision card</h4>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Title</label>
                                            <input type="text" wire:model.live.debounce.300ms="mission_vision.vision.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Material icon name</label>
                                            <div class="relative">
                                                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] leading-none text-blue-700">
                                                    {{ $mission_vision['vision']['icon'] ?? 'visibility' }}
                                                </span>
                                                <input type="text" wire:model.live.debounce.300ms="mission_vision.vision.icon" class="w-full rounded border border-outline-variant bg-white py-2.5 pl-11 pr-4" placeholder="e.g. visibility, lightbulb, rocket_launch">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Description</label>
                                        <textarea wire:model.live.debounce.300ms="mission_vision.vision.description" rows="3" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5"></textarea>
                                    </div>
                                </div>
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
                                <p class="mt-2 text-body-sm text-secondary">4 reasons why clients choose your company.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="why_choose_us.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Section Title</label>
                                <input type="text" wire:model.live.debounce.300ms="why_choose_us.section_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                <input type="text" wire:model.live.debounce.300ms="why_choose_us.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Subtitle</label>
                                <input type="text" wire:model.live.debounce.300ms="why_choose_us.subtitle" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                        </div>

                        <div class="mt-8 space-y-4">
                            <h4 class="font-label-md text-on-surface">Items (4 cards)</h4>
                            @foreach ($why_choose_us['items'] as $index => $item)
                            <div wire:key="why-item-{{ $index }}" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-secondary">Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.items.{{ $index }}.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-secondary">Description</label>
                                    <input type="text" wire:model.live.debounce.300ms="why_choose_us.items.{{ $index }}.description" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Expertise Tab --}}
                    @if ($activeTab === 'expertise')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">psychology</span>
                                    Expertise Bento Section
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Edit the four bento cards and preview all icons, labels, features and tags from one place.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="expertise.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Section Title</label>
                                <input type="text" wire:model.live.debounce.300ms="expertise.section_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                <input type="text" wire:model.live.debounce.300ms="expertise.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Subtitle</label>
                                <textarea wire:model.live.debounce.300ms="expertise.subtitle" rows="2" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @foreach ($expertise['items'] as $index => $item)
                        @php
                        $positionLabels = [
                        'Large left card',
                        'Upper-right security card',
                        'Lower-right development card',
                        'Full-width cloud card',
                        ];
                        @endphp
                        <div wire:key="expertise-card-editor-{{ $index }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-12 w-12 place-items-center rounded-xl border border-primary/15 bg-primary/5 text-primary">
                                        <span class="material-symbols-outlined text-[25px] leading-none">{{ $item['icon'] ?? 'settings_suggest' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Card {{ $index + 1 }} · {{ $positionLabels[$index] ?? 'Bento card' }}</p>
                                        <h4 class="mt-1 text-lg font-bold text-on-surface">{{ $item['title'] ?: 'Untitled expertise card' }}</h4>
                                    </div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $item['number'] ?? '' }}</span>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Main Icon</label>
                                    <div class="flex gap-3">
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-primary/15 bg-primary/5 text-primary">
                                            <span class="material-symbols-outlined leading-none">{{ $item['icon'] ?? 'settings_suggest' }}</span>
                                        </div>
                                        <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.icon" class="min-w-0 flex-1 rounded border border-outline-variant px-4 py-2.5" placeholder="settings_suggest">
                                    </div>
                                    <p class="text-xs text-secondary">Use a Material Symbols icon name.</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Eyebrow Label</label>
                                    <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.eyebrow" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Card Number</label>
                                    <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.number" class="w-full rounded border border-outline-variant px-4 py-2.5" placeholder="01">
                                </div>
                                <div class="space-y-2">
                                    <label class="block font-label-md text-on-surface">Card Title</label>
                                    <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block font-label-md text-on-surface">Card Description</label>
                                    <textarea wire:model.live.debounce.300ms="expertise.items.{{ $index }}.description" rows="3" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>
                                </div>
                            </div>

                            <div class="mt-7 rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <h5 class="font-semibold text-on-surface">Inner Visual Panel</h5>
                                <p class="mt-1 text-xs text-secondary">These values control the dashboard, shield, browser or cloud panel inside this card.</p>
                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Panel Title</label>
                                        <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.panel_title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Panel Subtitle</label>
                                        <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.panel_subtitle" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <label class="block font-label-md text-secondary">Panel Icon</label>
                                        <div class="flex gap-3">
                                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-primary/15 bg-white text-primary">
                                                <span class="material-symbols-outlined leading-none">{{ $item['panel_icon'] ?? 'widgets' }}</span>
                                            </div>
                                            <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.panel_icon" class="min-w-0 flex-1 rounded border border-outline-variant bg-white px-4 py-2.5" placeholder="verified_user">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-7">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h5 class="font-semibold text-on-surface">Feature Items</h5>
                                        <p class="mt-1 text-xs text-secondary">Each feature has its own icon, title and description.</p>
                                    </div>
                                    <button type="button" wire:click="addExpertiseFeature({{ $index }})" class="cursor-pointer rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/10">+ Add Feature</button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach ($item['features'] ?? [] as $featureIndex => $feature)
                                    <div wire:key="expertise-{{ $index }}-feature-{{ $featureIndex }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2 text-sm font-semibold text-on-surface">
                                                <span class="grid h-8 w-8 place-items-center rounded-lg border border-primary/15 bg-white text-primary">
                                                    <span class="material-symbols-outlined text-[18px] leading-none">{{ $feature['icon'] ?? 'check_circle' }}</span>
                                                </span>
                                                Feature {{ $featureIndex + 1 }}
                                            </div>
                                            @if (count($item['features'] ?? []) > 1)
                                            <button type="button" wire:click="removeExpertiseFeature({{ $index }}, {{ $featureIndex }})" class="cursor-pointer text-slate-400 hover:text-red-500">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                            @endif
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-xs font-semibold text-secondary">Icon</label>
                                                <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.features.{{ $featureIndex }}.icon" class="w-full rounded border border-outline-variant bg-white px-3 py-2.5">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-xs font-semibold text-secondary">Title</label>
                                                <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.features.{{ $featureIndex }}.title" class="w-full rounded border border-outline-variant bg-white px-3 py-2.5">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-xs font-semibold text-secondary">Description</label>
                                                <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.features.{{ $featureIndex }}.description" class="w-full rounded border border-outline-variant bg-white px-3 py-2.5">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-7">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h5 class="font-semibold text-on-surface">Tags</h5>
                                        <p class="mt-1 text-xs text-secondary">Small pills displayed at the bottom of supported cards.</p>
                                    </div>
                                    <button type="button" wire:click="addExpertiseTag({{ $index }})" class="cursor-pointer rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/10">+ Add Tag</button>
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach ($item['tags'] ?? [] as $tagIndex => $tag)
                                    <div wire:key="expertise-{{ $index }}-tag-{{ $tagIndex }}" class="flex gap-2">
                                        <input type="text" wire:model.live.debounce.300ms="expertise.items.{{ $index }}.tags.{{ $tagIndex }}" class="min-w-0 flex-1 rounded border border-outline-variant px-3 py-2.5" placeholder="Tag text">
                                        <button type="button" wire:click="removeExpertiseTag({{ $index }}, {{ $tagIndex }})" class="cursor-pointer rounded border border-slate-200 px-2 text-slate-400 hover:border-red-200 hover:text-red-500">
                                            <span class="material-symbols-outlined text-[19px]">close</span>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Timeline Tab --}}
                    @if ($activeTab === 'timeline')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">timeline</span>
                                    Timeline
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Company milestones and journey events.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="timeline.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Section Title</label>
                                <input type="text" wire:model.live.debounce.300ms="timeline.section_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                <input type="text" wire:model.live.debounce.300ms="timeline.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Subtitle</label>
                                <input type="text" wire:model.live.debounce.300ms="timeline.subtitle" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                        </div>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-label-md text-on-surface">Timeline Items</h4>
                                <button type="button" wire:click="addTimelineItem" class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Item</button>
                            </div>
                            @foreach ($timeline['items'] as $index => $item)
                            <div wire:key="timeline-item-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <h5 class="font-label-md text-on-surface">Item {{ $index + 1 }}</h5>
                                    @if (count($timeline['items']) > 1)
                                    <button type="button" wire:click="removeTimelineItem({{ $index }})" class="text-slate-400 hover:text-red-500 cursor-pointer">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                    @endif
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Year</label>
                                        <input type="text" wire:model.live.debounce.300ms="timeline.items.{{ $index }}.year" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5" placeholder="2024">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Title</label>
                                        <input type="text" wire:model.live.debounce.300ms="timeline.items.{{ $index }}.title" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block font-label-md text-secondary">Description</label>
                                        <input type="text" wire:model.live.debounce.300ms="timeline.items.{{ $index }}.description" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Leadership Tab --}}
                    @if ($activeTab === 'leadership')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">person</span>
                                    Leadership Message
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Founder or leadership message section.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="leadership.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Badge Text</label>
                                <input type="text" wire:model.live.debounce.300ms="leadership.badge" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Title</label>
                                <input type="text" wire:model.live.debounce.300ms="leadership.title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <label class="block font-label-md text-on-surface">Paragraphs</label>
                            @foreach ($leadership['paragraphs'] as $index => $paragraph)
                            <div wire:key="leadership-paragraph-{{ $index }}" class="flex gap-3">
                                <textarea wire:model.live.debounce.300ms="leadership.paragraphs.{{ $index }}" rows="2" class="flex-1 rounded border border-outline-variant px-4 py-2.5"></textarea>
                                @if (count($leadership['paragraphs']) > 1)
                                <button type="button" wire:click="removeLeadershipParagraph({{ $index }})" class="mt-1 text-slate-400 hover:text-red-500 cursor-pointer">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                @endif
                            </div>
                            @endforeach
                            <button type="button" wire:click="addLeadershipParagraph" class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Paragraph</button>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Name</label>
                                <input type="text" wire:model.live.debounce.300ms="leadership.name" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Role</label>
                                <input type="text" wire:model.live.debounce.300ms="leadership.role" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                        </div>

                        <div class="mt-8 space-y-3">
                            <label class="block font-label-md text-on-surface">Leadership Image</label>
                            <label for="leadership-image" class="flex h-64 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-outline-variant bg-surface">
                                <img src="{{ $this->leadershipPreviewImage() }}" class="h-full w-full object-cover" alt="Leadership preview">
                            </label>
                            <input id="leadership-image" type="file" wire:model="leadershipUploads.image" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <div wire:loading wire:target="leadershipUploads.image" class="text-sm text-primary">Uploading preview...</div>
                            @error('leadershipUploads.image')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <input type="text" wire:model.live.debounce.300ms="leadership.image_url" class="w-full rounded border border-outline-variant px-4 py-2.5" placeholder="Or paste image URL">
                        </div>
                    </div>
                    @endif

                    {{-- Experts Tab --}}
                    @if ($activeTab === 'experts')
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-8 flex items-start justify-between gap-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-h3 font-h2">
                                    <span class="material-symbols-outlined text-primary">groups</span>
                                    Meet Our Experts
                                </h3>
                                <p class="mt-2 text-body-sm text-secondary">Team members displayed on the about page.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="experts.enabled" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Section Title</label>
                                <input type="text" wire:model.live.debounce.300ms="experts.section_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Highlighted Title</label>
                                <input type="text" wire:model.live.debounce.300ms="experts.highlighted_title" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block font-label-md text-on-surface">Subtitle</label>
                                <input type="text" wire:model.live.debounce.300ms="experts.subtitle" class="w-full rounded border border-outline-variant px-4 py-2.5">
                            </div>
                        </div>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-label-md text-on-surface">Team Members</h4>
                                <button type="button" wire:click="addExpertItem" class="text-sm font-semibold text-primary hover:text-primary/80 cursor-pointer">+ Add Member</button>
                            </div>
                            @foreach ($experts['items'] as $index => $item)
                            <div wire:key="expert-item-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <h5 class="font-label-md text-on-surface">Member {{ $index + 1 }}</h5>
                                    @if (count($experts['items']) > 1)
                                    <button type="button" wire:click="removeExpertItem({{ $index }})" class="text-slate-400 hover:text-red-500 cursor-pointer">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                    @endif
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-[150px_1fr]">
                                    <div class="space-y-2">
                                        <label for="expert-image-{{ $index }}" class="flex h-44 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-outline-variant bg-white">
                                            <img src="{{ $this->expertPreviewImage($index) }}" class="h-full w-full object-cover" alt="Expert {{ $index + 1 }} preview">
                                        </label>
                                        <input id="expert-image-{{ $index }}" type="file" wire:model="expertUploads.{{ $index }}" accept="image/jpeg,image/png,image/webp" class="hidden">
                                        <div wire:loading wire:target="expertUploads.{{ $index }}" class="text-xs text-primary">Uploading...</div>
                                        @error("expertUploads.{$index}")
                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Name</label>
                                            <input type="text" wire:model.live.debounce.300ms="experts.items.{{ $index }}.name" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block font-label-md text-secondary">Role</label>
                                            <input type="text" wire:model.live.debounce.300ms="experts.items.{{ $index }}.role" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="block font-label-md text-secondary">Image URL</label>
                                            <input type="text" wire:model.live.debounce.300ms="experts.items.{{ $index }}.image_url" class="w-full rounded border border-outline-variant bg-white px-4 py-2.5" placeholder="Or paste image URL">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Live Preview --}}
                <div class="xl:sticky xl:top-6 xl:col-span-5">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <h3 class="font-label-md text-on-surface">Live Preview</h3>
                                <p class="mt-1 text-xs text-secondary">Changes update automatically.</p>
                            </div>
                            <div class="flex gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                        </div>

                        <div class="bg-slate-100 p-4">
                            <div class="relative min-h-[330px] overflow-hidden rounded-xl bg-linear-to-br from-slate-950 via-blue-950 to-slate-950 p-3 text-white">
                                {{-- Disabled overlay --}}
                                @if (! $this->previewSectionEnabled())
                                <div class="absolute inset-0 z-50 flex items-center justify-center bg-slate-950/90">
                                    <div class="text-center">
                                        <span class="material-symbols-outlined text-5xl text-white/40">visibility_off</span>
                                        <p class="mt-3 text-sm text-white/70">Section is disabled</p>
                                    </div>
                                </div>
                                @endif

                                {{-- Hero Preview --}}
                                @if ($activeTab === 'hero')
                                <div class="relative h-[560px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="relative overflow-hidden py-20 sm:py-24 lg:py-30">
                                            <div class="absolute inset-0 pointer-events-none">
                                                <div class="absolute left-[6%] top-10 h-52 w-52 rounded-full bg-cyan-400/10 blur-3xl"></div>
                                                <div class="absolute right-[8%] top-10 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
                                                <div class="absolute left-1/2 bottom-0 h-72 w-72 -translate-x-1/2 rounded-full bg-sky-400/8 blur-3xl"></div>
                                            </div>
                                            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                                                    <div class="max-w-3xl">
                                                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                                                            <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                                                            {{ $hero['badge'] ?? '' }}
                                                        </div>
                                                        <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-7xl">
                                                            {{ $hero['title'] ?? '' }}
                                                            <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">{{ $hero['highlighted_title'] ?? '' }}</span>
                                                        </h1>
                                                        <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">{{ $hero['description'] ?? '' }}</p>
                                                        <div class="mt-8 flex flex-wrap gap-4">
                                                            @if (filled($hero['primary_button_text'] ?? null))
                                                            <span class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-sky-400 px-6 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/30">{{ $hero['primary_button_text'] }}</span>
                                                            @endif
                                                            @if (filled($hero['secondary_button_text'] ?? null))
                                                            <span class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-6 py-3.5 font-semibold text-white backdrop-blur-xl">{{ $hero['secondary_button_text'] }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="relative">
                                                        <div class="relative overflow-hidden rounded-[34px] border border-white/15 bg-white/8 p-4 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                                                            <div class="absolute left-8 top-8 h-24 w-24 rounded-full bg-cyan-400/12 blur-3xl"></div>
                                                            <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <img src="{{ $this->previewImage('top_left_image', 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1000&q=80') }}" class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                                                                <img src="{{ $this->previewImage('top_right_image', 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1000&q=80') }}" class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                                                                <img src="{{ $this->previewImage('bottom_left_image', 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80') }}" class="h-52 w-full rounded-[24px] object-cover sm:h-64" alt="">
                                                                <div class="flex h-52 flex-col justify-center rounded-[24px] border border-white/10 bg-white/8 p-6 backdrop-blur-xl sm:h-64">
                                                                    <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">{{ $hero['info_eyebrow'] ?? '' }}</p>
                                                                    <h3 class="mt-4 text-2xl font-bold text-white">{{ $hero['info_title'] ?? '' }}</h3>
                                                                    <p class="mt-3 text-sm leading-7 text-blue-100/65">{{ $hero['info_description'] ?? '' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Stats Preview --}}
                                @if ($activeTab === 'stats')
                                <div class="relative h-[330px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="relative overflow-hidden py-20">
                                            <div class="absolute inset-0 pointer-events-none">
                                                <div class="absolute left-[6%] top-10 h-52 w-52 rounded-full bg-cyan-400/10 blur-3xl"></div>
                                                <div class="absolute right-[8%] top-10 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
                                            </div>
                                            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                                    @foreach ($stats as $stat)
                                                    <div class="about-stat-card">
                                                        <p class="text-3xl font-bold text-white sm:text-4xl">{{ $stat['value'] }}</p>
                                                        <p class="mt-2 text-sm text-blue-100/60">{{ $stat['label'] }}</p>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Who We Are Preview --}}
                                @if ($activeTab === 'who_we_are')
                                <div class="relative h-[420px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="py-20">
                                            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="about-panel">
                                                    <div class="grid gap-10 lg:grid-cols-[1fr_420px] items-center">
                                                        <div>
                                                            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs text-blue-100/80 backdrop-blur-xl">
                                                                <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                                                                {{ $who_we_are['badge'] ?? '' }}
                                                            </div>
                                                            <h2 class="mt-6 text-3xl font-bold sm:text-4xl lg:text-5xl">{{ $who_we_are['title'] ?? '' }}</h2>
                                                            @foreach ($who_we_are['paragraphs'] ?? [] as $i => $paragraph)
                                                            <p @class(['mt-5 about-text'=> $i === 0, 'mt-4 about-text' => $i > 0])>{{ $paragraph }}</p>
                                                            @endforeach
                                                        </div>
                                                        <div class="rounded-[30px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl">
                                                            <img src="{{ $this->whoWeArePreviewImage() }}" class="h-[340px] w-full rounded-[24px] object-cover" alt="Who we are">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Mission & Vision Preview --}}
                                @if ($activeTab === 'mission_vision')
                                <div class="relative h-[360px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="py-20">
                                            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="grid gap-6 lg:grid-cols-2">
                                                    <article class="about-mv-card group relative overflow-hidden">
                                                        <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
                                                        <div class="pointer-events-none absolute bottom-0 left-0 h-28 w-40 bg-linear-to-tr from-cyan-500/6 to-transparent"></div>
                                                        <div class="relative">
                                                            <div class="flex items-start gap-5">
                                                                <div class="grid size-14 shrink-0 place-items-center rounded-2xl border border-cyan-300/20 bg-cyan-400/12 text-cyan-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_12px_30px_rgba(34,211,238,0.08)]">
                                                                    <span class="material-symbols-outlined block text-[28px] leading-none">{{ $mission_vision['mission']['icon'] ?? 'home' }}</span>
                                                                </div>
                                                                <div class="min-w-0 pt-0.5">
                                                                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-200/55">Our purpose</p>
                                                                    <h3 class="mt-1.5 text-2xl font-bold leading-tight text-white">{{ $mission_vision['mission']['title'] ?? '' }}</h3>
                                                                </div>
                                                            </div>
                                                            <p class="mt-6 text-sm leading-7 text-blue-100/70 sm:text-base">{{ $mission_vision['mission']['description'] ?? '' }}</p>
                                                        </div>
                                                    </article>
                                                    <article class="about-mv-card group relative overflow-hidden">
                                                        <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-blue-500/10 blur-3xl"></div>
                                                        <div class="pointer-events-none absolute bottom-0 left-0 h-28 w-40 bg-linear-to-tr from-blue-500/6 to-transparent"></div>
                                                        <div class="relative">
                                                            <div class="flex items-start gap-5">
                                                                <div class="grid size-14 shrink-0 place-items-center rounded-2xl border border-blue-300/20 bg-blue-500/12 text-blue-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_12px_30px_rgba(59,130,246,0.08)]">
                                                                    <span class="material-symbols-outlined block text-[28px] leading-none">{{ $mission_vision['vision']['icon'] ?? 'visibility' }}</span>
                                                                </div>
                                                                <div class="min-w-0 pt-0.5">
                                                                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-200/55">Our direction</p>
                                                                    <h3 class="mt-1.5 text-2xl font-bold leading-tight text-white">{{ $mission_vision['vision']['title'] ?? '' }}</h3>
                                                                </div>
                                                            </div>
                                                            <p class="mt-6 text-sm leading-7 text-blue-100/70 sm:text-base">{{ $mission_vision['vision']['description'] ?? '' }}</p>
                                                        </div>
                                                    </article>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Why Choose Us Preview --}}
                                @if ($activeTab === 'why_choose_us')
                                <div class="relative h-[410px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="py-20">
                                            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="text-center mb-12">
                                                    <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                                                        {{ $why_choose_us['section_title'] ?? '' }}
                                                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">{{ $why_choose_us['highlighted_title'] ?? '' }}</span>
                                                    </h2>
                                                    @if (filled($why_choose_us['subtitle'] ?? null))
                                                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">{{ $why_choose_us['subtitle'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                                                    @foreach ($why_choose_us['items'] ?? [] as $i => $item)
                                                    <div @class(['why-upgrade-card'=> true, 'why-upgrade-card-featured' => $i === 1])>
                                                        <h3 class="why-upgrade-title">{{ $item['title'] }}</h3>
                                                        <p class="why-upgrade-text">{{ $item['description'] }}</p>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Expertise Preview --}}
                                @if ($activeTab === 'expertise')
                                @php
                                $previewExpertiseLayout = [
                                'md:col-span-2 xl:col-span-7 xl:row-span-2',
                                'xl:col-span-5',
                                'xl:col-span-5',
                                'md:col-span-2 xl:col-span-12',
                                ];
                                $previewExpertiseIcons = ['support_agent', 'shield_lock', 'code', 'cloud'];
                                @endphp
                                <div class="relative h-[650px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="relative overflow-hidden py-20">
                                            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="mx-auto mb-12 max-w-3xl text-center">
                                                    <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">
                                                        {{ $expertise['section_title'] ?? '' }}
                                                        <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">{{ $expertise['highlighted_title'] ?? '' }}</span>
                                                    </h2>
                                                    @if (filled($expertise['subtitle'] ?? null))
                                                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">{{ $expertise['subtitle'] }}</p>
                                                    @endif
                                                </div>

                                                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-12 xl:auto-rows-[300px]">
                                                    @foreach ($expertise['items'] ?? [] as $index => $item)
                                                    @php
                                                    $previewFeatures = array_values($item['features'] ?? []);
                                                    $previewTags = array_values($item['tags'] ?? []);
                                                    @endphp
                                                    <article class="group relative overflow-hidden rounded-[30px] border border-white/10 bg-slate-950/45 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.32)] backdrop-blur-2xl sm:p-8 {{ $previewExpertiseLayout[$index] ?? 'xl:col-span-4' }}">
                                                        <div class="absolute inset-0 bg-linear-to-br from-white/9 via-white/[0.025] to-transparent"></div>
                                                        <div class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-cyan-400/14 blur-3xl"></div>
                                                        <div class="absolute -left-10 bottom-8 h-40 w-40 rounded-full bg-blue-500/10 blur-3xl"></div>
                                                        <div class="absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgba(255,255,255,0.7)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.7)_1px,transparent_1px)] [background-size:30px_30px] [mask-image:radial-gradient(circle_at_top_right,black,transparent_72%)]"></div>

                                                        <div class="relative flex h-full flex-col">
                                                            <div class="flex items-start justify-between gap-5">
                                                                <div class="flex items-center gap-4">
                                                                    <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/12 text-cyan-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.12)]">
                                                                        <span class="material-symbols-outlined text-[28px]">{{ $item['icon'] ?? ($previewExpertiseIcons[$index] ?? 'settings_suggest') }}</span>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/65">{{ $item['eyebrow'] ?? (['Managed Services', 'Protection Layer', 'Digital Experience', 'Cloud Infrastructure'][$index] ?? 'Technology Service') }}</p>
                                                                        <h3 @class(['mt-1 font-bold text-white', 'text-2xl sm:text-3xl'=> $index === 0, 'text-xl sm:text-2xl' => $index !== 0])>{{ $item['title'] ?? '' }}</h3>
                                                                    </div>
                                                                </div>
                                                                <span class="rounded-full border border-white/10 bg-white/6 px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-blue-100/55">{{ $item['number'] ?? str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                                            </div>
                                                            <p class="mt-5 max-w-2xl text-sm leading-7 text-blue-100/65 sm:text-base">{{ $item['description'] ?? '' }}</p>

                                                            @if ($index === 0)
                                                            <div class="mt-7 flex flex-1 flex-col rounded-[24px] border border-white/10 bg-black/20 p-5 shadow-inner shadow-black/20 sm:p-6">
                                                                <div class="flex flex-wrap items-center justify-between gap-4">
                                                                    <div>
                                                                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-100/45">{{ $item['panel_title'] ?? 'Operations overview' }}</p>
                                                                        <p class="mt-1 text-sm font-semibold text-white sm:text-base">{{ $item['panel_subtitle'] ?? 'Managed technology environment' }}</p>
                                                                    </div>
                                                                    <span class="inline-flex items-center gap-2 rounded-full border border-cyan-300/15 bg-cyan-400/10 px-3 py-1 text-[10px] font-semibold text-cyan-100"><span class="material-symbols-outlined text-[14px]">{{ $item['panel_icon'] ?? 'settings_suggest' }}</span>Managed systems</span>
                                                                </div>
                                                                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                                                    @foreach (array_slice($previewFeatures, 0, 3) as $feature)
                                                                    <div class="rounded-2xl border border-white/8 bg-white/[0.035] p-4">
                                                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-300/15 bg-cyan-400/10 text-cyan-200"><span class="material-symbols-outlined text-[20px]">{{ $feature['icon'] ?? 'settings_suggest' }}</span></span>
                                                                        <p class="mt-4 text-sm font-semibold text-white/90">{{ $feature['title'] ?? '' }}</p>
                                                                        <p class="mt-1 text-xs leading-5 text-blue-100/45">{{ $feature['description'] ?? '' }}</p>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                                <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                                                    @foreach (array_slice($previewTags, 0, 4) as $tag)
                                                                    <span class="rounded-full border border-white/10 bg-white/[0.035] px-3 py-1.5 text-[10px] font-medium text-blue-100/55">{{ $tag }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            @elseif ($index === 1)
                                                            <div class="mt-6 grid flex-1 grid-cols-[1fr_118px] items-center gap-5">
                                                                <div class="space-y-2.5">
                                                                    @foreach (array_slice($previewFeatures, 0, 3) as $feature)
                                                                    <div class="flex items-center gap-2.5 text-xs text-blue-100/65"><span class="flex h-5 w-5 items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/10 text-cyan-200"><span class="material-symbols-outlined text-[13px]">{{ $feature['icon'] ?? 'check' }}</span></span>{{ $feature['title'] ?? '' }}</div>
                                                                    @endforeach
                                                                </div>
                                                                <div class="relative flex aspect-square w-full items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/8 shadow-[inset_0_0_35px_rgba(34,211,238,0.08)]">
                                                                    <div class="absolute inset-2 rounded-full border border-dashed border-cyan-200/20 animate-[spin_14s_linear_infinite]"></div>
                                                                    <div class="absolute inset-5 rounded-full border border-white/10 bg-slate-950/55"></div>
                                                                    <div class="relative text-center"><span class="material-symbols-outlined text-[30px] text-cyan-200">{{ $item['panel_icon'] ?? 'verified_user' }}</span>
                                                                        <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-100/50">{{ $item['panel_subtitle'] ?? 'Protected' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @elseif ($index === 2)
                                                            <div class="mt-auto pt-5">
                                                                <div class="overflow-hidden rounded-[20px] border border-white/10 bg-black/25 shadow-inner shadow-black/20">
                                                                    <div class="flex items-center gap-1.5 border-b border-white/8 px-4 py-3"><span class="h-2 w-2 rounded-full bg-red-300/70"></span><span class="h-2 w-2 rounded-full bg-amber-300/70"></span><span class="h-2 w-2 rounded-full bg-emerald-300/70"></span>
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
                                                                <div class="mt-3 flex flex-wrap gap-2">@foreach ((count($previewTags) ? array_slice($previewTags, 0, 3) : array_map(fn ($feature) => $feature['title'] ?? '', array_slice($previewFeatures, 0, 3))) as $tag)<span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-medium text-blue-100/55">{{ $tag }}</span>@endforeach</div>
                                                            </div>
                                                            @elseif ($index === 3)
                                                            <div class="mt-auto grid gap-5 pt-6 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                                                                <div>
                                                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200/60">{{ $item['panel_title'] ?? 'Connected operations' }}</p>
                                                                    <p class="mt-2 max-w-md text-sm leading-6 text-blue-100/55">{{ $item['panel_subtitle'] ?? 'One flexible foundation for communication, storage, hosting, and future growth.' }}</p>
                                                                </div>
                                                                <div class="relative grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                                    <div class="absolute left-[12%] right-[12%] top-1/2 hidden h-px -translate-y-1/2 bg-linear-to-r from-cyan-300/0 via-cyan-300/30 to-cyan-300/0 sm:block"></div>
                                                                    @foreach (array_slice($previewFeatures, 0, 4) as $feature)
                                                                    <div class="relative rounded-[20px] border border-white/10 bg-slate-950/65 p-4 text-center shadow-[0_12px_28px_rgba(0,0,0,0.22)]"><span class="material-symbols-outlined text-[24px] text-cyan-200">{{ $feature['icon'] ?? 'cloud' }}</span>
                                                                        <p class="mt-2 text-xs font-semibold text-white">{{ $feature['title'] ?? '' }}</p>
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
                                    </div>
                                </div>
                                @endif

                                {{-- Timeline Preview --}}
                                @if ($activeTab === 'timeline')
                                <div class="relative h-[560px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="py-20">
                                            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="text-center mb-12">
                                                    <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">{{ $timeline['section_title'] ?? '' }} <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">{{ $timeline['highlighted_title'] ?? '' }}</span></h2>
                                                    @if (filled($timeline['subtitle'] ?? null))<p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">{{ $timeline['subtitle'] }}</p>@endif
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute left-5 top-0 h-full w-px bg-gradient-to-b from-cyan-300/0 via-cyan-300/25 to-cyan-300/0 sm:left-1/2 sm:-translate-x-1/2"></div>
                                                    <div class="space-y-6">
                                                        @foreach ($timeline['items'] ?? [] as $i => $item)
                                                        <div @class(['timeline-card sm:mr-auto sm:max-w-[48%]'=> $i % 2 === 0, 'timeline-card sm:ml-auto sm:max-w-[48%]' => $i % 2 !== 0])><div class="timeline-dot"></div>
                                                            <p class="timeline-year">{{ $item['year'] }}</p>
                                                            <h3 class="timeline-title">{{ $item['title'] }}</h3>
                                                            <p class="timeline-text">{{ $item['description'] }}</p>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Leadership Preview --}}
                                @if ($activeTab === 'leadership')
                                <div class="relative h-[430px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="pointer-events-none absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="py-20">
                                            <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="about-panel">
                                                    <div class="grid gap-10 lg:grid-cols-[300px_1fr] items-center">
                                                        <div class="rounded-[30px] border border-white/10 bg-white/6 p-4 backdrop-blur-2xl"><img src="{{ $this->leadershipPreviewImage() }}" class="h-[340px] w-full rounded-[24px] object-cover" alt="CEO"></div>
                                                        <div>
                                                            <p class="text-xs uppercase tracking-[0.22em] text-blue-100/45">{{ $leadership['badge'] ?? '' }}</p>
                                                            <h2 class="mt-4 text-3xl font-bold sm:text-4xl">{{ $leadership['title'] ?? '' }}</h2>
                                                            @foreach ($leadership['paragraphs'] ?? [] as $i => $paragraph)<p @class(['mt-5 about-text'=> $i === 0, 'mt-4 about-text' => $i > 0])>{{ $paragraph }}</p>@endforeach
                                                            <div class="mt-6">
                                                                <h3 class="text-lg font-semibold text-white">{{ $leadership['name'] ?? '' }}</h3>
                                                                <p class="mt-1 text-sm text-blue-100/55">{{ $leadership['role'] ?? '' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif

                                {{-- Experts Preview --}}
                                @if ($activeTab === 'experts')
                                @php $previewExperts = array_values($experts['items'] ?? []); @endphp
                                <div class="relative h-[500px] overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                                    <div class="absolute left-0 top-0 w-[960px] origin-top-left scale-[0.48]">
                                        <section class="relative overflow-hidden py-20">
                                            <div class="pointer-events-none absolute inset-0">
                                                <div class="absolute left-1/2 top-1/3 h-72 w-72 -translate-x-1/2 rounded-full bg-blue-500/8 blur-3xl"></div>
                                            </div>
                                            <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
                                                <div class="mx-auto mb-12 max-w-3xl text-center">
                                                    <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl">{{ $experts['section_title'] ?? '' }} <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">{{ $experts['highlighted_title'] ?? '' }}</span></h2>
                                                    @if (filled($experts['subtitle'] ?? null))<p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 sm:text-base">{{ $experts['subtitle'] }}</p>@endif
                                                </div>
                                                @if (count($previewExperts))
                                                <div x-data="{ current: 0, total: {{ count($previewExperts) }}, get max() { return Math.max(0, this.total - 4) }, next() { this.current = this.current >= this.max ? 0 : this.current + 1 }, previous() { this.current = this.current <= 0 ? this.max : this.current - 1 } }" class="relative">
                                                    <div class="overflow-hidden px-1">
                                                        <div class="flex transition-transform duration-500 ease-out" :style="'transform: translateX(-' + (current * 25) + '%)'">
                                                            @foreach ($previewExperts as $index => $member)
                                                            <div class="w-1/4 shrink-0 px-2.5">
                                                                <article class="team-card h-full"><img src="{{ $this->expertPreviewImage($index) }}" class="team-img" alt="{{ $member['name'] ?? 'Team member' }}">
                                                                    <h3 class="team-name">{{ $member['name'] ?? '' }}</h3>
                                                                    <p class="team-role">{{ $member['role'] ?? '' }}</p>
                                                                </article>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @if (count($previewExperts) > 4)
                                                    <button type="button" @click="previous()" class="absolute left-0 top-1/2 z-10 flex h-11 w-11 -translate-x-2 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-slate-950/75 text-white shadow-xl backdrop-blur-xl"><span class="material-symbols-outlined">arrow_back</span></button>
                                                    <button type="button" @click="next()" class="absolute right-0 top-1/2 z-10 flex h-11 w-11 translate-x-2 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-slate-950/75 text-white shadow-xl backdrop-blur-xl"><span class="material-symbols-outlined">arrow_forward</span></button>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="mt-6">
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="save">Save Changes</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>