{{-- Project header --}}
<div class="border-b border-slate-200">
    <div class="flex flex-col gap-5 pb-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-primary"
                style="color: {{ $project->icon_color ?: '#4f46e5' }}">
                <span class="material-symbols-outlined text-[21px]">
                    {{ $project->icon ?: 'space_dashboard' }}
                </span>
            </div>

            <div class="min-w-0">
                <h1 class="truncate text-[22px] font-semibold leading-7 tracking-[-0.02em] text-slate-900">
                    {{ $project->name }}
                </h1>

                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    {{ $project->description ?: 'No project summary added yet.' }}
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($this->canEditProject())
            <button
                type="button"
                wire:click="openEditModal"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500  transition hover:bg-slate-50 hover:text-slate-800 cursor-pointer"
                title="Edit project"
                aria-label="Edit project">
                <span class="material-symbols-outlined text-[18px]">edit</span>
            </button>
            @endif
        </div>
    </div>

    {{-- Linear-style tabs --}}
    <div class="flex items-center gap-5 overflow-x-auto">
        <a
            href="{{ route('admin.workspace.projects.show.overview', $project) }}"
            wire:navigate
            class="relative flex h-10 shrink-0 cursor-pointer items-center gap-1.5 text-xs font-medium transition"
            @class([ 'text-slate-900'=> $active === 'overview',
            'text-slate-500 hover:text-slate-900' => $active !== 'overview', ])>
            Overview

            @if ($active === 'overview')
            <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-slate-900"></span>
            @endif
        </a>

        <a
            href="{{ route('admin.workspace.projects.show.issues', $project) }}"
            wire:navigate
            class="relative flex h-10 shrink-0 cursor-pointer items-center gap-1.5 text-xs font-medium transition"
            @class([ 'text-slate-900'=> $active === 'issues',
            'text-slate-500 hover:text-slate-900' => $active !== 'issues', ])>
            Issues

            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px]"
                @class([ 'text-slate-900'=> $active === 'issues',
                'text-slate-500' => $active !== 'issues', ])>
                {{ $project->tasks_count }}
            </span>

            @if ($active === 'issues')
            <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-slate-900"></span>
            @endif
        </a>

        <a
            href="{{ route('admin.workspace.projects.show.activity', $project) }}"
            wire:navigate
            class="relative flex h-10 shrink-0 cursor-pointer items-center gap-1.5 text-xs font-medium transition"
            @class([ 'text-slate-900'=> $active === 'activity',
            'text-slate-500 hover:text-slate-900' => $active !== 'activity', ])>
            Activity

            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px]"
                @class([ 'text-slate-900'=> $active === 'activity',
                'text-slate-500' => $active !== 'activity', ])>
                {{ $project->updates_count }}
            </span>

            @if ($active === 'activity')
            <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-slate-900"></span>
            @endif
        </a>
    </div>
</div>