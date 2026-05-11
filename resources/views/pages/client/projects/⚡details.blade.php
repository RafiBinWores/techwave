<?php

use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public Project $project;

    public SiteSetting $siteSetting;

    public $otherProjects;

    public function mount(string $slug): void
    {
        $this->siteSetting = SiteSetting::current();

        $this->project = Project::query()->with('category')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $this->otherProjects = Project::query()->with('category')->where('is_active', true)->where('id', '!=', $this->project->id)->latest('completed_at')->latest()->limit(3)->get();
    }

    public function title(): string
    {
        $title = $this->project->meta_title ?: $this->project->title ?: 'Project Details';

        return $title . ' | ' . ($this->siteSetting->site_name ?: config('app.name'));
    }

    public function projectImage(?Project $project = null): string
    {
        $project = $project ?: $this->project;

        if ($project->thumbnail) {
            if (str_starts_with($project->thumbnail, 'http://') || str_starts_with($project->thumbnail, 'https://')) {
                return $project->thumbnail;
            }

            return asset('storage/' . $project->thumbnail);
        }

        return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1400&q=80';
    }

    public function projectType(?Project $project = null): string
    {
        $project = $project ?: $this->project;

        return $project->project_type ?: $project->category?->name ?: 'Project';
    }

    public function projectTechnologies(?Project $project = null): array
    {
        $project = $project ?: $this->project;

        if (empty($project->technologies)) {
            return [];
        }

        return collect($project->technologies)
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
    @push('meta')
        <meta name="title"
            content="{{ ($project->meta_title ?: $project->title) . ' | ' . ($siteSetting->site_name ?: config('app.name')) }}">
        <meta name="description" content="{{ $project->meta_description ?: $project->short_description }}">
        <meta name="keywords" content="{{ $project->meta_keywords }}">
    @endpush

    <!-- Hero -->
    <section class="relative overflow-hidden py-18 sm:py-22 lg:py-26">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute left-[8%] top-10 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="absolute right-[10%] top-16 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs sm:text-sm text-blue-100/85 backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        {{ $this->projectType() }}
                    </div>

                    <h1
                        class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl">
                        {{ $project->title }}
                    </h1>

                    @if ($project->short_description)
                        <p class="mt-6 max-w-2xl text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8">
                            {{ $project->short_description }}
                        </p>
                    @endif

                    {{-- <div class="mt-8 flex flex-wrap gap-3">
                        @if ($project->client_name)
                            <span
                                class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">
                                {{ $project->client_name }}
                            </span>
                        @endif

                        @if ($project->client_place)
                            <span
                                class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">
                                {{ $project->client_place }}
                            </span>
                        @endif

                        @if ($project->completed_at)
                            <span
                                class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">
                                Completed {{ $project->completed_at->format('M Y') }}
                            </span>
                        @endif
                    </div> --}}

                    @if (!empty($this->projectTechnologies()))
                        <div class="mt-6 flex flex-wrap gap-3">
                            @foreach ($this->projectTechnologies() as $technology)
                                <span
                                    class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">
                                    {{ $technology }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-8 flex flex-wrap gap-3">
                        @if ($project->live_url)
                            <a href="{{ $project->live_url }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                                View Live Project
                                <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                            </a>
                        @endif

                        @if ($project->case_study_url)
                            <a href="{{ $project->case_study_url }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-6 py-3 text-sm font-semibold text-white backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/12">
                                View Case Study
                                <span class="material-symbols-outlined text-[18px]">article</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="relative overflow-hidden rounded-[30px] border border-white/15 bg-white/8 p-3 shadow-[0_25px_80px_rgba(0,0,0,0.24)] backdrop-blur-2xl">
                        <div class="absolute left-8 top-8 h-28 w-28 rounded-full bg-cyan-400/12 blur-3xl"></div>
                        <div class="absolute bottom-8 right-8 h-32 w-32 rounded-full bg-blue-500/12 blur-3xl"></div>

                        <div class="overflow-hidden rounded-3xl border border-white/10">
                            <img src="{{ $this->projectImage() }}" alt="{{ $project->title }}"
                                class="h-80 w-full object-cover sm:h-100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Details -->
    <section class="relative overflow-hidden pb-20 sm:pb-24">
        <div class="mx-auto max-w-350 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_380px] xl:grid-cols-[1fr_420px]">
                <!-- Left Content -->
                <div class="space-y-8">
                    @if ($project->overview)
                        <div class="service-detail-card">
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">Project Overview</h2>

                            <div
                                class="mt-5 text-sm leading-7 text-blue-100/72 sm:text-base sm:leading-8 [&_p]:mb-4 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-white [&_a]:text-cyan-200">
                                {!! $project->overview !!}
                            </div>
                        </div>
                    @endif

                    {{-- @if (!empty($this->projectTechnologies()))
                        <div class="service-detail-card">
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">Technologies Used</h2>

                            <div class="mt-6 flex flex-wrap gap-3">
                                @foreach ($this->projectTechnologies() as $technology)
                                    <span
                                        class="rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm text-blue-100/80">
                                        {{ $technology }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif --}}

                    {{-- <div class="service-detail-card">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Project Summary</h2>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Project Type</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    {{ $this->projectType() }}
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Client</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    {{ $project->client_name ?: 'Confidential Client' }}
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Location</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    {{ $project->client_place ?: 'N/A' }}
                                </p>
                            </div>

                            <div class="benefit-box">
                                <h3 class="text-lg font-semibold text-white">Completed</h3>
                                <p class="mt-2 text-sm leading-6 text-blue-100/66">
                                    {{ $project->completed_at ? $project->completed_at->format('F d, Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div> --}}

                    @if ($otherProjects->count())
                        <div class="service-detail-card">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl font-bold text-white sm:text-3xl">More Projects</h2>
                                    <p class="mt-2 text-sm text-blue-100/66">Explore other work from our portfolio.</p>
                                </div>

                                <a href="{{ route('client.projects') }}" wire:navigate
                                    class="hidden sm:inline-flex items-center rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm font-medium text-white backdrop-blur-xl transition hover:bg-white/12">
                                    View All
                                </a>
                            </div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($otherProjects as $otherProject)
                                    <a href="{{ route('client.projects.details', $otherProject->slug) }}" wire:navigate
                                        class="other-service-card {{ $loop->last ? 'sm:col-span-2 xl:col-span-1' : '' }}">
                                        <div class="other-service-icon bg-cyan-500/15 text-cyan-200">
                                            <span class="material-symbols-outlined">
                                                open_in_new
                                            </span>
                                        </div>

                                        <h3 class="mt-4 text-lg font-semibold text-white">
                                            {{ $otherProject->title }}
                                        </h3>

                                        @if ($otherProject->short_description)
                                            <p class="mt-2 text-sm leading-6 text-blue-100/64">
                                                {{ Str::limit($otherProject->short_description, 90) }}
                                            </p>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Sidebar -->
                <aside class="space-y-6">
                    <div class="sidebar-service-card">
                        <h3 class="text-2xl font-bold text-white">Project Information</h3>

                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            A quick view of this project’s core details, client information, and available links.
                        </p>

                        <div class="mt-6 space-y-4">
                            <div class="contact-side-box">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Project Type</p>
                                <p class="mt-2 text-base font-semibold text-white">
                                    {{ $this->projectType() }}
                                </p>
                            </div>

                            <div class="contact-side-box">
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Client</p>
                                <p class="mt-2 text-base font-semibold text-white">
                                    {{ $project->client_name ?: 'Confidential Client' }}
                                </p>
                            </div>

                            @if ($project->client_place)
                                <div class="contact-side-box">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Client Location</p>
                                    <p class="mt-2 text-base font-semibold text-white">
                                        {{ $project->client_place }}
                                    </p>
                                </div>
                            @endif

                            @if ($project->completed_at)
                                <div class="contact-side-box">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Completed At</p>
                                    <p class="mt-2 text-base font-semibold text-white">
                                        {{ $project->completed_at->format('M d, Y') }}
                                    </p>
                                </div>
                            @endif

                            @if ($project->live_url)
                                <a href="{{ $project->live_url }}" target="_blank"
                                    class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-blue-500 to-sky-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5">
                                    Visit Live Project
                                </a>
                            @endif

                            @if ($project->case_study_url)
                                <a href="{{ $project->case_study_url }}" target="_blank"
                                    class="inline-flex w-full items-center justify-center rounded-full border border-white/10 bg-white/8 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-xl transition hover:-translate-y-0.5 hover:bg-white/12">
                                    View Case Study
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="sidebar-service-card">
                        <h3 class="text-2xl font-bold text-white">Need a Similar Project?</h3>

                        <p class="mt-3 text-sm leading-7 text-blue-100/68">
                            Share your idea with our team and we’ll help you plan the right solution.
                        </p>

                        <div class="mt-6 space-y-4">
                            @if ($siteSetting->phone)
                                <div class="contact-side-box">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Call Us</p>
                                    <p class="mt-2 text-base font-semibold text-white">
                                        {{ $siteSetting->phone }}
                                    </p>
                                </div>
                            @endif

                            @if ($siteSetting->email)
                                <div class="contact-side-box">
                                    <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">Email</p>
                                    <p class="mt-2 text-base font-semibold text-white">
                                        {{ $siteSetting->email }}
                                    </p>
                                </div>
                            @endif

                            @if ($siteSetting->whatsapp_url)
                                <a href="{{ $siteSetting->whatsapp_url }}" target="_blank"
                                    class="inline-flex w-full items-center justify-center rounded-full bg-linear-to-r from-emerald-500 to-green-400 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5">
                                    Chat on WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
