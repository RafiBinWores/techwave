<?php

use App\Enums\TaskActivityType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\WorkspaceLabel;
use App\Models\WorkspaceTaskComment;
use App\Models\User;
use App\Models\WorkspaceTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin-app')] #[Title('Issue')] class extends Component {
    use WithFileUploads;

    private const MAX_COMMENT_FILE_SIZE = 20480;

    public WorkspaceTask $task;

    public bool $editingTitle = false;
    public bool $editingDescription = false;
    public string $editTitle = '';
    public string $editDescription = '';
    public string $editStatus = '';
    public string $editPriority = '';
    public ?int $editAssigneeId = null;

    public string $newComment = '';
    public array $commentFiles = [];
    public string $newSubtaskTitle = '';
    public bool $showSubtaskForm = false;

    public function mount(WorkspaceTask $task): void
    {
        $this->task = $task->load([
            'project',
            'assignee',
            'creator',
            'labels',
            'subtasks.assignee',
            'comments.user',
            'activities.user',
        ]);

        $this->editStatus = $task->status->value;
        $this->editPriority = $task->priority->value;
        $this->editAssigneeId = $task->assignee_id;
    }

    public function startEditingTitle(): void
    {
        $this->editTitle = $this->task->title;
        $this->resetValidation('editTitle');
        $this->editingTitle = true;
    }

    public function startEditingDescription(): void
    {
        $this->editDescription = $this->task->description ?? '';
        $this->resetValidation('editDescription');
        $this->editingDescription = true;
    }

    public function saveTitle(): void
    {
        if (! $this->editingTitle) {
            return;
        }

        $validated = $this->validateOnly('editTitle', [
            'editTitle' => ['required', 'string', 'max:255'],
        ]);

        $this->task->update(['title' => trim($validated['editTitle'])]);
        $this->editingTitle = false;
        $this->task->load('project');

        $this->logActivity(TaskActivityType::TITLE_CHANGED, null, 'Updated the issue title');

        $this->dispatch('toast', message: 'Title updated.', type: 'success');
    }

    public function saveDescription(): void
    {
        if (! $this->editingDescription) {
            return;
        }

        $validated = $this->validateOnly('editDescription', [
            'editDescription' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->task->update(['description' => $validated['editDescription'] ?: null]);
        $this->editingDescription = false;
        $this->task->load('project');

        $this->logActivity(TaskActivityType::DESCRIPTION_CHANGED, null, 'Updated the issue description');

        $this->dispatch('toast', message: 'Description updated.', type: 'success');
    }

    public function cancelTitleEdit(): void
    {
        $this->editingTitle = false;
        $this->reset('editTitle');
        $this->resetValidation('editTitle');
    }

    public function cancelDescriptionEdit(): void
    {
        $this->editingDescription = false;
        $this->reset('editDescription');
        $this->resetValidation('editDescription');
    }

    public function updateStatus(string $status): void
    {
        $this->editStatus = $status;

        $this->validateOnly('editStatus', [
            'editStatus' => ['required', 'string', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
        ]);

        $this->task->update(['status' => $this->editStatus]);
        $this->task->load('project');

        $this->logActivity(TaskActivityType::STATUS_CHANGED, null, 'Changed status to ' . str_replace('_', ' ', $this->editStatus));

        $this->dispatch('toast', message: 'Status updated.', type: 'success');
    }

    public function updatePriority(string $priority): void
    {
        $this->editPriority = $priority;

        $this->validateOnly('editPriority', [
            'editPriority' => ['required', 'string', 'in:' . implode(',', array_column(TaskPriority::cases(), 'value'))],
        ]);

        $this->task->update(['priority' => $this->editPriority]);
        $this->task->load('project');

        $this->logActivity(TaskActivityType::PRIORITY_CHANGED, null, 'Changed priority to ' . str_replace('_', ' ', $this->editPriority));

        $this->dispatch('toast', message: 'Priority updated.', type: 'success');
    }

    public function updateAssignee(?int $assigneeId): void
    {
        $this->editAssigneeId = $assigneeId;

        $this->validateOnly('editAssigneeId', [
            'editAssigneeId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assignee = $this->editAssigneeId ? User::find($this->editAssigneeId) : null;

        $this->task->update(['assignee_id' => $this->editAssigneeId ?: null]);
        $this->task->load(['project', 'assignee']);

        $this->logActivity(TaskActivityType::ASSIGNEE_CHANGED, null, $assignee ? 'Assigned to ' . $assignee->name : 'Removed assignee');

        $this->dispatch('toast', message: 'Assignee updated.', type: 'success');
    }

    public function addComment(): void
    {
        $validated = $this->validate([
            'newComment' => ['required', 'string', 'min:2', 'max:5000'],
            'commentFiles' => ['nullable', 'array', 'max:5'],
            'commentFiles.*' => ['file', 'max:' . self::MAX_COMMENT_FILE_SIZE, 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,json,html,htm,js,mjs,css,md,sql,xml,php'],
        ]);

        $attachments = [];

        foreach ($validated['commentFiles'] ?? [] as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $phpExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar'];

            $storeName = in_array($extension, $phpExtensions, true)
                ? $file->hashName() . '.txt'
                : $file->hashName() . '.' . ($extension ?: $file->guessExtension());

            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'url' => Storage::disk('public')->url($file->storeAs('workspace/task-comments', $storeName, 'public')),
            ];
        }

        $this->task->comments()->create([
            'user_id' => Auth::id(),
            'body' => trim($validated['newComment']),
            'attachments' => $attachments ?: null,
        ]);

        $this->logActivity(TaskActivityType::COMMENT_ADDED, null, 'Added a comment');

        $this->reset(['newComment', 'commentFiles']);
        $this->resetValidation(['newComment', 'commentFiles']);
        $this->task->load('comments.user');

        $this->dispatch('toast', message: 'Comment added.', type: 'success');
    }

    public function removeCommentFile(int $index): void
    {
        $files = array_values($this->commentFiles);
        unset($files[$index]);
        $this->commentFiles = array_values($files);
    }

    public function deleteComment(int $commentId): void
    {
        $comment = WorkspaceTaskComment::findOrFail($commentId);

        if ($comment->user_id !== Auth::id() && ! in_array(Auth::user()->role->value, ['admin', 'admin_manager'])) {
            $this->dispatch('toast', message: 'You cannot delete this comment.', type: 'error');
            return;
        }

        $comment->delete();
        $this->task->load('comments.user');

        $this->dispatch('toast', message: 'Comment deleted.', type: 'success');
    }

    public function addSubtask(): void
    {
        $this->validate([
            'newSubtaskTitle' => ['required', 'string', 'max:255'],
        ]);

        $prefix = strtoupper(Str::slug($this->task->project->name, '')) ?: 'TASK';
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
        $prefix = substr($prefix, 0, 4);
        $nextNumber = $this->task->project->tasks()->max('id') + 1;

        $this->task->subtasks()->create([
            'project_id' => $this->task->project_id,
            'identifier' => $prefix . '-' . $nextNumber,
            'title' => $this->newSubtaskTitle,
            'status' => TaskStatus::TODO,
            'priority' => TaskPriority::NONE,
            'creator_id' => Auth::id(),
            'sort_order' => $this->task->subtasks()->count(),
        ]);

        $this->newSubtaskTitle = '';
        $this->task->load('subtasks.assignee');

        $this->dispatch('toast', message: 'Subtask added.', type: 'success');
    }

    public function toggleSubtaskStatus(int $subtaskId): void
    {
        $subtask = $this->task->subtasks()->findOrFail($subtaskId);

        $newStatus = $subtask->status === TaskStatus::DONE ? TaskStatus::TODO : TaskStatus::DONE;

        $subtask->update([
            'status' => $newStatus,
        ]);

        $this->task->load('subtasks.assignee');

        $this->dispatch('toast', message: 'Subtask updated.', type: 'success');
    }

    public function deleteSubtask(int $subtaskId): void
    {
        $subtask = $this->task->subtasks()->findOrFail($subtaskId);
        $subtask->delete();

        $this->task->load('subtasks.assignee');

        $this->dispatch('toast', message: 'Subtask deleted.', type: 'success');
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

    public function addLabelFromPicker(string $name, ?string $color = null): void
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 60) {
            return;
        }

        $allowedColors = collect(WorkspaceLabel::colorOptions())->pluck('name')->all();

        if ($color !== null && ! in_array($color, $allowedColors, true)) {
            $color = null;
        }

        $label = $this->task->project->ensureLabel($name, $color);

        if (! $label) {
            return;
        }

        if (! $this->task->labels()->where('id', $label->id)->exists()) {
            $this->task->labels()->attach($label->id);
            $this->logActivity(TaskActivityType::LABEL_ADDED, null, $label->name);
        }

        $this->task->load('labels');
    }

    public function removeLabel(int $labelId): void
    {
        if (! $this->task->labels()->where('id', $labelId)->exists()) {
            return;
        }

        $label = $this->task->labels()->find($labelId);
        $this->task->labels()->detach($labelId);
        $this->logActivity(TaskActivityType::LABEL_REMOVED, $label?->name, null);

        $this->task->load('labels');
    }

    public function staff()
    {
        return User::query()
            ->whereIn('role', ['admin', 'manager', 'staff', 'admin_manager'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function canDeleteTask(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $this->task->creator_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role->value, ['admin', 'admin_manager'], true)) {
            return true;
        }

        return (int) $this->task->project->creator_id === (int) $user->id;
    }

    public function deleteTask(): void
    {
        abort_unless($this->canDeleteTask(), 403);

        $projectId = $this->task->project_id;

        $this->task->delete();

        $this->dispatch('toast', message: 'Issue deleted.', type: 'success');

        $this->redirectRoute('admin.workspace.projects.show.issues', $projectId, navigate: true);
    }

    public function statusColor(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::BACKLOG => 'bg-slate-100 text-slate-700',
            TaskStatus::TODO => 'bg-blue-100 text-blue-700',
            TaskStatus::IN_PROGRESS => 'bg-amber-100 text-amber-700',
            TaskStatus::DONE => 'bg-emerald-100 text-emerald-700',
            TaskStatus::CANCELLED => 'bg-red-100 text-red-700',
        };
    }

    public function priorityColor(TaskPriority $priority): string
    {
        return match ($priority) {
            TaskPriority::URGENT => 'bg-red-100 text-red-700',
            TaskPriority::HIGH => 'bg-orange-100 text-orange-700',
            TaskPriority::MEDIUM => 'bg-yellow-100 text-yellow-700',
            TaskPriority::LOW => 'bg-blue-100 text-blue-700',
            TaskPriority::NONE => 'bg-slate-100 text-slate-600',
        };
    }

    public function labelDotColor(string $color): string
    {
        return match ($color) {
            'slate' => 'bg-slate-500',
            'gray' => 'bg-gray-500',
            'red' => 'bg-red-500',
            'orange' => 'bg-orange-500',
            'amber' => 'bg-amber-500',
            'yellow' => 'bg-yellow-500',
            'lime' => 'bg-lime-500',
            'green' => 'bg-green-500',
            'emerald' => 'bg-emerald-500',
            'teal' => 'bg-teal-500',
            'cyan' => 'bg-cyan-500',
            'sky' => 'bg-sky-500',
            'blue' => 'bg-blue-500',
            'indigo' => 'bg-indigo-500',
            'violet' => 'bg-violet-500',
            'purple' => 'bg-purple-500',
            'fuchsia' => 'bg-fuchsia-500',
            'pink' => 'bg-pink-500',
            'rose' => 'bg-rose-500',
            default => 'bg-slate-500',
        };
    }

    public function activityIcon(TaskActivityType $type): string
    {
        return match ($type) {
            TaskActivityType::CREATED => 'add_task',
            TaskActivityType::STATUS_CHANGED => 'swap_horiz',
            TaskActivityType::PRIORITY_CHANGED => 'flag',
            TaskActivityType::ASSIGNEE_CHANGED => 'person',
            TaskActivityType::TITLE_CHANGED => 'title',
            TaskActivityType::DESCRIPTION_CHANGED => 'notes',
            TaskActivityType::DUE_DATE_CHANGED => 'event',
            TaskActivityType::LABEL_ADDED => 'label',
            TaskActivityType::LABEL_REMOVED => 'sell',
            TaskActivityType::COMMENT_ADDED => 'comment',
        };
    }

    protected function logActivity(TaskActivityType $type, ?string $oldValue, ?string $newValue): void
    {
        $this->task->activities()->create([
            'user_id' => Auth::id(),
            'type' => $type,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);

        $this->task->load('activities.user');
    }
};
?>

<div class="min-h-full">
    <div class="mx-auto w-full px-4 pb-10 lg:px-0">

        {{-- Header --}}
        <div class="border-b border-slate-200">
            <div class="flex items-center gap-3 pb-4">
                <a href="{{ route('admin.workspace.projects.show.issues', $this->task->project) }}"
                    wire:navigate
                    class="flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    {{ $this->task->project->name }}
                </a>

                <span class="text-slate-300">/</span>

                <span class="font-mono text-xs text-slate-400">{{ $this->task->identifier }}</span>

                @if ($this->canDeleteTask())
                <button type="button" wire:click="deleteTask"
                    wire:confirm="Delete this issue permanently?"
                    class="ml-auto rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                    title="Delete issue">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Main grid --}}
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_330px] xl:h-[calc(100dvh-140px)] xl:overflow-hidden">

            {{-- LEFT: Main content --}}
            <main class="workspace-scroll min-w-0 xl:h-full xl:min-h-0 xl:overflow-x-hidden xl:overflow-y-auto xl:border-r xl:border-slate-200 xl:pr-8">

                <div class="space-y-6 py-6">

                    {{-- Title --}}
                    <div>
                        @if ($editingTitle)
                            <input type="text" wire:model="editTitle"
                                wire:keydown.enter="saveTitle"
                                wire:keydown.escape="cancelTitleEdit"
                                wire:blur="saveTitle"
                                x-data x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xl font-semibold text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                            @error('editTitle')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-[11px] text-slate-400">Press Enter or click away to save. Press Esc to cancel.</p>
                        @else
                            <h1 wire:click="startEditingTitle"
                                class="group inline-flex cursor-text items-start gap-2 text-2xl font-semibold leading-tight text-on-surface transition-colors hover:text-slate-700">
                                <span>{{ $this->task->title }}</span>
                                <span class="material-symbols-outlined mt-1 text-[18px] text-slate-300 transition group-hover:text-slate-500">edit</span>
                            </h1>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="border-b border-slate-100 pb-6">
                        @if ($editingDescription)
                            <input type="text" wire:model="editDescription"
                                wire:keydown.enter="saveDescription"
                                wire:keydown.escape="cancelDescriptionEdit"
                                wire:blur="saveDescription"
                                x-data x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                                placeholder="Add a description...">
                            @error('editDescription')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        @else
                            <div wire:click="startEditingDescription"
                                class="group cursor-text rounded-lg border border-transparent px-2 py-1 text-sm leading-relaxed text-slate-700 transition-colors hover:border-slate-200 hover:bg-slate-50/50">
                                @if ($this->task->description)
                                    <p class="truncate" title="{{ $this->task->description }}">{{ $this->task->description }}</p>
                                @else
                                    <p class="text-slate-400">No description provided. Click to add one.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Subtasks --}}
                    @php
                        $totalSubtasks = $this->task->subtasks->count();
                        $doneSubtasks = $this->task->subtasks->where('status', TaskStatus::DONE)->count();
                    @endphp

                    @if ($totalSubtasks > 0)
                        <div class="border-t border-slate-100 pt-6">
                            <div class="mb-3 flex items-center justify-between">
                                <h2 class="text-sm font-medium text-on-surface">
                                    Subtasks
                                    <span class="text-slate-400">{{ $doneSubtasks }} / {{ $totalSubtasks }}</span>
                                </h2>
                            </div>

                            <div class="mb-4 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-primary transition-all" style="width: {{ ($doneSubtasks / $totalSubtasks) * 100 }}%"></div>
                            </div>

                            <div class="overflow-hidden rounded-lg border border-slate-100">
                                @foreach ($this->task->subtasks as $subtask)
                                    <div wire:key="subtask-{{ $subtask->id }}"
                                        class="group flex items-center gap-3 border-b border-slate-100 bg-white px-3 py-2.5 transition-colors last:border-b-0 hover:bg-slate-50/50">
                                        <button type="button" wire:click="toggleSubtaskStatus({{ $subtask->id }})"
                                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition-colors {{ $subtask->status === TaskStatus::DONE ? 'border-primary bg-primary text-white' : 'border-slate-300 bg-white text-transparent hover:border-primary' }}">
                                            <span class="material-symbols-outlined text-[14px]">check</span>
                                        </button>

                                        <span class="min-w-0 flex-1 truncate text-sm {{ $subtask->status === TaskStatus::DONE ? 'text-slate-400 line-through' : 'text-on-surface' }}">
                                            {{ $subtask->title }}
                                        </span>

                                        @if ($subtask->assignee)
                                            <div class="hidden shrink-0 items-center gap-1.5 lg:flex">
                                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-600">
                                                    {{ strtoupper(substr($subtask->assignee->name, 0, 1)) }}
                                                </div>
                                                <span class="text-xs text-slate-400">{{ $subtask->assignee->name }}</span>
                                            </div>
                                        @endif

                                        <button type="button" wire:click="deleteSubtask({{ $subtask->id }})"
                                            wire:confirm="Delete this subtask?"
                                            class="shrink-0 text-slate-300 opacity-0 transition group-hover:opacity-100 hover:text-red-500">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="border-t border-slate-100 pt-6">
                            @if ($showSubtaskForm)
                                <form wire:submit.prevent="addSubtask" class="flex items-center gap-2" x-data
                                    x-init="$nextTick(() => $refs.subtaskInput.focus())">
                                    <input x-ref="subtaskInput" type="text" wire:model="newSubtaskTitle"
                                        placeholder="Add a subtask..."
                                        class="w-full rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm text-on-surface outline-none transition focus:border-solid focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <button type="submit" wire:loading.attr="disabled"
                                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-60">
                                        Add
                                    </button>
                                </form>
                            @else
                                <button type="button" wire:click="$set('showSubtaskForm', true)"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-secondary transition-colors hover:text-on-surface">
                                    <span class="material-symbols-outlined text-[14px]">add</span>
                                    Add sub-issue
                                </button>
                            @endif

                            @error('newSubtaskTitle')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Comments --}}
                    <div class="border-t border-slate-100 pt-6">
                        <h2 class="mb-4 text-sm font-medium text-on-surface">
                            Comments
                            <span class="text-slate-400">{{ $this->task->comments->count() }}</span>
                        </h2>

                        <div class="space-y-5">
                            @forelse ($this->task->comments as $comment)
                                <div wire:key="comment-{{ $comment->id }}" class="group flex gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                        {{ strtoupper(substr($comment->user?->name ?? 'U', 0, 1)) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-on-surface">
                                                {{ $comment->user?->name ?? 'Unknown User' }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">
                                            {!! nl2br(e($comment->body)) !!}
                                        </p>

                                        @if ($comment->attachments)
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach ($comment->attachments as $attachment)
                                                    @php $isImage = str_starts_with($attachment['mime'] ?? '', 'image/'); @endphp
                                                    <a href="{{ $attachment['url'] }}"
                                                        {{ $isImage ? 'target=_blank rel=noopener' : "download=\"{$attachment['name']}\"" }}
                                                        class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                                                        <span class="material-symbols-outlined text-[13px] text-slate-400">
                                                            {{ $isImage ? 'image' : 'insert_drive_file' }}
                                                        </span>
                                                        <span class="truncate">{{ $attachment['name'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @if ($comment->user_id === Auth::id() || in_array(Auth::user()->role->value, ['admin', 'admin_manager']))
                                        <button type="button" wire:click="deleteComment({{ $comment->id }})"
                                            wire:confirm="Delete this comment?"
                                            class="shrink-0 self-start text-slate-300 opacity-0 transition group-hover:opacity-100 hover:text-red-500">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">No comments yet. Start the discussion.</p>
                            @endforelse
                        </div>

                        <form wire:submit.prevent="addComment" x-data="{ open: false }"
                            class="mt-5 rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="p-3">
                                <textarea wire:model="newComment"
                                    x-on:focus="open = true"
                                    x-on:keydown.enter.exact.prevent="$wire.addComment()"
                                    rows="1"
                                    maxlength="5000"
                                    placeholder="Write a comment..."
                                    class="w-full resize-none bg-transparent py-1 text-sm leading-6 text-slate-700 outline-none placeholder:text-slate-300"></textarea>

                                <div x-show="open" x-cloak x-transition class="border-t border-slate-100 pt-3">
                                    {{-- Attached files --}}
                                    @if ($this->commentFiles)
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach ($this->commentFiles as $index => $file)
                                                <span wire:key="comment-file-{{ $index }}" class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600">
                                                    <span class="material-symbols-outlined text-[13px] text-slate-400">insert_drive_file</span>
                                                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                                    <button
                                                        type="button"
                                                        @click="$wire.removeCommentFile({{ $index }})"
                                                        class="flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition hover:bg-slate-200 hover:text-slate-700">
                                                        <span class="material-symbols-outlined text-[12px]">close</span>
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            @error('newComment')
                                                <p class="text-[11px] text-red-500">{{ $message }}</p>
                                            @enderror

                                            @error('commentFiles')
                                                <p class="text-[11px] text-red-500">{{ $message }}</p>
                                            @enderror

                                            <label class="flex h-7 cursor-pointer items-center gap-1 rounded-md px-2 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                                                <span class="material-symbols-outlined text-[13px]">attach_file</span>
                                                Attach
                                                <input type="file" wire:model="commentFiles" multiple class="sr-only" />
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="open = false; $wire.set('commentFiles', [])"
                                                class="h-7 cursor-pointer rounded-md px-2.5 text-[11px] font-medium text-slate-500 transition hover:bg-slate-100">
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                wire:loading.attr="disabled"
                                                wire:target="addComment"
                                                class="inline-flex h-7 items-center gap-1 rounded-md bg-slate-900 px-3 text-[11px] font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                                                <span wire:loading.remove wire:target="addComment">Post</span>
                                                <span wire:loading wire:target="addComment" class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Activity --}}
                    <div class="border-t border-slate-100 pt-6">
                        <h2 class="mb-3 text-sm font-medium text-on-surface">Activity</h2>

                        <div class="space-y-4">
                            @forelse ($this->task->activities->sortByDesc('created_at')->take(15) as $activity)
                                <div class="flex gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined text-[15px]">{{ $this->activityIcon($activity->type) }}</span>
                                    </div>

                                    <div class="min-w-0 flex-1 text-sm">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                            <span class="font-medium text-on-surface">
                                                {{ $activity->user?->name ?? 'Unknown User' }}
                                            </span>
                                            <span class="text-xs text-slate-400">{{ $activity->type->label() }}</span>
                                        </div>

                                        @if ($activity->old_value || $activity->new_value)
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                @if ($activity->old_value && $activity->new_value)
                                                    <span class="text-slate-400 line-through">{{ $activity->old_value }}</span>
                                                    <span class="text-slate-400">&rarr;</span>
                                                    <span class="text-on-surface">{{ $activity->new_value }}</span>
                                                @else
                                                    <span class="text-on-surface">{{ $activity->new_value ?: $activity->old_value }}</span>
                                                @endif
                                            </p>
                                        @endif

                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">No activity yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </main>

            {{-- RIGHT: Project sidebar --}}
            <aside class="workspace-scroll min-w-0 xl:h-full xl:min-h-0 xl:overflow-x-hidden xl:overflow-y-auto xl:pl-6">

                <div class="space-y-0 py-6">

                    {{-- Project card --}}
                    <div>
                        <a href="{{ route('admin.workspace.projects.show.overview', $this->task->project) }}"
                            wire:navigate
                            class="group flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-primary"
                                style="color: {{ $this->task->project->icon_color ?: '#4f46e5' }}">
                                <span class="material-symbols-outlined text-[21px]">
                                    {{ $this->task->project->icon ?: 'space_dashboard' }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800 group-hover:text-primary">
                                    {{ $this->task->project->name }}
                                </p>
                                <p class="truncate text-[11px] text-slate-400">
                                    {{ $this->task->project->description ?: 'No description' }}
                                </p>
                            </div>
                        </a>
                    </div>

                    {{-- Properties --}}
                    <div class="mt-6 space-y-0.5">
                        {{-- Status --}}
                        <div class="grid grid-cols-[96px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Status</span>
                            <select wire:model.live="editStatus" wire:change="updateStatus($event.target.value)"
                                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-on-surface outline-none transition focus:border-primary">
                                @foreach (TaskStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="grid grid-cols-[96px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Priority</span>
                            <select wire:model.live="editPriority" wire:change="updatePriority($event.target.value)"
                                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-on-surface outline-none transition focus:border-primary">
                                @foreach (TaskPriority::cases() as $priorityOption)
                                    <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assignee --}}
                        <div class="grid grid-cols-[96px_minmax(0,1fr)] items-center gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="text-slate-400">Assignee</span>
                            <select wire:model.live="editAssigneeId" wire:change="updateAssignee($event.target.value ? (int) $event.target.value : null)"
                                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-on-surface outline-none transition focus:border-primary">
                                <option value="">Unassigned</option>
                                @foreach ($this->staff() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Labels --}}
                        <div class="grid grid-cols-[96px_minmax(0,1fr)] items-start gap-3 rounded-md px-2 py-2 text-xs hover:bg-slate-50">
                            <span class="pt-1 text-slate-400">Labels</span>

                            <div>
                                @if ($this->task->labels->count())
                                    <div class="mb-2 flex flex-wrap gap-1.5">
                                        @foreach ($this->task->labels as $label)
                                            <span wire:key="label-{{ $label->id }}"
                                                class="group inline-flex items-center gap-1.5 rounded-md px-1 py-0.5 text-xs font-medium text-slate-600">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $this->labelDotColor($label->color) }}"></span>
                                                {{ $label->name }}
                                                <button type="button" wire:click="removeLabel({{ $label->id }})"
                                                    class="opacity-0 transition group-hover:opacity-100 hover:text-red-600">
                                                    <span class="material-symbols-outlined text-[13px]">close</span>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <span class="relative block" x-data="{ open: false, search: '', color: 'slate', colors: @js(WorkspaceLabel::colorOptions()), labels: @js($this->existingLabels()), filteredLabels() { const q = (this.search || '').toLowerCase().trim(); const list = q ? this.labels.filter(l => l.name.toLowerCase().includes(q)) : this.labels; return list.slice(0, 20); }, isNewLabel() { const q = (this.search || '').trim(); return q.length > 0 && !this.labels.some(l => l.name.toLowerCase() === q.toLowerCase()); } }" @click.outside="open = false">
                                    <div class="relative">
                                        <input type="text" x-model="search" placeholder="Add label..."
                                            @focus="open = true"
                                            @input="open = true"
                                            class="w-full rounded-lg border border-dashed border-slate-300 bg-white py-1.5 pl-3 pr-8 text-xs text-on-surface outline-none transition hover:border-solid hover:border-slate-400 focus:border-solid focus:border-primary" />
                                        <span class="material-symbols-outlined pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                                    </div>

                                    <div x-cloak x-show="open && (filteredLabels().length > 0 || isNewLabel())" x-transition
                                        class="absolute left-0 z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div x-show="filteredLabels().length > 0" class="max-h-44 overflow-y-auto p-1">
                                            <template x-for="label in filteredLabels()" :key="label.name">
                                                <button type="button"
                                                    @click="$wire.addLabelFromPicker(label.name, label.color); search = ''; color = label.color"
                                                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                                    <span class="h-2 w-2 shrink-0 rounded-full" :style="`background-color: ${label.hex}`"></span>
                                                    <span class="flex-1 truncate" x-text="label.name"></span>
                                                    <span class="material-symbols-outlined text-[13px] text-slate-300">add</span>
                                                </button>
                                            </template>
                                        </div>

                                        <div x-show="isNewLabel()" class="border-t border-slate-100 p-2">
                                            <div class="mb-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-400">New label color</div>
                                            <div class="flex w-full flex-wrap gap-1.5">
                                                <template x-for="c in colors" :key="c.name">
                                                    <button type="button"
                                                        @click="$wire.addLabelFromPicker(search, c.name); search = ''; color = c.name; open = false"
                                                        class="flex h-5 w-5 items-center justify-center rounded-full transition hover:scale-110"
                                                        :style="`background-color: ${c.hex}`">
                                                        <span x-show="color === c.name" class="material-symbols-outlined text-[12px] text-white">check</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Created --}}
                    <div class="mt-7 border-t border-slate-200 pt-6">
                        <div class="text-[11px] leading-5 text-slate-400">
                            Created
                            <span class="font-medium text-slate-500">
                                {{ $this->task->created_at?->format('M d, Y') }}
                            </span>

                            @if ($this->task->creator)
                            by
                            <span class="font-medium text-slate-500">
                                {{ $this->task->creator->name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
