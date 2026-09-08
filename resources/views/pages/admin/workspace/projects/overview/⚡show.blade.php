<?php

use App\Enums\ProjectMemberRole;
    use App\Enums\ProjectPriority;
    use App\Enums\ProjectStatus;
    use App\Enums\TaskStatus;
    use App\Enums\UserRole;
    use App\Models\WorkspaceLabel;
    use App\Models\User;
    use App\Models\WorkspaceProject;
use App\Models\WorkspaceProjectUpdate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Project Overview')] class extends Component {
    use WithFileUploads;

    private const MAX_EDITOR_FILE_SIZE = 20480;

    public WorkspaceProject $project;

    public bool $showEditModal = false;

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
        $this->project = $project;
        $this->refreshProject();
    }

    public function updateStatus(string $status): void
    {
        $allowed = array_column(ProjectStatus::cases(), 'value');

        abort_unless(in_array($status, $allowed, true), 422);

        $this->project->update([
            'status' => $status,
        ]);

        $this->refreshProject();

        $this->dispatch(
            'toast',
            message: 'Project status updated.',
            type: 'success'
        );
    }

    public function updatePriority(string $priority): void
    {
        $allowed = array_column(ProjectPriority::cases(), 'value');

        abort_unless(in_array($priority, $allowed, true), 422);

        $this->project->update([
            'priority' => $priority,
        ]);

        $this->refreshProject();

        $this->dispatch(
            'toast',
            message: 'Project priority updated.',
            type: 'success'
        );
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

        $this->reset(['replyToId', 'replyBody']);
        $this->resetValidation(['replyToId', 'replyBody']);
        $this->refreshProject();

        $this->dispatch(
            'toast',
            message: 'Reply posted.',
            type: 'success'
        );
    }

    public function latestActivity(): ?WorkspaceProjectUpdate
    {
        return $this->project->updates
            ->where('parent_id', null)
            ->sortByDesc('created_at')
            ->first();
    }

    public function healthBadge(string $health): string
    {
        return match ($health) {
            'at_risk' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'off_track' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
    }

    public function projectProgress(): int
    {
        $total = $this->project->taskCount();

        if ($total === 0) {
            return 0;
        }

        $done = $this->project->taskCount(\App\Enums\TaskStatus::DONE->value);

        return (int) round(($done / $total) * 100);
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

    public function projectLabelHex(): string
    {
        return match ($this->project->labels()->value('color') ?? 'slate') {
            'slate' => '#64748b', 'gray' => '#6b7280', 'red' => '#ef4444',
            'orange' => '#f97316', 'amber' => '#f59e0b', 'yellow' => '#eab308',
            'lime' => '#84cc16', 'green' => '#22c55e', 'emerald' => '#10b981',
            'teal' => '#14b8a6', 'cyan' => '#06b6d4', 'sky' => '#0ea5e9',
            'blue' => '#3b82f6', 'indigo' => '#6366f1', 'violet' => '#8b5cf6',
            'purple' => '#a855f7', 'fuchsia' => '#d946ef', 'pink' => '#ec4899',
            'rose' => '#f43f5e', default => '#64748b',
        };
    }

    public function selectedManagerName(): ?string
    {
        if (! $this->editProjectManagerId) {
            return null;
        }

        return $this->staff()->firstWhere('id', $this->editProjectManagerId)?->name;
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
        $this->refreshProject();

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

        $this->refreshProject();
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

    public function manager()
    {
        return $this->project->members->first(
            fn($member) => $member->pivot?->role === ProjectMemberRole::MANAGER->value
        );
    }

    private function refreshProject(): void
    {
        $this->project = WorkspaceProject::query()
            ->with(['creator', 'client', 'members', 'updates.author'])
            ->withCount(['tasks', 'updates'])
            ->findOrFail($this->project->getKey());
    }
};
?>

<div class="min-h-full">
    <div class="mx-auto w-full px-4 pb-10 lg:px-0">

        @include('pages.admin.workspace.partials._project-header', ['active' => 'overview'])

        {{-- Main overview --}}
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_330px] xl:h-[calc(100dvh-236px)] xl:overflow-hidden">

            {{-- Main content --}}
            <main class="workspace-scroll min-w-0 xl:h-full xl:min-h-0 xl:overflow-x-hidden xl:overflow-y-auto xl:border-r xl:border-slate-200 xl:pr-8">

                <div>

                    {{-- Latest activity --}}
                    @php($latestActivity = $this->latestActivity())
                    @if ($latestActivity)
                    <section class="border-b border-slate-200 py-5">
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <h2 class="text-[14px] font-semibold text-slate-800">
                                Latest activity
                            </h2>

                            <a
                                href="{{ route('admin.workspace.projects.show.activity', $project) }}"
                                wire:navigate
                                class="text-[11px] font-medium text-slate-400 transition hover:text-slate-700">
                                View all activity
                            </a>
                        </div>

                        <div
                            x-data="{ openReply: false }"
                            class="group overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <a
                                href="{{ route('admin.workspace.projects.show.activity', $project) }}"
                                wire:navigate
                                class="block w-full p-4 text-left transition hover:bg-slate-50 focus:outline-none">
                                <span class="flex items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                                        <span class="material-symbols-outlined text-[15px]
                                            @if (($latestActivity->type ?? 'update') === 'comment') text-slate-400
                                            @elseif ($latestActivity->health === 'off_track') text-red-500
                                            @elseif ($latestActivity->health === 'at_risk') text-amber-500
                                            @else text-emerald-500
                                            @endif">
                                            {{ ($latestActivity->type ?? 'update') === 'comment' ? 'comment' : 'show_chart' }}
                                        </span>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-x-1.5 text-xs leading-5 text-slate-600">
                                            <span class="font-medium text-slate-800">
                                                {{ $latestActivity->author?->name ?: 'Former team member' }}
                                            </span>

                                            @if (($latestActivity->type ?? 'update') === 'update')
                                            posted a project update
                                            <span class="rounded-full px-2 py-0.5 align-middle text-[10px] font-medium ring-1 ring-inset {{ $this->healthBadge($latestActivity->health ?? 'on_track') }}">
                                                {{ $latestActivity->health === 'off_track' ? 'Off track' : ($latestActivity->health === 'at_risk' ? 'At risk' : 'On track') }}
                                            </span>
                                            @else
                                            commented
                                            <span class="text-slate-500">"{{ $latestActivity->body }}"</span>
                                            @endif
                                        </span>

                                        @if (($latestActivity->type ?? 'update') === 'update' && $latestActivity->body)
                                        <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500">
                                            {{ $latestActivity->body }}
                                        </span>
                                        @endif

                                        <span class="mt-1 flex flex-wrap items-center gap-x-2 text-[11px] text-slate-400">
                                            {{ $latestActivity->created_at?->format('M d, Y g:i A') }}

                                            @if (! empty($latestActivity->attachments))
                                            · {{ count($latestActivity->attachments) }}
                                            {{ Str::plural('attachment', count($latestActivity->attachments)) }}
                                            @endif
                                        </span>
                                    </span>
                                </span>
                            </a>

                            @if ($this->canWriteUpdate())
                            <div class="flex items-center gap-1 border-t border-slate-100 px-3 py-1.5">
                                <button
                                    type="button"
                                    @click="$wire.startReply({{ $latestActivity->getKey() }}); openReply = !openReply"
                                    class="h-7 cursor-pointer rounded-md px-2 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                    Reply
                                </button>

                                <!-- <span class="ml-auto inline-flex items-center gap-0.5 text-[11px] text-slate-400 transition group-hover:text-primary">
                                    View in activity
                                    <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
                                </span> -->
                            </div>

                            {{-- Inline reply composer --}}
                            @if ($this->replyToId === $latestActivity->getKey())
                            <div x-show="openReply" x-cloak x-transition class="border-t border-slate-100 p-3">
                                <form wire:submit.prevent="postReply">
                                    <textarea
                                        wire:model="replyBody"
                                        rows="2"
                                        maxlength="5000"
                                        placeholder="Write a reply..."
                                        class="w-full resize-none bg-transparent text-xs leading-5 text-slate-700 outline-none placeholder:text-slate-300"></textarea>

                                    <div class="mt-2 flex items-center justify-between gap-3">
                                        @error('replyBody')
                                        <p class="text-[11px] text-red-500">{{ $message }}</p>
                                        @enderror

                                        <div class="ml-auto flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="openReply = false; $wire.cancelReply()"
                                                class="h-7 cursor-pointer rounded-md px-2.5 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                wire:loading.attr="disabled"
                                                wire:target="postReply"
                                                class="inline-flex h-7 items-center gap-1 rounded-md bg-slate-900 px-3 text-[11px] font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                                                <span wire:loading.remove wire:target="postReply">Post</span>
                                                <span wire:loading wire:target="postReply" class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endif
                            @endif
                        </div>
                    </section>
                    @endif

                    {{-- Quick properties --}}
                    <section class="border-b border-slate-200 py-5">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-2.5">

                            {{-- Status --}}
                            <div class="relative">
                                <select
                                    wire:change="updateStatus($event.target.value)"
                                    class="h-8 appearance-none rounded-md border border-slate-200 bg-white pl-8 pr-7 text-xs font-medium text-slate-700 outline-none transition hover:border-primary/40 focus:border-primary">
                                    @foreach (ProjectStatus::cases() as $statusOption)
                                    <option
                                        value="{{ $statusOption->value }}"
                                        @selected($project->status->value === $statusOption->value)>
                                        {{ $statusOption->label() }}
                                    </option>
                                    @endforeach
                                </select>

                                <span class="pointer-events-none absolute left-2.5 top-1/2 h-2 w-2 -translate-y-1/2 rounded-full
                                @switch($project->status->value)
                                    @case('completed') bg-emerald-500 @break
                                    @case('in_progress') bg-amber-500 @break
                                    @case('planning') bg-blue-500 @break
                                    @case('cancelled') bg-red-400 @break
                                    @default bg-slate-300
                                @endswitch
                            "></span>

                                <span class="material-symbols-outlined pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-[15px] text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            {{-- Priority --}}
                            <div class="relative">
                                <select
                                    wire:change="updatePriority($event.target.value)"
                                    class="h-8 appearance-none rounded-md border border-slate-200 bg-white pl-8 pr-7 text-xs font-medium text-slate-700 outline-none transition hover:border-primary/40 focus:border-primary">
                                    @foreach (ProjectPriority::cases() as $priorityOption)
                                    <option
                                        value="{{ $priorityOption->value }}"
                                        @selected($project->priority->value === $priorityOption->value)>
                                        {{ $priorityOption->label() }}
                                    </option>
                                    @endforeach
                                </select>

                                <span class="material-symbols-outlined pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-[16px] text-slate-400">
                                    signal_cellular_alt
                                </span>

                                <span class="material-symbols-outlined pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-[15px] text-slate-400">
                                    expand_more
                                </span>
                            </div>

                            @if ($project->label)
                            <span class="inline-flex h-8 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 ">
                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $this->projectLabelHex() }}"></span>
                                {{ $project->label }}
                            </span>
                            @endif

                            @if ($this->manager())
                            @php($projectManager = $this->manager())
                            <span class="inline-flex h-8 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 ">
                                @if ($projectManager->avatar)
                                <span class="h-5 w-5 overflow-hidden rounded-full ring-1 ring-slate-200">
                                    <img
                                        src="{{ Storage::url($projectManager->avatar) }}"
                                        alt="{{ $projectManager->name }}"
                                        class="h-full w-full object-cover">
                                </span>
                                @else
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                    {{ strtoupper(substr($projectManager->name, 0, 1)) }}
                                </span>
                                @endif

                                {{ $projectManager->name }}
                            </span>
                            @endif

                            @if ($project->target_date)
                            <span class="inline-flex h-8 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 ">
                                <span class="material-symbols-outlined text-[15px] text-slate-400">event</span>
                                {{ $project->target_date->format('M d') }}
                            </span>
                            @endif
                        </div>
                    </section>

                    {{-- Project details --}}
                    <section class="py-7">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-[15px] font-semibold text-slate-900">
                                    Project details
                                </h2>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    Brief, requirements, decisions, links and project notes.
                                </p>
                            </div>
                        </div>

                        @if ($project->details)
                        <div
                            x-data="projectDetailsViewer(@js($project->details), $wire, @js($this->canEditProject()), @js($this->canWriteUpdate()))"
                            wire:ignore
                            class="linear-project-editor project-details-readonly">
                            <div
                                x-show="loading"
                                class="flex min-h-36 items-center justify-center gap-2 text-xs text-slate-400">
                                <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-200 border-t-primary"></span>
                                Loading project details...
                            </div>

                            <div
                                x-show="!loading"
                                x-cloak
                                x-ref="viewer"
                                class="linear-project-editor">
                            </div>
                        </div>
                        @else
                        <div class="rounded-lg border border-dashed border-slate-200 px-5 py-10 text-center">
                            <span class="material-symbols-outlined text-[24px] text-slate-300">
                                description
                            </span>

                            <p class="mt-2 text-sm font-medium text-slate-600">
                                No project details yet
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Add project details from your project editor.
                            </p>
                        </div>
                        @endif
                    </section>
                </div>
            </main>

            {{-- Right sidebar --}}
            <aside class="workspace-scroll min-w-0 xl:h-full xl:min-h-0 xl:overflow-x-hidden xl:overflow-y-auto xl:pl-6">
                <div class="py-6 xl:sticky xl:top-20">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-400">
                            Properties
                        </h2>
                    </div>

                    <div class="space-y-1">

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Status</span>

                            <div class="flex min-w-0 items-center gap-2">
                                <span class="h-2 w-2 shrink-0 rounded-full
                                    @switch($project->status->value)
                                        @case('completed') bg-emerald-500 @break
                                        @case('in_progress') bg-amber-500 @break
                                        @case('planning') bg-blue-500 @break
                                        @case('cancelled') bg-red-400 @break
                                        @default bg-slate-300
                                    @endswitch
                                "></span>

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->status->label() }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Priority</span>

                            <div class="flex min-w-0 items-center gap-2">
                                <span class="material-symbols-outlined text-[15px] text-slate-400">
                                    signal_cellular_alt
                                </span>

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->priority->label() }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Lead</span>

                            @if ($this->manager())
                            @php($projectManager = $this->manager())
                            <div class="flex min-w-0 items-center gap-2">
                                @if ($projectManager->avatar)
                                <span class="h-5 w-5 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                                    <img
                                        src="{{ Storage::url($projectManager->avatar) }}"
                                        alt="{{ $projectManager->name }}"
                                        class="h-full w-full object-cover">
                                </span>
                                @else
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[9px] font-semibold text-primary">
                                    {{ strtoupper(substr($projectManager->name, 0, 1)) }}
                                </span>
                                @endif

                                <span class="truncate font-medium text-slate-700">
                                    {{ $projectManager->name }}
                                </span>
                            </div>
                            @else
                            <span class="text-slate-400">No lead</span>
                            @endif
                        </div>

                        @if ($project->client)
                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Client</span>

                            <div class="flex min-w-0 items-center gap-2">
                                @if ($project->client->avatar)
                                <span class="h-5 w-5 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                                    <img
                                        src="{{ Storage::url($project->client->avatar) }}"
                                        alt="{{ $project->client->name }}"
                                        class="h-full w-full object-cover">
                                </span>
                                @else
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[9px] font-semibold text-primary">
                                    {{ strtoupper(substr($project->client->name, 0, 1)) }}
                                </span>
                                @endif

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->client->name }}
                                </span>
                            </div>
                        </div>
                        @endif

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Members</span>

                            @if ($project->members->isEmpty())
                            <span class="text-slate-400">None</span>
                            @else
                            <div class="flex min-w-0 items-center">
                                <div x-data="{ open: false, search: '' }" class="flex min-w-0 items-center">
                                    <button
                                        type="button"
                                        @click="open = true"
                                        title="Show all members"
                                        class="flex min-w-0 cursor-pointer items-center">
                                        <div class="flex -space-x-1.5">
                                            @foreach ($project->members->take(5) as $member)
                                            @if ($member->avatar)
                                            <span
                                                title="{{ $member->name }}"
                                                class="h-5 w-5 overflow-hidden rounded-full border-2 border-white">
                                                <img
                                                    src="{{ Storage::url($member->avatar) }}"
                                                    alt="{{ $member->name }}"
                                                    class="h-full w-full object-cover">
                                            </span>
                                            @else
                                            <span
                                                title="{{ $member->name }}"
                                                class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-primary/10 text-[8px] font-semibold text-primary">
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </span>
                                            @endif
                                            @endforeach
                                        </div>

                                        @if ($project->members->count() > 5)
                                        <span class="ml-2 text-[11px] text-slate-400">
                                            +{{ $project->members->count() - 5 }}
                                        </span>
                                        @endif
                                    </button>

                                    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                                        <div
                                            class="fixed inset-0 bg-slate-950/35 backdrop-blur-[1px]"
                                            @click="open = false">
                                        </div>

                                        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                                <div>
                                                    <h3 class="text-sm font-semibold text-slate-900">Members</h3>
                                                    <p class="mt-0.5 text-xs text-slate-400">
                                                        {{ $project->members->count() }} people have access to this project
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="open = false"
                                                    class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                                </button>
                                            </div>

                                            <div class="p-2">
                                                <div class="border-b border-slate-100 p-2">
                                                    <input x-model="search" type="text" placeholder="Search members..."
                                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm outline-none focus:border-primary" />
                                                </div>

                                                <div class="max-h-80 overflow-y-auto p-1.5">
                                                    @foreach ($project->members as $member)
                                                    <div
                                                        x-show="!search || (@js($member->name) + ' ' + @js($member->email)).toLowerCase().includes(search.toLowerCase())"
                                                        class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition hover:bg-slate-50">
                                                        @if ($member->avatar)
                                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-full">
                                                            <img
                                                                src="{{ Storage::url($member->avatar) }}"
                                                                alt="{{ $member->name }}"
                                                                class="h-full w-full object-cover">
                                                        </span>
                                                        @else
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                                        </span>
                                                        @endif

                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-sm font-medium text-slate-800">
                                                                {{ $member->name }}
                                                            </p>
                                                            <p class="truncate text-xs text-slate-400">
                                                                {{ $member->email }}
                                                            </p>
                                                        </div>

                                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">
                                                            @if ($member->pivot?->role === App\Enums\ProjectMemberRole::MANAGER->value)
                                                            {{ App\Enums\ProjectMemberRole::MANAGER->label() }}
                                                            @else
                                                            {{ App\Enums\ProjectMemberRole::MEMBER->label() }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Label</span>

                            @if ($project->label)
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $this->projectLabelHex() }}"></span>

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->label }}
                                </span>
                            </div>
                            @else
                            <span class="text-slate-400">No label</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Start date</span>

                            <div class="flex min-w-0 items-center gap-2">
                                <span class="material-symbols-outlined text-[14px] text-slate-400">
                                    calendar_today
                                </span>

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->start_date?->format('M d, Y') ?? 'No date' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-[92px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Target date</span>

                            <div class="flex min-w-0 items-center gap-2">
                                <span class="material-symbols-outlined text-[14px] text-slate-400">
                                    flag
                                </span>

                                <span class="truncate font-medium text-slate-700">
                                    {{ $project->target_date?->format('M d, Y') ?? 'No date' }}
                                </span>
                            </div>
                        </div>
                    </div>


                    <div class="mt-7 border-t border-slate-200 pt-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Updates</span>
                            <span class="text-xs font-semibold text-slate-800">{{ $project->updates_count }}</span>
                        </div>

                        <div class="mt-4 space-y-4">
                            @php($overviewUpdates = $this->project->updates->where('parent_id', null)->where('type', 'update')->sortByDesc('created_at')->take(3))
                            @forelse ($overviewUpdates as $update)
                            <div class="flex items-start gap-2.5">
                                <span class="relative z-10 mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                                    <span class="material-symbols-outlined text-[11px]
                                            @if ($update->health === 'off_track') text-red-500
                                            @elseif ($update->health === 'at_risk') text-amber-500
                                            @else text-emerald-500
                                            @endif">
                                        show_chart
                                    </span>
                                </span>

                                <div class="min-w-0">
                                    <p class="text-[11px] leading-5 text-slate-600">
                                        <span class="font-medium text-slate-800">
                                            {{ $update->author?->name ?: 'Former team member' }}
                                        </span>
                                        posted a project update
                                    </p>

                                    <p class="mt-0.5 line-clamp-2 text-[11px] leading-5 text-slate-500">
                                        {{ $update->body }}
                                    </p>

                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $update->created_at?->format('M j') }}
                                    </p>
                                </div>
                            </div>
                            @empty
                            <p class="text-xs text-slate-400">No updates yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-7 border-t border-slate-200 pt-6">
                        <span class="text-xs font-medium text-slate-500">Progress</span>

                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">
                                    {{ $this->projectProgress() }}%
                                </span>

                                <span class="text-slate-400">
                                    {{ $project->taskCount(TaskStatus::DONE->value) }}/{{ $project->taskCount() }} solved
                                </span>
                            </div>

                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    style="width: {{ $this->projectProgress() }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-7 border-t border-slate-200 pt-6">
                        <div class="text-[11px] leading-5 text-slate-400">
                            Created
                            <span class="font-medium text-slate-500">
                                {{ $project->created_at?->format('M d, Y') }}
                            </span>

                            @if ($project->creator)
                            by
                            <span class="font-medium text-slate-500">
                                {{ $project->creator->name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @include('pages.admin.workspace.partials._edit-project-modal')

    @include('pages.admin.workspace.partials._editor-script')

    <script>
        window.projectDetailsViewer = function(initialContent = null, wire = null, canEdit = false, canToggleTasks = false) {
            let viewer = null;

            return {
                loading: true,

                async init() {
                    try {
                        const version = '3.30.5';

                        const [
                            core,
                            starter,
                            imageModule,
                            taskListModule,
                            taskItemModule,
                            codeBlockModule,
                            textStyleModule,
                            lowlightModule
                        ] = await Promise.all([
                            import(`https://esm.sh/@tiptap/core@${version}`),
                            import(`https://esm.sh/@tiptap/starter-kit@${version}`),
                            import(`https://esm.sh/@tiptap/extension-image@${version}`),
                            import(`https://esm.sh/@tiptap/extension-task-list@${version}`),
                            import(`https://esm.sh/@tiptap/extension-task-item@${version}`),
                            import(`https://esm.sh/@tiptap/extension-code-block-lowlight@${version}`),
                            import(`https://esm.sh/@tiptap/extension-text-style@${version}`),
                            import('https://esm.sh/lowlight@3'),
                        ]);

                        const {
                            Editor,
                            Node,
                            mergeAttributes,
                            ResizableNodeView
                        } = core;

                        const StarterKit = starter.default ?? starter.StarterKit;
                        const Image = imageModule.default ?? imageModule.Image;
                        const TaskList = taskListModule.default ?? taskListModule.TaskList;
                        const TaskItem = taskItemModule.default ?? taskItemModule.TaskItem;
                        const CodeBlockLowlight = codeBlockModule.default ?? codeBlockModule.CodeBlockLowlight;

                        const {
                            TextStyle,
                            FontFamily
                        } = textStyleModule;

                        const lowlight = lowlightModule.createLowlight(lowlightModule.common);

                        const self = this;

                        const FileAttachment = Node.create({
                            name: 'fileAttachment',
                            group: 'block',
                            atom: true,
                            selectable: false,

                            addAttributes() {
                                return {
                                    url: {
                                        default: null
                                    },
                                    name: {
                                        default: 'Attachment'
                                    },
                                    sizeLabel: {
                                        default: ''
                                    },
                                    mime: {
                                        default: ''
                                    },
                                };
                            },

                            parseHTML() {
                                return [{
                                    tag: 'a[data-file-attachment="true"]'
                                }];
                            },

                            renderHTML({
                                HTMLAttributes
                            }) {
                                const title = HTMLAttributes.sizeLabel ?
                                    `📎 ${HTMLAttributes.name} · ${HTMLAttributes.sizeLabel}` :
                                    `📎 ${HTMLAttributes.name}`;

                                return [
                                    'a',
                                    mergeAttributes(HTMLAttributes, {
                                        href: HTMLAttributes.url,
                                        target: '_blank',
                                        rel: 'noopener noreferrer',
                                        'data-file-attachment': 'true',
                                    }),
                                    title,
                                ];
                            },
                        });

                        const ResizableImage = Image.extend({
                            addNodeView() {
                                if (!this.options.resize || !this.options.resize.enabled || typeof document === 'undefined' || !canEdit) {
                                    return null;
                                }

                                const {
                                    minWidth,
                                    minHeight,
                                    alwaysPreserveAspectRatio
                                } = this.options.resize;

                                return ({
                                    node,
                                    getPos,
                                    HTMLAttributes,
                                    editor
                                }) => {
                                    const el = document.createElement('img');
                                    el.draggable = false;
                                    el.loading = 'lazy';
                                    el.setAttribute('src', HTMLAttributes.src ?? node.attrs.src ?? '');

                                    if (node.attrs.alt != null) el.setAttribute('alt', node.attrs.alt);
                                    if (node.attrs.title != null) el.setAttribute('title', node.attrs.title);

                                    const nodeView = new ResizableNodeView({
                                        element: el,
                                        editor: {
                                            isEditable: true,
                                            on() {},
                                            off() {},
                                        },
                                        node,
                                        getPos,
                                        onResize: (width, height) => {
                                            el.style.width = `${width}px`;
                                            el.style.height = `${height}px`;
                                        },
                                        onCommit: (width, height) => {
                                            const pos = getPos();
                                            if (pos === undefined) return;

                                            editor
                                                .chain()
                                                .setNodeSelection(pos)
                                                .updateAttributes(this.name, {
                                                    width,
                                                    height
                                                })
                                                .run();

                                            self.saveDetails();
                                        },
                                        onUpdate: () => true,
                                        options: {
                                            min: {
                                                width: minWidth,
                                                height: minHeight
                                            },
                                            preserveAspectRatio: alwaysPreserveAspectRatio === true,
                                        },
                                    });

                                    const dom = nodeView.dom;
                                    dom.style.visibility = 'hidden';
                                    dom.style.pointerEvents = 'none';

                                    if (el.complete && el.naturalWidth > 0) {
                                        dom.style.visibility = '';
                                        dom.style.pointerEvents = '';
                                    } else {
                                        el.onload = () => {
                                            dom.style.visibility = '';
                                            dom.style.pointerEvents = '';
                                        };
                                    }

                                    return nodeView;
                                };
                            },
                        }).configure({
                            inline: false,
                            allowBase64: false,
                            HTMLAttributes: {
                                loading: 'lazy'
                            },
                            resize: {
                                enabled: true,
                                minWidth: 80,
                                minHeight: 80,
                                alwaysPreserveAspectRatio: true,
                            },
                        });

                        viewer = new Editor({
                            element: this.$refs.viewer,
                            editable: false,
                            content: this.parseContent(initialContent),

                            extensions: [
                                StarterKit.configure({
                                    codeBlock: false,
                                    link: {
                                        openOnClick: true,
                                        autolink: true,
                                        linkOnPaste: true,
                                        defaultProtocol: 'https',
                                    },
                                }),

                                CodeBlockLowlight.configure({
                                    lowlight
                                }),

                                ResizableImage,

                                TaskList.configure({
                                    HTMLAttributes: {
                                        class: 'project-task-list',
                                    },
                                }),

                                TaskItem.configure({
                                    nested: true,
                                    HTMLAttributes: {
                                        class: 'project-task-item',
                                    },
                                    onReadOnlyChecked: (node, checked) => {
                                        return Boolean(canToggleTasks && viewer);
                                    },
                                }),

                                TextStyle,

                                FontFamily.configure({
                                    types: ['textStyle']
                                }),

                                FileAttachment,
                            ],

                            editorProps: {
                                attributes: {
                                    class: 'project-details-prosemirror tiptap',
                                },
                            },
                        });

                        viewer.view.dom.addEventListener('change', (event) => {
                            if (!canToggleTasks || !viewer) {
                                return;
                            }

                            const target = event.target;
                            if (!(target instanceof HTMLInputElement) || target.type !== 'checkbox') {
                                return;
                            }

                            const listItem = target.closest('li.project-task-item');
                            if (!listItem) {
                                return;
                            }

                            let pos = null;

                            viewer.view.state.doc.descendants((node, nodePos) => {
                                if (node.type.name !== 'taskItem') {
                                    return true;
                                }

                                const rendered = viewer.view.nodeDOM(nodePos);
                                const matches = rendered === listItem || (rendered && rendered.contains(listItem));

                                if (matches) {
                                    pos = nodePos;
                                    return false;
                                }

                                return true;
                            });

                            if (pos === null) {
                                return;
                            }

                            const node = viewer.view.state.doc.nodeAt(pos);

                            if (!node || node.type.name !== 'taskItem') {
                                return;
                            }

                            const tr = viewer.view.state.tr.setNodeMarkup(pos, undefined, {
                                ...node.attrs,
                                checked: Boolean(target.checked),
                            });

                            viewer.view.dispatch(tr);
                            self.saveDetails();
                        });

                        this.loading = false;
                    } catch (error) {
                        console.error('Project details viewer failed to load:', error);
                        this.loading = false;

                        this.$refs.viewer.innerHTML =
                            '<div class="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600">Project details could not be rendered. Check the browser console and network access to esm.sh.</div>';
                    }
                },

                parseContent(content) {
                    if (!content) {
                        return {
                            type: 'doc',
                            content: [{
                                type: 'paragraph'
                            }]
                        };
                    }

                    if (typeof content === 'object') {
                        return content;
                    }

                    try {
                        return JSON.parse(content);
                    } catch (error) {
                        return content;
                    }
                },

                saveDetails() {
                    if (!viewer || !wire || (!canEdit && !canToggleTasks)) {
                        return;
                    }

                    wire.call('saveDetailsDraft', JSON.stringify(viewer.getJSON()));
                },
            };
        };
    </script>

    <style>
        .project-details-readonly .linear-project-editor .tiptap {
            min-height: 0;
            max-height: none;
            overflow: visible;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .project-details-readonly .project-details-prosemirror {
            outline: none;
        }
    </style>
</div>