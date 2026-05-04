<?php

use App\Models\Category;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Completed Projects')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $featured = 'all';
    public string $categoryFilter = 'all';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFeatured(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function categories()
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function projects()
    {
        $search = trim($this->search);

        return Project::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_place', 'like', '%' . $search . '%')
                        ->orWhere('project_type', 'like', '%' . $search . '%')
                        ->orWhere('short_description', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('is_active', $this->status === 'active');
            })
            ->when($this->featured !== 'all', function ($query) {
                $query->where('is_featured', $this->featured === 'featured');
            })
            ->when($this->categoryFilter !== 'all', function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function toggleStatus(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $project->update([
            'is_active' => !$project->is_active,
        ]);

        $this->dispatch('toast', message: 'Project status updated successfully.', type: 'success');
    }

    public function toggleFeatured(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $project->update([
            'is_featured' => !$project->is_featured,
        ]);

        $this->dispatch('toast', message: $project->fresh()->is_featured ? 'Project marked as featured.' : 'Project removed from featured.', type: 'success');
    }

    public function delete(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        if ($project->thumbnail && Storage::disk('public')->exists($project->thumbnail)) {
            Storage::disk('public')->delete($project->thumbnail);
        }

        $project->delete();

        $this->dispatch('toast', message: 'Project deleted successfully.', type: 'success');
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Completed Projects
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage completed projects, portfolio showcase, case studies and client work.
                </p>
            </div>
        </div>


        <div class="flex w-full flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <!-- Filters -->
    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 lg:max-w-5xl">
        <!-- Search -->
        <div class="relative sm:col-span-2 xl:col-span-1">
            <span
                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                search
            </span>

            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Search project..."
                class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10"
            />
        </div>

        <!-- Category -->
        <div class="relative">
            <select
                wire:model.live="categoryFilter"
                class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
            >
                <option value="all">All Categories</option>

                @foreach ($this->categories() as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <span
                class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                expand_more
            </span>
        </div>

        <!-- Status -->
        <div class="relative">
            <select
                wire:model.live="status"
                class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
            >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <span
                class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                expand_more
            </span>
        </div>

        <!-- Featured -->
        <div class="relative">
            <select
                wire:model.live="featured"
                class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
            >
                <option value="all">All Projects</option>
                <option value="featured">Featured</option>
                <option value="normal">Normal</option>
            </select>

            <span
                class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                expand_more
            </span>
        </div>
    </div>

    <!-- Action -->
    <a
        href="{{ route('admin.projects.create') }}"
        wire:navigate
        class="flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] sm:w-auto lg:self-start"
    >
        <span class="material-symbols-outlined text-lg">add</span>
        Create New
    </a>
</div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Project</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Category</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Client</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Featured</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Completed</th>
                            <th
                                class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->projects() as $project)
                            <tr wire:key="project-{{ $project->id }}" class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-14 w-16 overflow-hidden rounded-xl bg-slate-100">
                                            @if ($project->thumbnail)
                                                <img src="{{ Storage::url($project->thumbnail) }}"
                                                    alt="{{ $project->title }}" class="h-full w-full object-cover" />
                                            @else
                                                <div
                                                    class="flex h-full w-full items-center justify-center bg-primary/10 text-primary">
                                                    <span
                                                        class="material-symbols-outlined text-[24px]">workspaces</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <span class="block text-label-md font-label-md text-on-surface">
                                                {{ $project->title }}
                                            </span>

                                            <span
                                                class="block max-w-sm truncate text-body-sm font-body-sm text-secondary">
                                                {{ $project->short_description }}
                                            </span>

                                            <span class="mt-1 block font-mono text-[11px] text-slate-400">
                                                {{ $project->slug }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($project->category)
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold uppercase text-blue-700">
                                            <span class="material-symbols-outlined text-[15px]">
                                                {{ $project->category->icon ?: 'category' }}
                                            </span>
                                            {{ $project->category->name }}
                                        </span>
                                    @else
                                        <span class="text-body-sm text-secondary">No category</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-body-sm text-on-surface">
                                        {{ $project->client_name ?: 'N/A' }}
                                    </span>

                                    @if ($project->client_place)
                                        <span class="block text-[11px] text-slate-500">
                                            {{ $project->client_place }}
                                        </span>
                                    @endif

                                    <span class="block text-[11px] uppercase tracking-wider text-slate-400">
                                        {{ $project->project_type ?: 'Project' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <button type="button" wire:click="toggleFeatured({{ $project->id }})"
                                        @class([
                                            'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-label-sm uppercase transition',
                                            'bg-amber-100 text-amber-700 hover:bg-amber-200' => $project->is_featured,
                                            'bg-slate-100 text-slate-500 hover:bg-slate-200' => !$project->is_featured,
                                        ])>
                                        <span class="material-symbols-outlined text-[13px]">
                                            {{ $project->is_featured ? 'stars' : 'star' }}
                                        </span>

                                        {{ $project->is_featured ? 'Featured' : 'Normal' }}
                                    </button>
                                </td>

                                <td class="px-6 py-4">
                                    <button type="button" wire:click="toggleStatus({{ $project->id }})"
                                        class="flex items-center gap-2">
                                        @if ($project->is_active)
                                            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                                            <span class="text-body-md font-body-md text-on-surface">Active</span>
                                        @else
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                            <span class="text-body-md font-body-md text-on-surface">Inactive</span>
                                        @endif
                                    </button>
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                                    {{ $project->completed_at?->format('M d, Y') ?? 'N/A' }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                            class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                            <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                                Edit
                                            </a>

                                            <button type="button" wire:click="toggleFeatured({{ $project->id }})"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">
                                                    {{ $project->is_featured ? 'star_half' : 'stars' }}
                                                </span>
                                                {{ $project->is_featured ? 'Remove Featured' : 'Make Featured' }}
                                            </button>

                                            <button type="button" wire:click="toggleStatus({{ $project->id }})"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                                <span class="material-symbols-outlined text-[18px]">
                                                    {{ $project->is_active ? 'visibility_off' : 'visibility' }}
                                                </span>
                                                {{ $project->is_active ? 'Make Inactive' : 'Make Active' }}
                                            </button>

                                            <button type="button" wire:click="delete({{ $project->id }})"
                                                wire:confirm="Are you sure you want to delete this project?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">workspaces</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No projects found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Create your first completed project to showcase your work.
                                        </p>

                                        <a href="{{ route('admin.projects.create') }}" wire:navigate
                                            class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90">
                                            Create Project
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">Per page</span>

                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->projects()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
