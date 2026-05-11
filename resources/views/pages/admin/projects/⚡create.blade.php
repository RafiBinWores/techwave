<?php

use App\Models\Category;
use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Create Project')] class extends Component {
    use WithFileUploads;

    public ?int $category_id = null;

    public string $title = '';
    public string $slug = '';

    public string $client_name = '';
    public string $client_place = '';
    public string $project_type = '';

    public string $short_description = '';
    public string $overview = '';

    public string $live_url = '';
    public string $case_study_url = '';
    public string $completed_at = '';

    public string $meta_title = '';
    public string $meta_description = '';
    public string $meta_keywords = '';

    public bool $is_active = true;
    public bool $is_featured = false;

    public $thumbnail = null;

    public array $technologies = [];
    public string $technology = '';

    protected function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],

            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:projects,slug'],

            'client_name' => ['nullable', 'string', 'max:180'],
            'client_place' => ['nullable', 'string', 'max:180'],
            'project_type' => ['nullable', 'string', 'max:120'],

            'short_description' => ['required', 'string', 'max:500'],
            'overview' => ['nullable', 'string'],

            'live_url' => ['nullable', 'url', 'max:255'],
            'case_study_url' => ['nullable', 'url', 'max:255'],
            'completed_at' => ['nullable', 'date'],

            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],

            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],

            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],

            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function messages(): array
    {
        return [
            'thumbnail.mimes' => 'Thumbnail must be JPG, PNG, WEBP, or SVG.',
            'live_url.url' => 'Please enter a valid live project URL.',
            'case_study_url.url' => 'Please enter a valid case study URL.',
        ];
    }

    public function categories()
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function selectedCategory()
    {
        if (!$this->category_id) {
            return null;
        }

        return $this->categories()->firstWhere('id', (int) $this->category_id);
    }

    public function updatedTitle(): void
    {
        $this->slug = Str::slug($this->title);

        $this->validateOnly('title');

        if (filled($this->slug)) {
            $this->validateOnly('slug');
        }
    }

    public function updated($property): void
    {
        if ($property !== 'title') {
            $this->validateOnly($property);
        }
    }

    private function uniqueSlug(string $value): string
    {
        $slug = Str::slug($value ?: $this->title);
        $originalSlug = $slug;
        $counter = 1;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function addTechnology(): void
    {
        $technology = trim($this->technology);

        if ($technology === '') {
            $this->dispatch('toast', message: 'Please type a technology first.', type: 'warning');

            return;
        }

        if (!in_array($technology, $this->technologies, true)) {
            $this->technologies[] = $technology;
        }

        $this->technology = '';
    }

    public function removeTechnology(int $index): void
    {
        unset($this->technologies[$index]);

        $this->technologies = array_values($this->technologies);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $thumbnailPath = null;

        if ($this->thumbnail) {
            $thumbnailPath = $this->thumbnail->store('projects/thumbnails', 'public');
        }

        Project::create([
            'category_id' => $validated['category_id'] ?: null,

            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($this->slug ?: $validated['title']),

            'client_name' => $validated['client_name'] ?: null,
            'client_place' => $validated['client_place'] ?: null,
            'project_type' => $validated['project_type'] ?: null,

            'thumbnail' => $thumbnailPath,
            'short_description' => $validated['short_description'],
            'overview' => $validated['overview'] ?: null,

            'technologies' => array_values(array_filter($validated['technologies'] ?? [])),

            'live_url' => $validated['live_url'] ?: null,
            'case_study_url' => $validated['case_study_url'] ?: null,
            'completed_at' => $validated['completed_at'] ?: null,

            'meta_title' => $validated['meta_title'] ?: null,
            'meta_description' => $validated['meta_description'] ?: null,
            'meta_keywords' => $validated['meta_keywords'] ?: null,

            'is_active' => $validated['is_active'],
            'is_featured' => $validated['is_featured'],
        ]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Project created successfully.',
        ]);

        $this->redirectRoute('admin.projects.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset(['category_id', 'title', 'slug', 'client_name', 'client_place', 'project_type', 'short_description', 'overview', 'live_url', 'case_study_url', 'completed_at', 'meta_title', 'meta_description', 'meta_keywords', 'thumbnail', 'technologies', 'technology']);

        $this->is_active = true;
        $this->is_featured = false;

        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes discarded.', type: 'info');
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Create Project</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Add a completed project to showcase your work, client results and case studies.
            </p>
        </div>

        <a href="{{ route('admin.projects.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Projects
        </a>
    </div>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">workspaces</span>
                        Project Information
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Project Category</label>

                            <div class="relative">
                                <select wire:model.live="category_id"
                                    class="w-full appearance-none rounded border border-outline-variant bg-white px-4 py-2.5 pr-10 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10">
                                    <option value="">Select a category</option>

                                    @foreach ($this->categories() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>

                                <span
                                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @error('category_id')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Project Title</label>

                            <input type="text" wire:model.live="title" placeholder="Example: TechWave SaaS Website"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('title')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            <p class="text-xs text-secondary">
                                Slug:
                                <span class="font-mono text-primary">{{ $slug ?: 'project-slug' }}</span>
                            </p>

                            @error('slug')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Project Type</label>

                            <input type="text" wire:model.live="project_type"
                                placeholder="Website, SaaS, ERP, E-commerce"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('project_type')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Client Name</label>

                            <input type="text" wire:model.live="client_name" placeholder="Example: Happy Potato"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('client_name')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Client Place</label>

                            <input type="text" wire:model.live="client_place" placeholder="Example: Gulshan, Dhaka"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('client_place')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Completed Date</label>

                            <input type="date" wire:model.live="completed_at"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('completed_at')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Short Description</label>

                            <textarea wire:model.live="short_description" placeholder="Briefly explain the project result..." rows="2"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"></textarea>

                            @error('short_description')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Project Overview</label>

                            <div wire:ignore x-data="{
                                quill: null,
                                value: @entangle('overview'),
                            
                                init() {
                                    this.quill = new Quill(this.$refs.editor, {
                                        theme: 'snow',
                                        placeholder: 'Write project details, challenges, solution and results...',
                                        modules: {
                                            toolbar: [
                                                [{ header: [2, 3, false] }],
                                                [{ 'font': [] }],
                                                ['bold', 'italic', 'underline', 'strike'],
                                                [{ 'color': [] }, { 'background': [] }],
                                                [{ list: 'ordered' }, { list: 'bullet' }],
                                                [{ 'align': [] }],
                                                ['blockquote', 'code-block'],
                                                ['link'],
                                                ['clean']
                                            ]
                                        }
                                    });
                            
                                    if (this.value) {
                                        this.quill.root.innerHTML = this.value;
                                    }
                            
                                    this.quill.on('text-change', () => {
                                        this.value = this.quill.root.innerHTML;
                                    });
                                }
                            }"
                                class="overflow-hidden rounded border border-outline-variant bg-white">
                                <div x-ref="editor" class="min-h-45 px-4 py-2.5 font-body-md"></div>
                            </div>

                            @error('overview')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">code_blocks</span>
                        Technologies Used
                    </h3>

                    <div class="mb-4 flex gap-3">
                        <input wire:model.live="technology" wire:keydown.enter.prevent="addTechnology"
                            class="flex-1 rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10"
                            placeholder="Laravel, React, Tailwind, Alpine" type="text" />

                        <button type="button" wire:click="addTechnology"
                            class="flex items-center gap-1 rounded border border-dashed border-[#0F52BA] px-4 py-2.5 text-sm font-semibold text-[#0F52BA] transition-colors hover:bg-primary/5">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Add
                        </button>
                    </div>

                    <div class="flex min-h-15 flex-wrap gap-2 rounded-lg border border-slate-100 bg-surface p-4">
                        @forelse ($technologies as $index => $item)
                            <div wire:key="technology-{{ $index }}"
                                class="flex items-center gap-2 rounded-full border border-outline-variant bg-white px-3 py-1.5 shadow-sm">
                                <span class="text-sm font-body-md">{{ $item }}</span>

                                <button type="button" wire:click="removeTechnology({{ $index }})"
                                    class="material-symbols-outlined text-sm text-outline hover:text-error">
                                    close
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-secondary">No technologies added yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">link</span>
                        Project Links
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Live URL</label>

                            <input type="url" wire:model.live="live_url" placeholder="https://example.com"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('live_url')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Case Study URL</label>

                            <input type="url" wire:model.live="case_study_url"
                                placeholder="https://example.com/case-study"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-[#0F52BA] focus:ring-opacity-10" />

                            @error('case_study_url')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">travel_explore</span>
                        SEO Meta Information
                    </h3>

                    <div class="grid grid-cols-1 gap-6">
                        <input wire:model.live="meta_title"
                            class="w-full rounded-lg border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            placeholder="Meta title" type="text" />

                        <textarea wire:model.live="meta_description"
                            class="w-full rounded-lg border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            placeholder="Meta description" rows="3"></textarea>

                        <textarea wire:model.live="meta_keywords"
                            class="w-full rounded-lg border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10"
                            placeholder="Meta keywords" rows="2"></textarea>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" wire:click="discard" wire:loading.attr="disabled"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                            Discard Changes
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Save Project</span>

                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-h3 font-h2">Project Thumbnail</h3>

                    <label for="thumbnail"
                        class="flex h-64 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-outline-variant bg-surface transition-colors hover:bg-surface-container">
                        @if ($thumbnail)
                            <img src="{{ $thumbnail->temporaryUrl() }}" alt="Project preview"
                                class="h-full w-full object-cover" />
                        @else
                            <span class="material-symbols-outlined mb-2 text-5xl text-outline">
                                add_photo_alternate
                            </span>

                            <p class="text-sm font-body-sm text-outline">
                                Click to upload project thumbnail
                            </p>

                            <p class="mt-1 text-xs font-bold uppercase tracking-widest text-outline-variant">
                                PNG, JPG, WEBP, SVG up to 5MB
                            </p>
                        @endif
                    </label>

                    <input id="thumbnail" type="file" wire:model="thumbnail"
                        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden" />

                    <div wire:loading wire:target="thumbnail" class="mt-3 text-sm text-primary">
                        Uploading thumbnail...
                    </div>

                    @error('thumbnail')
                        <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-label-sm font-label-sm uppercase tracking-widest text-secondary">
                        Project Settings
                    </h3>

                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <div>
                            <span class="block text-label-md font-label-md text-on-surface">
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="text-xs text-secondary">Show or hide this project publicly.</span>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="is_active" class="peer sr-only" />
                            <div
                                class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white">
                            </div>
                        </label>
                    </div>

                    <div
                        class="mt-3 flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50/50 p-3">
                        <div>
                            <span class="block text-label-md font-label-md text-on-surface">
                                Featured Project
                            </span>
                            <span class="text-xs text-secondary">Highlight this project on homepage.</span>
                        </div>

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="is_featured" class="peer sr-only" />
                            <div
                                class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-amber-500 peer-checked:after:translate-x-full peer-checked:after:border-white">
                            </div>
                        </label>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Quick Preview</h3>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            @if ($this->selectedCategory())
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    <span class="material-symbols-outlined text-[14px]">
                                        {{ $this->selectedCategory()->icon ?: 'category' }}
                                    </span>
                                    {{ $this->selectedCategory()->name }}
                                </span>
                            @endif

                            @if ($is_featured)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    <span class="material-symbols-outlined text-[14px]">stars</span>
                                    Featured
                                </span>
                            @endif

                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-700' => $is_active,
                                'bg-red-100 text-red-700' => !$is_active,
                            ])>
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <h4 class="text-lg font-semibold text-on-surface">
                            {{ $title ?: 'Project Title' }}
                        </h4>

                        <p class="mt-1 font-mono text-xs text-primary">
                            {{ $slug ?: 'project-slug' }}
                        </p>

                        <div class="mt-3 space-y-1 rounded-xl bg-white p-3 text-xs text-slate-500 shadow-sm">
                            <p>
                                <span class="font-semibold text-slate-700">Client:</span>
                                {{ $client_name ?: 'Client Name' }}
                            </p>
                            <p>
                                <span class="font-semibold text-slate-700">Place:</span>
                                {{ $client_place ?: 'Client Place' }}
                            </p>
                            <p>
                                <span class="font-semibold text-slate-700">Type:</span>
                                {{ $project_type ?: 'Project Type' }}
                            </p>
                        </div>

                        <p class="mt-3 text-sm leading-relaxed text-secondary">
                            {{ $short_description ?: 'Short project description will appear here.' }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($technologies as $previewTechnology)
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-sm">
                                    {{ $previewTechnology }}
                                </span>
                            @empty
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-400 shadow-sm">
                                    No technology yet
                                </span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
