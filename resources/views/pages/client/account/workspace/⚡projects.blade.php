<?php

use App\Enums\ProjectStatus;
use App\Models\WorkspaceProject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Projects')] class extends Component {
    public function projects()
    {
        return WorkspaceProject::query()
            ->where('client_id', auth()->id())
            ->with(['creator', 'updates.author'])
            ->withCount(['tasks', 'updates'])
            ->get()
            ->sortByDesc('updated_at')
            ->values();
    }

    public function statusBadge(string $status): string
    {
        return match ($status) {
            ProjectStatus::IN_PROGRESS->value => 'bg-amber-400/15 text-amber-200 border-amber-300/20',
            ProjectStatus::COMPLETED->value => 'bg-emerald-400/15 text-emerald-200 border-emerald-300/20',
            ProjectStatus::CANCELLED->value => 'bg-red-400/15 text-red-200 border-red-300/20',
            ProjectStatus::PLANNING->value => 'bg-blue-400/15 text-blue-200 border-blue-300/20',
            default => 'bg-slate-400/15 text-slate-200 border-slate-300/20',
        };
    }

    public function formatDate($date): string
    {
        if (! $date) {
            return 'N/A';
        }

        return Carbon::parse($date)->format('M d, Y');
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                                <span class="material-symbols-outlined text-2xl">folder_managed</span>
                            </div>

                            <div>
                                <h1 class="text-2xl font-bold text-white sm:text-3xl">
                                    My Projects
                                </h1>

                                <p class="mt-1 text-sm text-blue-100/50">
                                    Track the progress of your projects with us.
                                </p>
                            </div>
                        </div>
                    </div>

                    @forelse ($this->projects() as $project)

                    <a
                        wire:key="ws-project-{{ $project->id }}"
                        href="{{ route('account.workspace-project.activity', $project) }}"
                        wire:navigate
                        class="mb-4 block rounded-2xl border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl transition hover:-translate-y-0.5 hover:bg-white/10">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/10 shadow-inner"
                                    style="background: {{ $project->icon_color ?: '#4f46e5' }}22;">
                                    <span class="material-symbols-outlined text-xl" style="color: {{ $project->icon_color ?: '#4f46e5' }}">
                                        {{ $project->icon ?: 'folder' }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-bold text-white">
                                        {{ $project->name }}
                                    </h2>

                                    <p class="mt-0.5 text-sm text-blue-100/55">
                                        {{ Str::limit($project->description ?: 'No description provided.', 90) }}
                                    </p>
                                </div>
                            </div>

                            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium {{ $this->statusBadge($project->status->value) }}">
                                {{ $project->status->label() }}
                            </span>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 pt-4 text-xs text-blue-100/55">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-cyan-200/80">
                                    {{ $project->priority !== null && $project->priority->value !== 'none' ? 'flag' : 'tune' }}
                                </span>
                                {{ $project->priority?->label() ?? 'No Priority' }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-cyan-200/80">checklist</span>
                                {{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-cyan-200/80">update</span>
                                {{ $project->updates_count }} activity {{ Str::plural('entry', $project->updates_count) }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-cyan-200/80">schedule</span>
                                Updated {{ $this->formatDate($project->updated_at) }}
                            </span>
                        </div>
                    </a>

                    @empty

                    <div class="rounded-[28px] border border-dashed border-white/15 bg-white/5 p-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-cyan-200">
                            <span class="material-symbols-outlined text-3xl">folder_open</span>
                        </div>

                        <p class="mt-4 font-semibold text-white">
                            No projects yet
                        </p>

                        <p class="mx-auto mt-1 max-w-md text-sm text-blue-100/50">
                            When a project is assigned to you, its progress and activity will appear here.
                        </p>
                    </div>

                    @endforelse

                </div>
            </div>
        </div>
    </div>
</div>