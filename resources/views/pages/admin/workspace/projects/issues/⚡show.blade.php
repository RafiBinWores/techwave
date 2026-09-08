<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskActivityType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\WorkspaceLabel;
use App\Models\User;
use App\Models\WorkspaceProject;
use App\Models\WorkspaceTask;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Project Issues')] class extends Component {
    use WithFileUploads;

    private const MAX_EDITOR_FILE_SIZE = 20480;

    public WorkspaceProject $project;

    public bool $showEditModal = false;

    public bool $showIssueModal = false;
    public string $issueTitle = '';
    public string $issueSummary = '';
    public string $issueStatus = 'todo';
    public string $issuePriority = 'none';
    public ?int $issueAssigneeId = null;
    public ?int $issueLabelId = null;
    public ?string $issueDueDate = null;
    public array $issueFiles = [];

    public string $editTitle = '';
    public string $editSummary = '';
    public ?string $editDetails = null;
    public string $editIcon = '';
    public string $editIconColor = '';
    public ?int $editClientId = null;
    public ?int $editProjectManagerId = null;
    public string $editStatus = 'backlog';
    public string $editPriority = 'none';
    public string $editLabel = '';
    public string $editLabelColor = 'slate';
    public array $editMemberIds = [];
    public ?string $editStartDate = null;
    public ?string $editTargetDate = null;
    public $editorUpload = null;

    public string $issueView = 'board';

    public function mount(WorkspaceProject $project): void
    {
        $this->project = WorkspaceProject::query()
            ->with(['creator', 'members'])
            ->withCount(['tasks', 'updates'])
            ->findOrFail($project->getKey());
    }

    public function canWriteUpdate(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $this->project->creator_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['admin', 'admin_manager'], true)) {
            return true;
        }

        return $this->project->members->contains(
            fn($member) => (int) $member->id === (int) $user->id
        );
    }

    public function canEditProject(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $this->project->creator_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['admin', 'admin_manager'], true)) {
            return true;
        }

        return $this->project->members->contains(
            fn($member) => (int) $member->id === (int) $user->id
                && $member->pivot?->role === ProjectMemberRole::MANAGER->value
        );
    }

    public function canCreateIssue(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $this->project->creator_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['admin', 'admin_manager'], true)) {
            return true;
        }

        return $this->project->members->contains(
            fn($member) => (int) $member->id === (int) $user->id
        );
    }

    public function openEditModal(): void
    {
        abort_unless($this->canEditProject(), 403);

        $this->editTitle = $this->project->name;
        $this->editSummary = $this->project->description ?? '';
        $this->editDetails = $this->project->details;
        $this->editIcon = $this->project->icon ?? '';
        $this->editIconColor = $this->project->icon_color ?? '';
        $this->editClientId = $this->project->client_id;
        $this->editProjectManagerId = $this->project->members->first(
            fn($member) => $member->pivot?->role === ProjectMemberRole::MANAGER->value
        )?->id;
        $this->editStatus = $this->project->status->value;
        $this->editPriority = $this->project->priority->value;
        $this->editLabel = $this->project->label ?? '';
        $this->editLabelColor = $this->project->labels()->value('color') ?? 'slate';
        $this->editMemberIds = $this->project->members
            ->reject(fn($member) => $member->pivot?->role === ProjectMemberRole::MANAGER->value)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
        $this->editStartDate = $this->project->start_date?->format('Y-m-d');
        $this->editTargetDate = $this->project->target_date?->format('Y-m-d');

        $this->resetValidation(['editTitle', 'editSummary', 'editDetails', 'editIcon', 'editIconColor', 'editClientId', 'editProjectManagerId', 'editStatus', 'editPriority', 'editLabel', 'editLabelColor', 'editMemberIds', 'editStartDate', 'editTargetDate']);
        $this->showEditModal = true;
    }

    public function editIconColor(): string
    {
        return $this->editIconColor ?: '#4f46e5';
    }

    public function projectIconColors(): array
    {
        return [
            '#4f46e5',
            '#5b8def',
            '#1d9bf0',
            '#30a46c',
            '#46a758',
            '#eab308',
            '#f59e0b',
            '#f97316',
            '#ef4444',
            '#f43f5e',
            '#ec4899',
            '#d946ef',
            '#8b5cf6',
            '#64748b',
            '#0f172a',
        ];
    }

    public function projectIcons(): array
    {
        return [
            'space_dashboard',
            'rocket_launch',
            'task_alt',
            'check_circle',
            'flag',
            'flag_circle',
            'emoji_objects',
            'lightbulb',
            'bolt',
            'construction',
            'build',
            'engineering',
            'design_services',
            'palette',
            'draw',
            'brush',
            'developer_mode',
            'code',
            'terminal',
            'data_object',
            'api',
            'web',
            'language',
            'cloud',
            'cloud_done',
            'storage',
            'database',
            'analytics',
            'monitoring',
            'insights',
            'query_stats',
            'campaign',
            'phone',
            'trending_up',
            'shopping_bag',
            'shopping_cart',
            'payments',
            'receipt_long',
            'folder',
            'folder_open',
            'inventory_2',
            'package_2',
            'extension',
            'apps',
            'widgets',
            'grid_view',
            'dashboard',
            'account_tree',
            'hub',
            'lan',
            'devices',
            'computer',
            'phone_iphone',
            'photo_camera',
            'movie',
            'article',
            'description',
            'auto_stories',
            'school',
            'event',
            'calendar_month',
            'schedule',
            'groups',
            'group',
            'person',
            'handshake',
            'favorite',
            'volunteer_activism',
        ];
    }

    public function clients()
    {
        return User::query()
            ->where('role', UserRole::CLIENT->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function selectedClientName(): ?string
    {
        if (! $this->editClientId) {
            return null;
        }

        return $this->clients()->firstWhere('id', $this->editClientId)?->name;
    }

    public function staff()
    {
        return User::query()
            ->whereIn('role', ['admin', 'manager', 'staff', 'admin_manager'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function toggleMember(int $userId): void
    {
        $allowed = $this->staff()->pluck('id')->all();

        abort_unless(in_array($userId, $allowed, true), 422);

        $key = array_search($userId, $this->editMemberIds, true);

        if ($key === false) {
            $this->editMemberIds[] = $userId;
        } else {
            unset($this->editMemberIds[$key]);
            $this->editMemberIds = array_values($this->editMemberIds);
        }
    }

    public function selectedManagerName(): ?string
    {
        if (! $this->editProjectManagerId) {
            return null;
        }

        return $this->staff()->firstWhere('id', $this->editProjectManagerId)?->name;
    }

    public function existingLabels(): array
    {
        $colors = [
            'slate' => '#64748b', 'gray' => '#6b7280', 'red' => '#ef4444',
            'orange' => '#f97316', 'amber' => '#f59e0b', 'yellow' => '#eab308',
            'lime' => '#84cc16', 'green' => '#22c55e', 'emerald' => '#10b981',
            'teal' => '#14b8a6', 'cyan' => '#06b6d4', 'sky' => '#0ea5e9',
            'blue' => '#3b82f6', 'indigo' => '#6366f1', 'violet' => '#8b5cf6',
            'purple' => '#a855f7', 'fuchsia' => '#d946ef', 'pink' => '#ec4899',
            'rose' => '#f43f5e',
        ];

        return WorkspaceLabel::query()
            ->select('name', 'color')
            ->distinct()
            ->orderBy('name')
            ->get()
            ->map(fn (WorkspaceLabel $label) => [
                'name' => $label->name,
                'color' => $label->color,
                'hex' => $colors[$label->color] ?? '#64748b',
            ])
            ->values()
            ->all();
    }

    public function saveProjectEdit(): void
    {
        abort_unless($this->canEditProject(), 403);

        $validated = $this->validate([
            'editTitle' => ['required', 'string', 'max:180'],
            'editSummary' => ['nullable', 'string', 'max:300'],
            'editDetails' => ['nullable', 'string'],
            'editIcon' => ['nullable', 'string', 'max:100'],
            'editIconColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'editClientId' => ['nullable', 'integer', 'exists:users,id'],
            'editProjectManagerId' => ['nullable', 'integer', 'exists:users,id'],
            'editStatus' => ['required', 'string', 'in:backlog,planning,in_progress,completed,cancelled'],
            'editPriority' => ['required', 'string', 'in:urgent,high,medium,low,none'],
            'editLabel' => ['nullable', 'string', 'max:100'],
            'editMemberIds' => ['nullable', 'array'],
            'editMemberIds.*' => ['integer', 'exists:users,id'],
            'editStartDate' => ['nullable', 'date'],
            'editTargetDate' => ['nullable', 'date', 'after_or_equal:editStartDate'],
        ]);

        $this->project->update([
            'name' => trim($validated['editTitle']),
            'description' => $validated['editSummary'] ?: null,
            'details' => $validated['editDetails'] ?: null,
            'icon' => $validated['editIcon'] ?: null,
            'icon_color' => $validated['editIconColor'] ?: null,
            'client_id' => $validated['editClientId'] ?: null,
            'status' => $validated['editStatus'],
            'priority' => $validated['editPriority'],
            'start_date' => $validated['editStartDate'] ?: null,
            'target_date' => $validated['editTargetDate'] ?: null,
        ]);

        $managerId = $validated['editProjectManagerId'] ? (int) $validated['editProjectManagerId'] : null;

        $memberIds = collect($validated['editMemberIds'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($managerId !== null) {
            $memberIds = $memberIds
                ->reject(fn($id) => $id === $managerId)
                ->push($managerId)
                ->unique()
                ->values();
        }

        $currentIds = $this->project->members()->get()->pluck('id')->map(fn($id) => (int) $id)->values();

        $removed = $currentIds->diff($memberIds);
        if ($removed->isNotEmpty()) {
            $this->project->members()->detach($removed->all());
        }

        foreach ($memberIds->diff($currentIds) as $userId) {
            $this->project->members()->attach($userId, [
                'role' => $userId === $managerId
                    ? ProjectMemberRole::MANAGER->value
                    : ProjectMemberRole::MEMBER->value,
            ]);
        }

        foreach ($memberIds->intersect($currentIds) as $userId) {
            $this->project->members()->updateExistingPivot($userId, [
                'role' => $userId === $managerId
                    ? ProjectMemberRole::MANAGER->value
                    : ProjectMemberRole::MEMBER->value,
            ]);
        }

        $this->project->ensureLabel($validated['editLabel'] ?? '', $this->editLabelColor);

        $this->showEditModal = false;
        $this->project->refresh();

        $this->dispatch(
            'toast',
            message: 'Project updated successfully.',
            type: 'success'
        );
    }

    public function saveDetailsDraft(?string $details = null): void
    {
        abort_unless($this->canWriteUpdate(), 403);

        if ($details === null) {
            return;
        }

        if (strlen($details) > 500000 || ($details !== '' && ! json_validate($details))) {
            abort(422);
        }

        $this->project->update([
            'details' => $details === '' ? null : $details,
        ]);

        $this->project->refresh();
    }

    public function storeEditorUpload(): array
    {
        abort_unless($this->canEditProject(), 403);

        $this->validate([
            'editorUpload' => [
                'required',
                'file',
                'max:' . self::MAX_EDITOR_FILE_SIZE,
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,json',
            ],
        ]);

        $file = $this->editorUpload;
        $mime = (string) $file->getMimeType();
        $isImage = str_starts_with($mime, 'image/');
        $directory = $isImage
            ? 'workspace/project-details/images'
            : 'workspace/project-details/files';

        $path = $file->store($directory, 'public');

        $payload = [
            'kind' => $isImage ? 'image' : 'file',
            'name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => (int) $file->getSize(),
            'url' => Storage::disk('public')->url($path),
        ];

        $this->reset('editorUpload');

        return $payload;
    }

    public function tasks()
    {
        return WorkspaceTask::query()
            ->with(['assignee'])
            ->where('project_id', $this->project->id)
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    public function projectMembers()
    {
        return $this->project->members->sortBy('name');
    }

    public function projectLabels()
    {
        return WorkspaceLabel::query()
            ->orderBy('name')
            ->get()
            ->unique('name');
    }

    public function labelHex(string $color): string
    {
        $colors = [
            'slate' => '#64748b', 'gray' => '#6b7280', 'red' => '#ef4444',
            'orange' => '#f97316', 'amber' => '#f59e0b', 'yellow' => '#eab308',
            'lime' => '#84cc16', 'green' => '#22c55e', 'emerald' => '#10b981',
            'teal' => '#14b8a6', 'cyan' => '#06b6d4', 'sky' => '#0ea5e9',
            'blue' => '#3b82f6', 'indigo' => '#6366f1', 'violet' => '#8b5cf6',
            'purple' => '#a855f7', 'fuchsia' => '#d946ef', 'pink' => '#ec4899',
            'rose' => '#f43f5e',
        ];

        return $colors[$color] ?? '#64748b';
    }

    public function selectedLabelName(): ?string
    {
        if (! $this->issueLabelId) {
            return null;
        }

        return WorkspaceLabel::query()->whereKey($this->issueLabelId)->value('name');
    }

    public function openIssueModal(): void
    {
        abort_unless($this->canCreateIssue(), 403);

        $this->reset(['issueTitle', 'issueSummary', 'issueStatus', 'issuePriority', 'issueAssigneeId', 'issueLabelId', 'issueDueDate', 'issueFiles']);
        $this->resetValidation(['issueTitle', 'issueSummary', 'issueStatus', 'issuePriority', 'issueAssigneeId', 'issueLabelId', 'issueDueDate', 'issueFiles']);
        $this->issueStatus = 'todo';
        $this->issuePriority = 'none';
        $this->showIssueModal = true;
    }

    public function closeIssueModal(): void
    {
        $this->showIssueModal = false;
        $this->resetValidation(['issueTitle', 'issueSummary', 'issueStatus', 'issuePriority', 'issueAssigneeId', 'issueLabelId', 'issueDueDate', 'issueFiles']);
    }

    public function removeIssueFile(int $index): void
    {
        $files = array_values($this->issueFiles);
        unset($files[$index]);
        $this->issueFiles = array_values($files);
    }

    public function saveIssue(): void
    {
        abort_unless($this->canCreateIssue(), 403);

        $validated = $this->validate([
            'issueTitle' => ['required', 'string', 'max:255'],
            'issueSummary' => ['nullable', 'string', 'max:10000'],
            'issueStatus' => ['required', 'string', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'issuePriority' => ['required', 'string', 'in:' . implode(',', array_column(TaskPriority::cases(), 'value'))],
            'issueAssigneeId' => ['nullable', 'integer', 'exists:users,id'],
            'issueLabelId' => ['nullable', 'integer', 'exists:workspace_labels,id'],
            'issueDueDate' => ['nullable', 'date'],
            'issueFiles' => ['nullable', 'array', 'max:5'],
            'issueFiles.*' => ['file', 'max:' . self::MAX_EDITOR_FILE_SIZE, 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,json,html,htm,js,mjs,css,md,sql,xml,php'],
        ]);

        if ($this->issueLabelId) {
            $exists = WorkspaceLabel::query()
                ->where('project_id', $this->project->id)
                ->whereKey($this->issueLabelId)
                ->exists();

            abort_unless($exists, 422);
        }

        $attachments = [];

        foreach ($validated['issueFiles'] ?? [] as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $phpExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar'];

            $storeName = in_array($extension, $phpExtensions, true)
                ? $file->hashName() . '.txt'
                : $file->hashName() . '.' . ($extension ?: $file->guessExtension());

            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'url' => Storage::disk('public')->url($file->storeAs('workspace/issues', $storeName, 'public')),
            ];
        }

        $prefix = strtoupper(Str::slug($this->project->name, '')) ?: 'TASK';
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
        $prefix = substr($prefix, 0, 4);
        $nextNumber = $this->project->tasks()->max('id') + 1;

        $task = $this->project->tasks()->create([
            'identifier' => $prefix . '-' . $nextNumber,
            'title' => trim($validated['issueTitle']),
            'description' => $validated['issueSummary'] ?: null,
            'attachments' => $attachments ?: null,
            'status' => $validated['issueStatus'],
            'priority' => $validated['issuePriority'],
            'assignee_id' => $validated['issueAssigneeId'] ?: null,
            'due_date' => $validated['issueDueDate'] ?: null,
            'creator_id' => auth()->id(),
            'sort_order' => $this->project->tasks()->whereNull('parent_task_id')->count(),
        ]);

        if ($this->issueLabelId) {
            $task->labels()->attach($this->issueLabelId);
        }

        $task->activities()->create([
            'user_id' => auth()->id(),
            'type' => TaskActivityType::CREATED,
            'old_value' => null,
            'new_value' => $task->title,
        ]);

        $this->reset(['issueTitle', 'issueSummary', 'issueStatus', 'issuePriority', 'issueAssigneeId', 'issueLabelId', 'issueDueDate', 'issueFiles']);
        $this->resetValidation(['issueTitle', 'issueSummary', 'issueStatus', 'issuePriority', 'issueAssigneeId', 'issueLabelId', 'issueDueDate', 'issueFiles']);
        $this->showIssueModal = false;

        $this->dispatch(
            'toast',
            message: 'Issue created.',
            type: 'success'
        );
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

    public function statusDotClass(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::TODO => 'bg-blue-400',
            TaskStatus::IN_PROGRESS => 'bg-amber-400',
            TaskStatus::DONE => 'bg-emerald-400',
            TaskStatus::CANCELLED => 'bg-red-400',
            TaskStatus::BACKLOG => 'bg-slate-400',
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

<div class="min-h-full">
    <div class="mx-auto w-full px-4 pb-10 lg:px-0">

        @include('pages.admin.workspace.partials._project-header', ['active' => 'issues'])

        {{-- Issues content --}}
        @persist('workspace-issues-content-' . $project->id)
        <main class="py-7">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-[15px] font-semibold text-slate-900">Issues</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Every task tracked in this project.</p>
                </div>

                <span class="flex items-center gap-2">
                    <span class="flex items-center rounded-lg border border-slate-200 bg-white p-0.5">
                        <button
                            type="button"
                            wire:click="$set('issueView', 'board')"
                            class="inline-flex h-6 cursor-pointer items-center gap-1 rounded-md px-2 text-[11px] font-medium transition"
                            @class([
                                'bg-slate-900 text-white' => $issueView === 'board',
                                'text-slate-500 hover:text-slate-800' => $issueView !== 'board',
                            ])>
                            <span class="material-symbols-outlined text-[13px]">grid_view</span>
                            Board
                        </button>

                        <button
                            type="button"
                            wire:click="$set('issueView', 'list')"
                            class="inline-flex h-6 cursor-pointer items-center gap-1 rounded-md px-2 text-[11px] font-medium transition"
                            @class([
                                'bg-slate-900 text-white' => $issueView === 'list',
                                'text-slate-500 hover:text-slate-800' => $issueView !== 'list',
                            ])>
                            <span class="material-symbols-outlined text-[13px]">view_list</span>
                            List
                        </button>
                    </span>

                    @if ($this->canCreateIssue())
                    <button
                        type="button"
                        wire:click="openIssueModal"
                        class="inline-flex h-7 cursor-pointer items-center gap-1 rounded-md bg-slate-900 px-2.5 text-[11px] font-medium text-white transition hover:bg-slate-800">
                        <span class="material-symbols-outlined text-[13px]">add</span>
                        Add issue
                    </button>
                    @endif
                </span>
            </div>

            @php($projectTasks = $this->tasks())
            @if ($projectTasks->isNotEmpty())
            @php($groupedTasks = $projectTasks->groupBy(fn ($task) => $task->status->value))

            @if ($issueView === 'board')
            <div class="flex items-start gap-4 overflow-x-auto pb-2">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                @php($statusTasks = $groupedTasks->get($status->value) ?? collect())
                <div class="w-72 shrink-0">
                    <div class="mb-2 flex items-center gap-2 px-1">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $this->statusDotClass($status) }}"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            {{ $status->label() }}
                        </h3>
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
                            {{ $statusTasks->count() }}
                        </span>
                    </div>

                    <div class="flex min-h-28 flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50/60 p-2">
                        @forelse ($statusTasks as $task)
                        <a
                            href="{{ route('admin.workspace.tasks.show', $task->id) }}"
                            wire:navigate
                            wire:key="board-{{ $task->id }}-{{ $status->value }}"
                            class="block rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition hover:border-slate-300 hover:shadow">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate font-mono text-[10px] text-slate-400">
                                    {{ $task->identifier }}
                                </span>

                                <span class="material-symbols-outlined shrink-0 text-[14px] {{ $this->priorityColor($task->priority) }}">
                                    {{ $task->priority->icon() }}
                                </span>
                            </div>

                            <p class="mt-1.5 text-[13px] font-medium leading-snug text-slate-800">
                                {{ $task->title }}
                            </p>

                            <div class="mt-2.5 flex items-center justify-between gap-2">
                                <span class="min-w-0 truncate text-[10px] text-slate-400">
                                    @if ($task->due_date)
                                    {{ \Illuminate\Support\Carbon::parse($task->due_date)->format('M d') }}
                                    @else
                                    No due date
                                    @endif
                                </span>

                                @if ($task->assignee)
                                @if ($task->assignee->avatar)
                                <span
                                    title="{{ $task->assignee->name }}"
                                    class="h-5 w-5 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                                    <img
                                        src="{{ Storage::url($task->assignee->avatar) }}"
                                        alt="{{ $task->assignee->name }}"
                                        class="h-full w-full object-cover">
                                </span>
                                @else
                                <span
                                    title="{{ $task->assignee->name }}"
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[8px] font-bold text-primary">
                                    {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                </span>
                                @endif
                                @endif
                            </div>
                        </a>
                        @empty
                        <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 px-3 py-8 text-[11px] text-slate-300">
                            No issues
                        </div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="space-y-6">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                @php($statusTasks = $groupedTasks->get($status->value) ?? collect())
                @if ($statusTasks->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-2.5">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $this->statusDotClass($status) }}"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            {{ $status->label() }}
                        </h3>
                        <span class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 ring-1 ring-slate-200">
                            {{ $statusTasks->count() }}
                        </span>
                    </div>

                    @foreach ($statusTasks as $task)
                    <a
                        href="{{ route('admin.workspace.tasks.show', $task->id) }}"
                        wire:navigate
                        wire:key="issue-{{ $task->id }}-{{ $status->value }}"
                        class="group flex items-center gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 last:border-0">
                        <span class="w-16 shrink-0 font-mono text-[11px] text-slate-400">
                            {{ $task->identifier }}
                        </span>

                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 transition group-hover:text-primary">
                            {{ $task->title }}
                        </span>

                        <span class="material-symbols-outlined text-[15px] {{ $this->priorityColor($task->priority) }}">
                            {{ $task->priority->icon() }}
                        </span>

                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $this->statusBadgeClass($task->status) }}">
                            {{ $task->status->label() }}
                        </span>

                        @if ($task->assignee)
                        @if ($task->assignee->avatar)
                        <span
                            title="{{ $task->assignee->name }}"
                            class="h-6 w-6 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                            <img
                                src="{{ Storage::url($task->assignee->avatar) }}"
                                alt="{{ $task->assignee->name }}"
                                class="h-full w-full object-cover">
                        </span>
                        @else
                        <span
                            title="{{ $task->assignee->name }}"
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                            {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                        </span>
                        @endif
                        @endif
                    </a>
                    @endforeach
                </section>
                @endif
                @endforeach
            </div>
            @endif

            @else
            <div class="rounded-xl border border-dashed border-slate-200 bg-white px-5 py-14 text-center">
                <span class="material-symbols-outlined text-[28px] text-slate-300">
                    task_alt
                </span>

                <p class="mt-2 text-sm font-medium text-slate-600">
                    No issues yet
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Create tasks to start tracking work in this project.
                </p>
            </div>
            @endif
        </main>
        @endpersist
    </div>


    @include('pages.admin.workspace.projects.issues._issue-modal')

    @include('pages.admin.workspace.partials._edit-project-modal')

    @include('pages.admin.workspace.partials._editor-script')
</div>