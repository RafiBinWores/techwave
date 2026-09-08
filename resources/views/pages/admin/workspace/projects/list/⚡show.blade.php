<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\WorkspaceProject;
use App\Models\WorkspaceTask;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Project List')] class extends Component {
    use WithPagination;

    public WorkspaceProject $project;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $priorityFilter = 'all';
    public string $assigneeFilter = 'all';
    public int $perPage = 15;

    public function mount(WorkspaceProject $project): void
    {
        $this->project = $project->load([
            'members',
            'tasks.assignee',
            'tasks.labels',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAssigneeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function staff()
    {
        return $this->project->members;
    }

    public function tasks()
    {
        $search = trim($this->search);

        return WorkspaceTask::query()
            ->with(['assignee', 'labels'])
            ->where('project_id', $this->project->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('identifier', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->priorityFilter !== 'all', function ($query) {
                $query->where('priority', $this->priorityFilter);
            })
            ->when($this->assigneeFilter !== 'all', function ($query) {
                $query->where('assignee_id', $this->assigneeFilter);
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function toggleStatus(int $taskId): void
    {
        $task = WorkspaceTask::findOrFail($taskId);

        $nextStatus = match ($task->status) {
            TaskStatus::BACKLOG => TaskStatus::TODO,
            TaskStatus::TODO => TaskStatus::IN_PROGRESS,
            TaskStatus::IN_PROGRESS => TaskStatus::DONE,
            TaskStatus::DONE => TaskStatus::BACKLOG,
            TaskStatus::CANCELLED => TaskStatus::BACKLOG,
        };

        $task->update(['status' => $nextStatus]);

        $this->dispatch('toast', message: 'Task status updated to ' . $nextStatus->label() . '.', type: 'success');
    }

    public function deleteTask(int $taskId): void
    {
        $task = WorkspaceTask::findOrFail($taskId);
        $task->delete();

        $this->dispatch('toast', message: 'Task deleted successfully.', type: 'success');
    }

    public function statusBadgeClass(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::TODO => 'bg-blue-100 text-blue-700',
            TaskStatus::IN_PROGRESS => 'bg-amber-100 text-amber-700',
            TaskStatus::DONE => 'bg-emerald-100 text-emerald-700',
            TaskStatus::CANCELLED => 'bg-red-100 text-red-700',
            TaskStatus::BACKLOG => 'bg-slate-100 text-slate-700',
        };
    }

    public function priorityColor(TaskPriority $priority): string
    {
        return match ($priority) {
            TaskPriority::URGENT => 'text-red-500',
            TaskPriority::HIGH => 'text-orange-500',
            TaskPriority::MEDIUM => 'text-yellow-500',
            TaskPriority::LOW => 'text-blue-500',
            TaskPriority::NONE => 'text-slate-300',
        };
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <div class="mb-2">
                    <a href="{{ route('admin.workspace.projects.index') }}" wire:navigate
                        class="inline-flex items-center gap-1.5 text-body-sm font-body-sm text-secondary transition-colors hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        Back to Workspace
                    </a>
                </div>

                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    {{ $this->project->name }}
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Task list view — manage and track all project tasks.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.workspace.projects.show.overview', $this->project) }}" wire:navigate
                    class="flex items-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
                    <span class="material-symbols-outlined text-lg">dashboard</span>
                    Overview
                </a>

                <span
                    class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2.5 text-label-md font-label-md text-primary">
                    <span class="material-symbols-outlined text-lg">view_list</span>
                    List
                </span>
            </div>
        </div>

        <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-4xl lg:grid-cols-4">
                <div class="relative sm:col-span-2 lg:col-span-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search tasks..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10"
                    />
                </div>

                <div class="relative">
                    <select
                        wire:model.live="statusFilter"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                        <option value="all">All Status</option>

                        @foreach (TaskStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>

                <div class="relative">
                    <select
                        wire:model.live="priorityFilter"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                        <option value="all">All Priority</option>

                        @foreach (TaskPriority::cases() as $priority)
                            <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                        @endforeach
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>

                <div class="relative">
                    <select
                        wire:model.live="assigneeFilter"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                        <option value="all">All Assignees</option>

                        @foreach ($this->staff() as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="w-12 px-6 py-4">
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" />
                            </th>

                            <th class="px-4 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                ID
                            </th>

                            <th class="px-4 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Title
                            </th>

                            <th class="px-4 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Assignee
                            </th>

                            <th class="px-4 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Due Date
                            </th>

                            <th class="px-4 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th class="px-4 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->tasks() as $task)
                            <tr
                                wire:key="task-{{ $task->id }}"
                                class="transition-colors hover:bg-slate-50/80"
                            >
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" />
                                </td>

                                <td class="px-4 py-4">
                                    <span class="font-mono text-body-sm text-secondary">
                                        {{ $task->identifier }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ route('admin.workspace.tasks.show', $task->id) }}" wire:navigate
                                            class="text-label-md font-label-md text-on-surface transition-colors hover:text-primary">
                                            {{ $task->title }}
                                        </a>

                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $this->statusBadgeClass($task->status) }}">
                                                {{ $task->status->label() }}
                                            </span>

                                            <span class="inline-flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[14px] {{ $this->priorityColor($task->priority) }}">
                                                    {{ $task->priority->icon() }}
                                                </span>
                                                <span class="text-[11px] text-secondary">
                                                    {{ $task->priority->label() }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    @if ($task->assignee)
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                            </div>

                                            <span class="text-body-sm text-on-surface">
                                                {{ $task->assignee->name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-body-sm text-secondary">Unassigned</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    @if ($task->due_date)
                                        <span class="font-mono text-body-sm {{ $task->due_date->isPast() && $task->status !== App\Enums\TaskStatus::DONE ? 'text-red-500' : 'text-secondary' }}">
                                            {{ $task->due_date->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-body-sm text-secondary">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        wire:click="toggleStatus({{ $task->id }})"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold transition-opacity hover:opacity-80 {{ $this->statusBadgeClass($task->status) }}"
                                    >
                                        {{ $task->status->label() }}
                                    </button>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div
                                        x-data="{ open: false }"
                                        class="relative inline-block text-left"
                                    >
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="text-slate-400 transition-colors hover:text-primary"
                                        >
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            @click.outside="open = false"
                                            x-transition
                                            class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                                        >
                                            <a
                                                href="{{ route('admin.workspace.tasks.show', $task->id) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                View
                                            </a>

                                            <button
                                                type="button"
                                                wire:click="toggleStatus({{ $task->id }})"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                                                Edit Status
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="deleteTask({{ $task->id }})"
                                                wire:confirm="Are you sure you want to delete this task?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50"
                                            >
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
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">task_alt</span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No tasks found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            {{ $this->search || $this->statusFilter !== 'all' || $this->priorityFilter !== 'all' || $this->assigneeFilter !== 'all' ? 'Try adjusting your filters.' : 'Create tasks to start tracking progress in this project.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-body-sm font-body-sm text-secondary">
                        Per page
                    </span>

                    <select
                        wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10"
                    >
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div>
                    {{ $this->tasks()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
