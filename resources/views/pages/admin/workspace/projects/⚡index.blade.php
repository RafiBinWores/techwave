<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\WorkspaceLabel;
use App\Models\User;
use App\Models\WorkspaceProject;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Workspace')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    private const MAX_EDITOR_FILE_SIZE = 20480;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 12;

    public bool $showCreateModal = false;

    public string $formName = '';
    public string $formSlug = '';
    public string $formDescription = '';
    public string $formStatus = 'backlog';
    public string $formPriority = 'none';
    public string $formLabel = '';
    public string $formLabelColor = 'slate';
    public string $formIcon = '';
    public string $formIconColor = '';
    public ?string $formDetails = null;
    public $editorUpload = null;
    public ?string $formStartDate = null;
    public ?string $formTargetDate = null;
    public ?int $formProjectManagerId = null;
    public ?int $formClientId = null;
    public array $formMemberIds = [];

    protected function rules(): array
    {
        return [
            'formName' => ['required', 'string', 'max:180'],
            'formSlug' => ['nullable', 'string', 'max:220', 'unique:workspace_projects,slug'],
            'formDescription' => ['nullable', 'string', 'max:300'],
            'formDetails' => ['nullable', 'string'],
            'formStatus' => ['required', 'string', 'in:backlog,planning,in_progress,completed,cancelled'],
            'formPriority' => ['required', 'string', 'in:urgent,high,medium,low,none'],
            'formLabel' => ['nullable', 'string', 'max:100'],
            'formIcon' => ['nullable', 'string', 'max:100'],
            'formIconColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'formStartDate' => ['nullable', 'date'],
            'formTargetDate' => ['nullable', 'date', 'after_or_equal:formStartDate'],
            'formProjectManagerId' => ['nullable', 'integer', 'exists:users,id'],
            'formClientId' => ['nullable', 'integer', 'exists:users,id'],
            'formMemberIds' => ['nullable', 'array'],
            'formMemberIds.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedFormName(): void
    {
        $this->formSlug = Str::slug($this->formName);
    }

    public function toggleMember(int $userId): void
    {
        $allowed = $this->staff()->pluck('id')->all();

        abort_unless(in_array($userId, $allowed, true), 422);

        $key = array_search($userId, $this->formMemberIds, true);

        if ($key === false) {
            $this->formMemberIds[] = $userId;
        } else {
            unset($this->formMemberIds[$key]);
            $this->formMemberIds = array_values($this->formMemberIds);
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['formName', 'formSlug', 'formDescription', 'formDetails', 'editorUpload', 'formPriority', 'formLabel', 'formLabelColor', 'formIcon', 'formIconColor', 'formStartDate', 'formTargetDate', 'formProjectManagerId', 'formClientId', 'formMemberIds']);
        $this->formStatus = 'backlog';
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createProject(): void
    {
        $validated = $this->validate();

        $project = WorkspaceProject::create([
            'name' => $validated['formName'],
            'slug' => $this->uniqueSlug($this->formSlug ?: $validated['formName']),
            'description' => $validated['formDescription'] ?: null,
            'status' => $validated['formStatus'],
            'priority' => $validated['formPriority'],
            'icon' => $validated['formIcon'] ?: null,
            'icon_color' => $validated['formIconColor'] ?: null,
            'start_date' => $validated['formStartDate'] ?: null,
            'target_date' => $validated['formTargetDate'] ?: null,
            'creator_id' => auth()->id(),
            'client_id' => $validated['formClientId'] ?: null,
        ]);

        // Keep the short project summary separate from the rich Linear-style document.
        // `details` should be a nullable LONGTEXT/TEXT column containing Tiptap JSON.
        $project->details = $validated['formDetails'] ?: null;
        $project->save();

        if ($this->formProjectManagerId) {
            $project->members()->attach($this->formProjectManagerId, ['role' => ProjectMemberRole::MANAGER->value]);
        }

        $memberIds = collect($this->formMemberIds)
            ->filter(fn($id) => $id != $this->formProjectManagerId)
            ->unique();

        foreach ($memberIds as $memberId) {
            $project->members()->attach($memberId, ['role' => ProjectMemberRole::MEMBER->value]);
        }

        $project->ensureLabel($validated['formLabel'] ?? '', $this->formLabelColor);

        $notificationIds = collect($this->formMemberIds)
            ->push($this->formProjectManagerId)
            ->filter()
            ->unique()
            ->reject(fn($id) => (int) $id === (int) auth()->id());

        UserNotificationService::notifyUsers(
            $notificationIds,
            'New project assigned to you',
            $validated['formName'],
            auth()->user()->name,
            route('admin.workspace.projects.show.overview', $project),
            'project'
        );

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Project created successfully.',
        ]);

        $this->redirectRoute('admin.workspace.projects.show.overview', $project, navigate: true);
    }

    public function storeEditorUpload(): array
    {
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

    private function uniqueSlug(string $value): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 1;

        while (WorkspaceProject::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function staff()
    {
        return User::query()
            ->whereIn('role', ['admin', 'manager', 'staff', 'admin_manager'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function selectedManagerName(): ?string
    {
        if (! $this->formProjectManagerId) {
            return null;
        }

        return $this->staff()->firstWhere('id', $this->formProjectManagerId)?->name;
    }

    public function clients()
    {
        return User::query()
            ->where('role', UserRole::CLIENT->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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

    public function selectedClientName(): ?string
    {
        if (! $this->formClientId) {
            return null;
        }

        return $this->clients()->firstWhere('id', $this->formClientId)?->name;
    }

    public function iconColor(): string
    {
        return $this->formIconColor ?: '#4f46e5';
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

    public function projects()
    {
        $search = trim($this->search);

        return WorkspaceProject::query()
            ->with(['creator', 'members'])
            ->withCount(['tasks', 'members'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function delete(int $projectId): void
    {
        $project = WorkspaceProject::findOrFail($projectId);
        $project->delete();

        $this->dispatch('toast', message: 'Project deleted successfully.', type: 'success');
    }

    public function statusDot(ProjectStatus $status): string
    {
        return match ($status) {
            ProjectStatus::BACKLOG => 'bg-slate-300',
            ProjectStatus::PLANNING => 'bg-blue-500',
            ProjectStatus::IN_PROGRESS => 'bg-amber-500',
            ProjectStatus::COMPLETED => 'bg-emerald-500',
            ProjectStatus::CANCELLED => 'bg-red-400',
        };
    }

    public function statusColor(ProjectStatus $status): string
    {
        return match ($status) {
            ProjectStatus::BACKLOG => 'bg-slate-100 text-slate-700',
            ProjectStatus::PLANNING => 'bg-blue-100 text-blue-700',
            ProjectStatus::IN_PROGRESS => 'bg-amber-100 text-amber-700',
            ProjectStatus::COMPLETED => 'bg-emerald-100 text-emerald-700',
            ProjectStatus::CANCELLED => 'bg-red-100 text-red-700',
        };
    }

    public function priorityColor(ProjectPriority $priority): string
    {
        return match ($priority) {
            ProjectPriority::URGENT => 'bg-red-100 text-red-700',
            ProjectPriority::HIGH => 'bg-orange-100 text-orange-700',
            ProjectPriority::MEDIUM => 'bg-yellow-100 text-yellow-700',
            ProjectPriority::LOW => 'bg-blue-100 text-blue-700',
            ProjectPriority::NONE => 'bg-slate-100 text-slate-600',
        };
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Workspace
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage your projects, assign tasks and track progress with your team.
                </p>
            </div>

            <div class="flex w-full flex-col gap-4 lg:w-auto lg:flex-row lg:items-center">
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                    <div class="relative sm:col-span-2 lg:col-span-1">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            search
                        </span>

                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Search projects, teams, tasks..."
                            class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>

                    <div class="relative">
                        <select
                            wire:model.live="status"
                            class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="all">All Status</option>

                            @foreach (ProjectStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>

                        <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                            expand_more
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98] sm:w-auto lg:self-start cursor-pointer">
                    <span class="material-symbols-outlined text-lg">add</span>
                    New Project
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Project</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Tasks</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Team</th>
                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Target</th>
                            <th class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->projects() as $project)
                        <tr wire:key="project-{{ $project->id }}" class="group transition-colors hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white"
                                        style="color: {{ $project->icon_color ?: '#4f46e5' }}">
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ $project->icon ?: 'space_dashboard' }}
                                        </span>
                                    </div>

                                    <a href="{{ route('admin.workspace.projects.show.overview', $project) }}" wire:navigate class="min-w-0 block">
                                        <span class="block text-label-md font-label-md text-on-surface transition-colors group-hover:text-primary">
                                            {{ $project->name }}
                                        </span>

                                        <span class="block max-w-sm truncate text-[11px] text-secondary">
                                            {{ $project->description ?: 'No description' }}
                                        </span>
                                    </a>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusColor($project->status) }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $this->statusDot($project->status) }}"></span>
                                    {{ $project->status->label() }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-body-sm text-secondary">
                                {{ $project->tasks_count }} tasks
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex -space-x-1.5">
                                        @foreach ($project->members->take(4) as $member)
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-primary/10 text-[9px] font-bold text-primary">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        @endforeach
                                    </div>

                                    @if ($project->members_count > 4)
                                    <span class="text-[11px] text-secondary">+{{ $project->members_count - 4 }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 text-body-sm text-secondary">
                                {{ $project->target_date?->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button type="button" @click="open = !open"
                                        class="text-slate-400 transition-colors hover:text-primary">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>

                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        <a href="{{ route('admin.workspace.projects.show.overview', $project) }}" wire:navigate
                                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">dashboard</span>
                                            Open Overview
                                        </a>

                                        <button
                                            type="button"
                                            wire:click="delete({{ $project->id }})"
                                            wire:confirm="Are you sure you want to delete this project? All tasks will also be deleted."
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
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined">space_dashboard</span>
                                    </div>

                                    <h3 class="text-base font-semibold text-on-surface">
                                        No projects yet
                                    </h3>

                                    <p class="mt-1 text-sm text-secondary">
                                        Create your first project to start managing tasks.
                                    </p>

                                    <button type="button" wire:click="openCreateModal"
                                        class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90 cursor-pointer">
                                        Create Project
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->projects()->hasPages())
            <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
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
            @endif
        </div>
    </div>

    {{-- Create project modal --}}
    @if ($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/40" wire:click="$set('showCreateModal', false)"></div>

        <div class="relative mx-auto my-10 w-full max-w-5xl rounded-2xl bg-white shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-8 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-on-surface">Create New Project</h3>
                    <p class="mt-0.5 text-sm text-secondary">Fill in the details to get started.</p>
                </div>

                <button type="button" wire:click="$set('showCreateModal', false)"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>

            <form wire:submit.prevent="createProject" class="max-h-[calc(100vh-9rem)] overflow-y-auto">
                {{-- Icon selector (top) --}}
                <div class="px-8 pt-2">
                    <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative flex items-center gap-3">
                        <button type="button" @click="open = !open"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border transition hover:scale-105 cursor-pointer"
                            style="background-color: {{ $this->iconColor() }}1f; border-color: {{ $this->iconColor() }}40; color: {{ $this->iconColor() }}"
                            title="Choose icon & color">
                            <span class="material-symbols-outlined text-[24px]">
                                {{ $this->formIcon ?: 'space_dashboard' }}
                            </span>
                        </button>

                        <div class="min-w-0 flex-1">
                            @if ($this->formIcon)
                            <p class="truncate text-sm font-medium text-on-surface">
                                {{ $this->formIcon }}
                            </p>
                            <button type="button" @click="$wire.set('formIcon', '')"
                                class="mt-1 cursor-pointer text-xs text-slate-400 transition hover:text-red-500">
                                Change icon
                            </button>
                            @else
                            <p class="text-sm text-slate-400">Choose an icon & color</p>
                            @endif
                        </div>

                        <div x-cloak x-show="open" x-transition
                            class="absolute left-0 right-0 top-0 z-40 mt-1.5 max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="sticky top-0 border-b border-slate-100 bg-white p-2">
                                <input x-model="search" type="text" placeholder="Search icons..."
                                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm outline-none focus:border-primary" />
                            </div>

                            {{-- Icon color --}}
                            <div class="border-b border-slate-100 p-3">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                    Icon color
                                </p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <input type="color"
                                        :value="($wire.formIconColor || '#4f46e5')"
                                        @input="$wire.set('formIconColor', $event.target.value)"
                                        class="h-6 w-10 cursor-pointer rounded-full border border-black/10 bg-transparent p-0"
                                        title="Custom color" />
                                    <span class="mr-1 h-4 w-px bg-slate-100"></span>
                                    @foreach ($this->projectIconColors() as $color)
                                    <button type="button"
                                        @click="$wire.set('formIconColor', @js($color))"
                                        wire:key="color-{{ $color }}"
                                        @class([ 'h-6 w-6 rounded-full border border-black/10 transition cursor-pointer' , 'ring-2 ring-primary ring-offset-2'=> $this->formIconColor === $color,
                                        'hover:scale-110' => $this->formIconColor !== $color,
                                        ])
                                        style="background-color: {{ $color }}"
                                        title="{{ $color }}"></button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid max-h-100 grid-cols-6 gap-1 overflow-y-auto p-2">
                                @foreach ($this->projectIcons() as $icon)
                                <button type="button"
                                    @click="open = false; search = ''; $wire.set('formIcon', @js($icon))"
                                    wire:key="icon-{{ $icon }}"
                                    x-show="!search || @js($icon).includes(search.toLowerCase())"
                                    @class([ 'flex h-10 w-10 items-center justify-center rounded-lg border transition cursor-pointer' , 'border-primary bg-primary/10'=> $this->formIcon === $icon,
                                    'border-transparent hover:border-slate-200 hover:bg-slate-50' => $this->formIcon !== $icon,
                                    ])>
                                    <span class="material-symbols-outlined text-[20px]" style="color: {{ $this->iconColor() }}">{{ $icon }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        @error('formIcon')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        @error('formIconColor')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Title --}}
                <div class="px-8 pt-6">
                    <input
                        type="text"
                        wire:model.live="formName"
                        placeholder="Name your project..."
                        class="w-full bg-transparent text-2xl font-bold text-on-surface placeholder:text-slate-300 outline-none focus:ring-0" />

                    @error('formName')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Summary --}}
                <div class="px-8 pt-3">
                    <div x-data="{ chars: 0 }" class="relative">
                        <textarea
                            wire:model.live="formDescription"
                            @input="chars = $el.value.length; const lh = parseFloat(getComputedStyle($el).lineHeight) || 24; const lines = Math.max(1, Math.round($el.scrollHeight / lh)); $el.style.height = (lines * lh) + 'px'"
                            @keydown.enter.prevent
                            placeholder="Add a short summary of what this project is about..."
                            maxlength="300"
                            class="w-full bg-transparent p-0 text-sm text-on-surface placeholder:text-slate-300 outline-none focus:ring-0 resize-none overflow-hidden"
                            style="height: 1.5rem; min-height: 1.5rem; line-height: 1.5rem;"
                            spellcheck="false"></textarea>

                        <div x-show="chars >= 300" class="absolute right-2 bottom-1 text-[10px] text-red-500 font-medium">
                            <span x-text="chars"></span>/300 chars
                        </div>

                        <div x-show="chars >= 300" class="text-[10px] text-red-500">
                            Maximum 300 characters reached
                        </div>

                        @error('formDescription')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Linear-style property row (single line, small text, no labels) --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-slate-100 px-8 py-3 text-sm text-slate-600">
                    {{-- Status --}}
                    <span class="relative">
                        <select wire:model.live="formStatus"
                            class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                            @foreach (ProjectStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                    </span>

                    {{-- Priority --}}
                    <span class="relative">
                        <select wire:model.live="formPriority"
                            class="h-7 appearance-none rounded-md border border-slate-200 bg-white pl-1.5 pr-6 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                            @foreach (ProjectPriority::cases() as $priorityOption)
                            <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                    </span>

                    {{-- Label (type to create, pick existing) --}}
                    <span class="relative" x-data="{ open: false, search: @js($this->formLabel), color: @js($this->formLabelColor), colors: @js(WorkspaceLabel::colorOptions()), labels: @js($this->existingLabels()), filteredLabels() { const q = (this.search || '').toLowerCase().trim(); const list = q ? this.labels.filter(l => l.name.toLowerCase().includes(q)) : this.labels; return list.slice(0, 20); }, isNewLabel() { const q = (this.search || '').trim(); return q.length > 0 && !this.labels.some(l => l.name.toLowerCase() === q.toLowerCase()); } }" @click.outside="open = false">
                        <div class="relative">
                            <input type="text" x-model="search" wire:model.live="formLabel" placeholder="Label"
                                @focus="open = true"
                                @input="open = true"
                                class="h-7 w-32 rounded-md border border-slate-200 bg-white px-2 pr-6 text-xs font-medium text-on-surface placeholder:text-slate-400 outline-none transition hover:border-primary/40 focus:border-primary" />
                            <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[14px] text-slate-400">expand_more</span>
                        </div>

                        <div x-cloak x-show="open && (filteredLabels().length > 0 || isNewLabel())" x-transition
                            class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                            <div x-show="filteredLabels().length > 0" class="max-h-44 overflow-y-auto p-1">
                                <template x-for="label in filteredLabels()" :key="label.name">
                                    <button type="button"
                                        @click="$wire.set('formLabel', label.name); $wire.set('formLabelColor', label.color); open = false; search = label.name; color = label.color"
                                        class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                        <span class="h-2 w-2 shrink-0 rounded-full" :style="`background-color: ${label.hex}`"></span>
                                        <span class="flex-1 truncate" x-text="label.name"></span>
                                        <span x-show="label.name.trim().toLowerCase() === (search || '').trim().toLowerCase()" class="material-symbols-outlined text-[14px] text-primary">check</span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="isNewLabel()" class="border-t border-slate-100 p-2">
                                <div class="mb-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-400">New label color</div>
                                <div class="flex w-full flex-wrap gap-1.5">
                                    <template x-for="c in colors" :key="c.name">
                                        <button type="button" @click="$wire.set('formLabelColor', c.name); color = c.name; open = false"
                                            class="flex h-5 w-5 items-center justify-center rounded-full transition hover:scale-110"
                                            :style="`background-color: ${c.hex}`">
                                            <span x-show="color === c.name" class="material-symbols-outlined text-[12px] text-white">check</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        @error('formLabel')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>

                    {{-- Team lead (searchable) --}}
                    <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="flex h-7 max-w-35 items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                            <span class="truncate">
                                {{ $this->selectedManagerName() ?: 'Team Lead' }}
                            </span>
                            <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                        </button>

                        <div x-cloak x-show="open" x-transition
                            class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                            <div class="border-b border-slate-100 p-2">
                                <input x-model="search" type="text" placeholder="Search team lead..."
                                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                            </div>

                            <div class="max-h-44 overflow-y-auto p-1">
                                <button type="button" wire:click="$set('formProjectManagerId', null)"
                                    x-show="!search"
                                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-[15px] text-slate-400">person_off</span>
                                    None
                                </button>

                                @foreach ($this->staff() as $user)
                                <button type="button"
                                    wire:click="$set('formProjectManagerId', {{ $user->id }}), open = false"
                                    wire:key="manager-{{ $user->id }}"
                                    x-show="!search || '{{ strtolower($user->name) }}'.includes(search.toLowerCase())"
                                    @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> (int) $this->formProjectManagerId === (int) $user->id,
                                    ])>
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="flex-1 truncate text-on-surface">{{ $user->name }}</span>
                                    @if ((int) $this->formProjectManagerId === (int) $user->id)
                                    <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                    @endif
                                </button>
                                @endforeach
                            </div>
                        </div>

                        @error('formProjectManagerId')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>

                    {{-- Client (searchable) --}}
                    <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="flex h-7 max-w-35 items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                            <span class="truncate">
                                {{ $this->selectedClientName() ?: 'Client' }}
                            </span>
                            <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                        </button>

                        <div x-cloak x-show="open" x-transition
                            class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                            <div class="border-b border-slate-100 p-2">
                                <input x-model="search" type="text" placeholder="Search client..."
                                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                            </div>

                            <div class="max-h-44 overflow-y-auto p-1">
                                <button type="button" wire:click="$set('formClientId', null)"
                                    x-show="!search"
                                    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs text-on-surface transition hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-[15px] text-slate-400">person_off</span>
                                    None
                                </button>

                                @foreach ($this->clients() as $client)
                                <button type="button"
                                    wire:click="$set('formClientId', {{ $client->id }}), open = false"
                                    wire:key="client-{{ $client->id }}"
                                    x-show="!search || '{{ strtolower($client->name) }}'.includes(search.toLowerCase())"
                                    @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> (int) $this->formClientId === (int) $client->id,
                                    ])>
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                        {{ strtoupper(substr($client->name, 0, 1)) }}
                                    </span>
                                    <span class="flex-1 truncate text-on-surface">{{ $client->name }}</span>
                                    @if ((int) $this->formClientId === (int) $client->id)
                                    <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                    @endif
                                </button>
                                @endforeach
                            </div>
                        </div>

                        @error('formClientId')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>

                    {{-- Members (searchable multi-select) --}}
                    <span class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="flex h-7 max-w-35 items-center gap-1 rounded-md border border-slate-200 bg-white pl-1.5 pr-5 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary">
                            <span class="truncate">
                                {{ $this->formMemberIds ? count($this->formMemberIds).' member'.(count($this->formMemberIds) > 1 ? 's' : '') : 'Members' }}
                            </span>
                            <span class="material-symbols-outlined pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] text-slate-400" style="font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 16">expand_more</span>
                        </button>

                        <div x-cloak x-show="open" x-transition
                            class="absolute left-0 z-30 mt-1 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                            <div class="border-b border-slate-100 p-2">
                                <input x-model="search" type="text" placeholder="Search members..."
                                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:border-primary" />
                            </div>

                            <div class="max-h-44 overflow-y-auto p-1">
                                @foreach ($this->staff() as $user)
                                <button
                                    type="button"
                                    wire:key="member-{{ $user->id }}"
                                    wire:click="toggleMember({{ $user->id }})"
                                    x-show="!search || '{{ strtolower($user->name) }}'.includes(search.toLowerCase())"
                                    @class([ 'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-slate-50' , 'bg-primary/5'=> in_array((int) $user->id, $this->formMemberIds),
                                    ])>
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[9px] font-bold text-primary">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="flex-1 truncate text-on-surface">{{ $user->name }}</span>
                                    @if (in_array((int) $user->id, $this->formMemberIds))
                                    <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                    @endif
                                </button>
                                @endforeach
                            </div>
                        </div>

                        @error('formMemberIds')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>

                    {{-- Start date --}}
                    <span class="relative flex items-center gap-1">
                        <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Start</span>
                        <input type="date" wire:model.live="formStartDate"
                            class="h-7 rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary" />

                        @error('formStartDate')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>

                    {{-- Target date --}}
                    <span class="relative flex items-center gap-1">
                        <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">End</span>
                        <input type="date" wire:model.live="formTargetDate"
                            class="h-7 rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-on-surface outline-none transition hover:border-primary/40 focus:border-primary" />

                        @error('formTargetDate')
                        <p class="absolute left-0 top-full mt-0.5 text-[10px] text-red-500">{{ $message }}</p>
                        @enderror
                    </span>
                </div>

                {{-- Body: Linear-style project details editor --}}
                <div class="px-8 pb-6 mt-3">
                    @include('pages.admin.workspace.partials._editor-field', ['editorContent' => $formDetails, 'editorSyncProperty' => 'formDetails', 'editorErrorProperty' => 'formDetails'])
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/60 px-8 py-4">
                    <button type="button" wire:click="$set('showCreateModal', false)"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-on-surface transition-colors hover:bg-slate-100 cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                        <span wire:loading.remove wire:target="createProject">Create project</span>
                        <span wire:loading wire:target="createProject" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @include('pages.admin.workspace.partials._editor-script')
</div>