<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\WorkspaceLabel;
use App\Models\User;
use App\Models\WorkspaceProject;
use App\Models\WorkspaceProjectUpdate;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Project Activity')] class extends Component {
    use WithFileUploads;

    private const MAX_EDITOR_FILE_SIZE = 20480;

    public WorkspaceProject $project;

    public bool $showEditModal = false;

    public string $activityBody = '';
    public string $activityHealth = '';
    public string $activityType = 'comment';
    public array $activityFiles = [];

    public ?int $replyToId = null;
    public string $replyBody = '';

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

    public function mount(WorkspaceProject $project): void
    {
        $this->project = WorkspaceProject::query()
            ->with(['creator', 'members', 'updates.author'])
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

        if (in_array($user->role, [UserRole::ADMIN, UserRole::ADMIN_MANAGER], true)) {
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

        if (in_array($user->role, [UserRole::ADMIN, UserRole::ADMIN_MANAGER], true)) {
            return true;
        }

        return $this->project->members->contains(
            fn($member) => (int) $member->id === (int) $user->id
                && $member->pivot?->role === ProjectMemberRole::MANAGER->value
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

    public function postActivity(): void
    {
        abort_unless($this->canWriteUpdate(), 403);

        $validated = $this->validate([
            'activityType' => ['required', 'string', 'in:comment,update'],
            'activityHealth' => ['nullable', 'required_if:activityType,update', 'string', 'in:on_track,at_risk,off_track'],
            'activityBody' => ['required', 'string', 'min:2', 'max:5000'],
            'activityFiles' => ['nullable', 'array', 'max:5'],
            'activityFiles.*' => ['file', 'max:' . self::MAX_EDITOR_FILE_SIZE, 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,json'],
        ]);

        $attachments = [];

        foreach ($validated['activityFiles'] ?? [] as $file) {
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'url' => Storage::disk('public')->url($file->store('workspace/project-updates', 'public')),
            ];
        }

        $isUpdate = $validated['activityType'] === 'update';

        $this->project->updates()->create([
            'user_id' => auth()->id(),
            'type' => $validated['activityType'],
            'health' => $isUpdate ? $validated['activityHealth'] : null,
            'body' => trim($validated['activityBody']),
            'attachments' => $attachments ?: null,
        ]);

        $this->notifyProjectParticipants(
            $isUpdate ? 'posted a project update' : 'commented on the project',
            trim($validated['activityBody']),
            'account.workspace-project.activity'
        );

        $this->reset(['activityBody', 'activityFiles']);
        $this->resetValidation(['activityType', 'activityHealth', 'activityBody', 'activityFiles']);
        $this->project->refresh();

        $this->dispatch(
            'toast',
            message: $isUpdate ? 'Project update posted.' : 'Comment posted.',
            type: 'success'
        );
    }

    public function removeActivityFile(int $index): void
    {
        $files = array_values($this->activityFiles);
        unset($files[$index]);
        $this->activityFiles = array_values($files);
    }

    public function startReply(int $id): void
    {
        abort_unless($this->canWriteUpdate(), 403);

        $this->resetValidation(['replyToId', 'replyBody']);
        $this->replyToId = $id;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->reset(['replyToId', 'replyBody']);
    }

    public function postReply(): void
    {
        abort_unless($this->canWriteUpdate(), 403);

        $this->validate([
            'replyToId' => ['required', 'integer'],
            'replyBody' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $parent = $this->project->updates()->findOrFail($this->replyToId);

        $this->project->updates()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parent->getKey(),
            'type' => 'comment',
            'health' => null,
            'body' => trim($this->replyBody),
        ]);

        $this->notifyProjectParticipants(
            'replied to a comment',
            trim($this->replyBody),
            'account.workspace-project.activity'
        );

        $this->reset(['replyToId', 'replyBody']);
        $this->resetValidation(['replyToId', 'replyBody']);
        $this->project->refresh();

        $this->dispatch(
            'toast',
            message: 'Reply posted.',
            type: 'success'
        );
    }

    public function activityThreads(): array
    {
        $byParent = $this->project->updates->groupBy('parent_id');

        $build = function (WorkspaceProjectUpdate $update) use ($byParent, &$build): array {
            $replies = ($byParent->get($update->id) ?? collect())
                ->sortByDesc('created_at')
                ->values()
                ->map(fn(WorkspaceProjectUpdate $child): array => $build($child))
                ->all();

            return ['update' => $update, 'replies' => $replies];
        };

        return $this->project->updates
            ->where('parent_id', null)
            ->sortByDesc('created_at')
            ->values()
            ->map(fn(WorkspaceProjectUpdate $root): array => $build($root))
            ->all();
    }

    public function healthBadge(string $health): string
    {
        return match ($health) {
            'at_risk' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'off_track' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
    }

    private function notifyProjectParticipants(string $action, string $body, string $clientRoute): void
    {
        $senderId = (int) auth()->id();
        $sender = auth()->user();
        $preview = str($body)->limit(60)->toString();

        $recipientIds = collect();

        if ($this->project->client_id) {
            $recipientIds->push((int) $this->project->client_id);
        }

        $recipientIds = $recipientIds
            ->merge($this->project->members()->pluck('users.id'))
            ->push((int) $this->project->creator_id)
            ->filter()
            ->unique()
            ->reject(fn (int $id) => $id === $senderId)
            ->values();

        $adminRoles = ['admin', 'admin_manager', 'manager', 'staff'];

        foreach ($recipientIds as $userId) {
            $user = User::query()->find($userId);
            $isAdmin = $user && in_array(
                $user->role instanceof UserRole ? $user->role->value : $user->role,
                $adminRoles,
                true
            );

            $url = $isAdmin
                ? route('admin.workspace.projects.show.activity', $this->project)
                : route($clientRoute, $this->project);

            UserNotificationService::notifyUser(
                $userId,
                ($sender?->name ?: 'Someone').' '.$action.' in '.$this->project->name,
                $preview,
                $sender?->name,
                $url
            );
        }
    }
};
?>

<div class="min-h-full">
    <div class="mx-auto w-full px-4 pb-10 lg:px-0">

        @include('pages.admin.workspace.partials._project-header', ['active' => 'activity'])

        {{-- Activity content --}}
        <main class="py-7">
            <div class="mb-5 flex items-center justify-between gap-4">
                <h2 class="text-[15px] font-semibold text-slate-900">
                    Activity
                </h2>

                <span class="text-[11px] text-slate-400">
                    {{ $project->updates_count }} activity {{ Str::plural('entry', $project->updates_count) }}
                </span>
            </div>

            {{-- Composer --}}
            @if ($this->canWriteUpdate())
            <form
                wire:submit.prevent="postActivity"
                x-data="{ open: false, isUpdate: @js($this->activityType === 'update') }"
                class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="p-3">
                    {{-- Type toggle (top-left) --}}
                    <div class="flex w-max items-center gap-1 rounded-lg bg-slate-50 p-0.5">
                        <button
                            type="button"
                            @click="isUpdate = false; $wire.set('activityType', 'comment')"
                            class="h-6 cursor-pointer rounded-[5px] px-2 text-[10px] font-medium transition"
                            :class="isUpdate ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-slate-900 shadow-sm'">
                            Comment
                        </button>

                        <button
                            type="button"
                            @click="isUpdate = true; $wire.set('activityType', 'update'); if ($wire.activityHealth === '') $wire.set('activityHealth', 'on_track')"
                            class="h-6 cursor-pointer rounded-[5px] px-2 text-[10px] font-medium transition"
                            :class="isUpdate ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Update
                        </button>
                    </div>

                    <div class="mt-2">
                        <div class="min-w-0 flex-1">
                            <textarea
                                wire:model="activityBody"
                                x-on:focus="open = true"
                                x-on:keydown.enter.exact.prevent="$wire.postActivity()"
                                rows="1"
                                maxlength="5000"
                                placeholder="Write a comment or update..."
                                class="w-full resize-none bg-transparent py-1 text-sm leading-6 text-slate-700 outline-none placeholder:text-slate-300"></textarea>

                            <div x-show="open" x-cloak x-transition class="border-t border-slate-100 pt-3">
                                {{-- Health (updates only) --}}
                                <div x-show="isUpdate" x-cloak class="flex flex-wrap items-center gap-1.5">
                                    <span class="mr-1 text-[11px] font-medium text-slate-400">Health</span>

                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="activityHealth" value="on_track" class="peer sr-only">
                                        <span class="flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-medium text-slate-500 transition peer-checked:bg-emerald-100 peer-checked:text-emerald-700">
                                            <span class="material-symbols-outlined text-[14px] text-emerald-500">show_chart</span>
                                            On track
                                        </span>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="activityHealth" value="at_risk" class="peer sr-only">
                                        <span class="flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-medium text-slate-500 transition peer-checked:bg-amber-100 peer-checked:text-amber-700">
                                            <span class="material-symbols-outlined text-[14px] text-amber-500">show_chart</span>
                                            At risk
                                        </span>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="activityHealth" value="off_track" class="peer sr-only">
                                        <span class="flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-medium text-slate-500 transition peer-checked:bg-red-100 peer-checked:text-red-700">
                                            <span class="material-symbols-outlined text-[14px] text-red-500">show_chart</span>
                                            Off track
                                        </span>
                                    </label>
                                </div>

                                {{-- Attached files --}}
                                @if ($this->activityFiles)
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($this->activityFiles as $index => $file)
                                    <span wire:key="activity-file-{{ $index }}" class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600">
                                        <span class="material-symbols-outlined text-[13px] text-slate-400">insert_drive_file</span>
                                        <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                        <button
                                            type="button"
                                            @click="$wire.removeActivityFile({{ $index }})"
                                            class="flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition hover:bg-slate-200 hover:text-slate-700">
                                            <span class="material-symbols-outlined text-[12px]">close</span>
                                        </button>
                                    </span>
                                    @endforeach
                                </div>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        @error('activityBody')
                                        <p class="text-[11px] text-red-500">{{ $message }}</p>
                                        @enderror

                                        <label class="flex h-7 cursor-pointer items-center gap-1 rounded-md px-2 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                                            <span class="material-symbols-outlined text-[13px]">attach_file</span>
                                            Attach
                                            <input type="file" wire:model="activityFiles" multiple class="sr-only" />
                                        </label>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="open = false"
                                            class="h-7 cursor-pointer rounded-md px-2.5 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                                            Cancel
                                        </button>

                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="postActivity"
                                            class="inline-flex h-7 items-center gap-1 rounded-md bg-slate-900 px-3 text-[11px] font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span wire:loading.remove wire:target="postActivity">Post</span>
                                            <span wire:loading wire:target="postActivity" class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            @endif

            <div class="relative space-y-5 before:absolute before:bottom-3 before:left-[13px] before:top-3 before:w-px before:bg-slate-200">
                @foreach ($this->activityThreads() as $node)
                <div wire:key="activity-node-{{ $node['update']->id }}">
                    @include('pages.admin.workspace.projects.activity._activity-item', ['node' => $node, 'depth' => 0])
                </div>
                @endforeach

                @if ($project->updated_at && $project->updated_at->ne($project->created_at))
                <div class="relative flex gap-3">
                    <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                        <span class="material-symbols-outlined text-[14px] text-slate-400">edit</span>
                    </div>

                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs leading-5 text-slate-600">Project information was updated.</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            {{ $project->updated_at->format('M d, Y  g:i A') }}
                        </p>
                    </div>
                </div>
                @endif

                <div class="relative flex gap-3">
                    <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                        <span class="material-symbols-outlined text-[14px] text-slate-400">add</span>
                    </div>

                    <div class="min-w-0 pt-0.5">
                        <p class="text-xs leading-5 text-slate-600">
                            <span class="font-medium text-slate-800">
                                {{ $project->creator?->name ?: 'A team member' }}
                            </span>
                            created this project.
                        </p>

                        <p class="mt-0.5 text-[11px] text-slate-400">
                            {{ $project->created_at?->format('M d, Y  g:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @include('pages.admin.workspace.partials._edit-project-modal')

    @include('pages.admin.workspace.partials._editor-script')
</div>