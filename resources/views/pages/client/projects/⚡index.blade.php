<?php

use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects | Techwave')] class extends Component {
    public int $perPage = 12;

    public function loadMore(): void
    {
        $this->perPage += 12;
    }

    public function getProjectsProperty()
    {
        return Project::query()->with('category')->where('is_active', true)->latest('completed_at')->latest()->limit($this->perPage)->get();
    }

    public function getTotalProjectsProperty(): int
    {
        return Project::query()->where('is_active', true)->count();
    }

    public function projectImage(Project $project): string
    {
        if ($project->thumbnail) {
            if (str_starts_with($project->thumbnail, 'http://') || str_starts_with($project->thumbnail, 'https://')) {
                return $project->thumbnail;
            }

            return asset('storage/' . $project->thumbnail);
        }

        return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80';
    }

    public function projectType(Project $project): string
    {
        return $project->project_type ?: $project->category?->name ?: 'Project';
    }

    public function projectTechnologies(Project $project): array
    {
        if (empty($project->technologies)) {
            return [];
        }

        return collect($project->technologies)
            ->take(3)
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['title'] ?? ($item['name'] ?? ($item['text'] ?? null));
                }

                return $item;
            })
            ->filter()
            ->values()
            ->toArray();
    }
};
?>

<div class="relative text-white">

    <!-- Main Projects -->
    <section id="project-list" class="relative overflow-hidden py-20 sm:py-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center lg:mb-18">
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Project Showcase
                </div>

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Real projects built for
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        performance, trust, and growth
                    </span>
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    Explore our latest digital solutions, business systems, IT infrastructure projects,
                    security implementations, and modern web experiences crafted for real business impact.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

                @forelse ($this->projects as $project)
                    <a href="{{ route('client.projects.details', $project->slug) }}" wire:navigate
                        class="group relative min-h-107.5 overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-blue-950/20 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-300/30 hover:shadow-cyan-950/30">

                        <img src="{{ $this->projectImage($project) }}" alt="{{ $project->title }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">

                        <div class="absolute inset-0 bg-linear-to-t from-slate-950 via-slate-950/75 to-blue-950/20">
                        </div>
                        <div class="absolute inset-0 bg-linear-to-br from-cyan-500/20 via-transparent to-blue-700/20">
                        </div>

                        <div class="relative z-10 flex h-full min-h-107.5 flex-col justify-between p-6">
                            <div class="flex items-start justify-between gap-4">
                                <span
                                    class="inline-flex items-center rounded-full border border-white/10 bg-slate-950/30 px-3 py-1 text-xs font-semibold text-cyan-100 backdrop-blur-md">
                                    {{ $this->projectType($project) }}
                                </span>

                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-slate-950/30 text-cyan-200 backdrop-blur-md">
                                    <span class="material-symbols-outlined">open_in_new</span>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-2xl font-bold text-white">
                                    {{ $project->title }}
                                </h3>

                                <p class="mt-3 text-sm leading-7 text-blue-100/75">
                                    {{ Str::limit($project->short_description, 145) }}
                                </p>

                                @if (!empty($this->projectTechnologies($project)))
                                    <ul class="mt-6 space-y-3 text-sm text-blue-50/85">
                                        @foreach ($this->projectTechnologies($project) as $technology)
                                            <li class="service-bullet">{{ $technology }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                    <div class="inline-flex items-center gap-2 text-sm font-semibold text-cyan-100">
                                        View Project
                                        <span
                                            class="material-symbols-outlined text-[18px] transition-transform duration-300 group-hover:translate-x-1">
                                            arrow_forward
                                        </span>
                                    </div>

                                    @if ($project->completed_at)
                                        <span class="text-xs font-medium text-blue-100/55">
                                            {{ $project->completed_at->format('M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-3xl border border-white/10 bg-white/5 p-10 text-center">
                        <h3 class="text-2xl font-bold text-white">No projects found</h3>
                        <p class="mt-3 text-sm text-blue-100/70">
                            Please add active projects from your admin panel.
                        </p>
                    </div>
                @endforelse

            </div>

            @if ($this->projects->count() < $this->totalProjects)
                <div class="mt-12 flex justify-center">
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border border-white/10 bg-white/8 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/12 disabled:cursor-not-allowed disabled:opacity-60">

                        <span wire:loading.remove wire:target="loadMore">
                            Load More Projects
                        </span>

                        <span wire:loading wire:target="loadMore">
                            Loading...
                        </span>

                        <span wire:loading.remove wire:target="loadMore" class="material-symbols-outlined text-[18px]">
                            expand_more
                        </span>
                    </button>
                </div>
            @endif
        </div>
    </section>

    <!-- Project Approach -->
    <section class="relative overflow-hidden py-20 sm:py-24">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[8%] top-10 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[10%] bottom-8 h-52 w-52 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="mb-14 text-center lg:mb-18">
                <div
                    class="mx-auto mb-5 inline-flex items-center justify-center gap-2 rounded-full glass-chip px-4 py-2 text-xs sm:text-sm text-blue-100/85">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                    Project Method
                </div>

                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Every build follows a
                    <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                        clear delivery framework
                    </span>
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/70 sm:text-base">
                    We combine planning, design, development, testing, and post-launch improvement to deliver
                    projects that feel polished, reliable, and business-ready.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="why-premium-card">
                    <div class="why-premium-icon bg-cyan-500/15 text-cyan-200">
                        <span class="material-symbols-outlined text-3xl">fact_check</span>
                    </div>

                    <h3 class="mt-6 text-xl font-bold text-white">Requirement Mapping</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        We understand business goals, users, workflows, risks, and technical expectations before
                        execution.
                    </p>
                </div>

                <div class="why-premium-card why-premium-card-featured">
                    <div class="why-premium-icon bg-blue-500/15 text-blue-200">
                        <span class="material-symbols-outlined text-3xl">design_services</span>
                    </div>

                    <h3 class="mt-6 text-xl font-bold text-white">Design & Architecture</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        We create clean UI, structured user flows, and scalable technical architecture for long-term
                        use.
                    </p>
                </div>

                <div class="why-premium-card">
                    <div class="why-premium-icon bg-sky-500/15 text-sky-200">
                        <span class="material-symbols-outlined text-3xl">deployed_code</span>
                    </div>

                    <h3 class="mt-6 text-xl font-bold text-white">Build & Validate</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        We develop, test, optimize, and refine each project so it performs smoothly in real conditions.
                    </p>
                </div>

                <div class="why-premium-card">
                    <div class="why-premium-icon bg-violet-500/15 text-violet-200">
                        <span class="material-symbols-outlined text-3xl">rocket_launch</span>
                    </div>

                    <h3 class="mt-6 text-xl font-bold text-white">Launch & Improve</h3>
                    <p class="mt-3 text-sm leading-7 text-blue-100/68">
                        We support deployment, monitor feedback, and help improve the project after launch.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
