<?php

use App\Models\WorkspaceProject;
use App\Models\WorkspaceProjectUpdate;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project Activity')] class extends Component {
    public WorkspaceProject $project;

    public function mount(WorkspaceProject $project): void
    {
        $user = auth()->user();

        if ($user?->role !== 'admin' && (int) $project->client_id !== (int) $user?->id) {
            abort(403);
        }

        $this->project = WorkspaceProject::query()
            ->with(['creator', 'members', 'updates.author'])
            ->withCount(['tasks', 'updates'])
            ->where('is_active', true)
            ->findOrFail($project->getKey());
    }

    public function canWriteUpdate(): bool
    {
        return false;
    }

    public function healthBadge(string $health): string
    {
        return match ($health) {
            'at_risk' => 'text-amber-700 ring-amber-200',
            'off_track' => 'text-red-700 ring-red-200',
            default => 'text-emerald-700 ring-emerald-200',
        };
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
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                <div x-show="sidebarOpen" x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                <livewire:shared.user-sidebar />

                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <a
                                href="{{ route('account.workspace-projects') }}"
                                wire:navigate
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12">
                                <span class="material-symbols-outlined text-xl">arrow_back</span>
                            </a>

                            <div>
                                <h1 class="text-2xl font-bold text-white sm:text-3xl">
                                    Project Activity
                                </h1>

                                <p class="mt-1 text-sm text-blue-100/50">
                                    {{ $project->name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs font-medium text-blue-100/70">
                                {{ $project->status->label() }}
                            </span>

                            <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs font-medium text-blue-100/70">
                                {{ $project->updates_count }} activity {{ Str::plural('entry', $project->updates_count) }}
                            </span>
                        </div>
                    </div>

                    {{-- Activity timeline --}}
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_16px_50px_rgba(0,0,0,0.18)] backdrop-blur-2xl">

                        @forelse ($this->activityThreads() as $node)

                        <div class="relative space-y-5 before:absolute before:bottom-3 before:left-[13px] before:top-3 before:w-px before:bg-white/10">
                            <div class="relative flex gap-3">
                                <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10">
                                    <span class="material-symbols-outlined text-[14px] text-cyan-200">
                                        {{ ($node['update']->type ?? 'update') === 'comment' ? 'comment' : 'show_chart' }}
                                    </span>
                                </div>

                                <div class="min-w-0 w-full pt-0.5">
                                    <p class="text-xs leading-5 text-blue-100/70">
                                        <span class="font-medium text-white">
                                            {{ $node['update']->author?->name ?: 'Former team member' }}
                                        </span>

                                        @if (($node['update']->type ?? 'update') === 'update')
                                        posted a project update
                                        <span class="ml-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-white/80 ring-1 ring-inset {{ $this->healthBadge($node['update']->health ?? 'on_track') }}">
                                            {{ $node['update']->health === 'off_track' ? 'Off track' : ($node['update']->health === 'at_risk' ? 'At risk' : 'On track') }}
                                        </span>
                                        @else
                                        commented on the project
                                        @endif
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-blue-50/80">
                                        {{ $node['update']->body }}
                                    </p>

                                    @if ($node['update']->attachments)
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($node['update']->attachments as $attachment)
                                        <a
                                            href="{{ $attachment['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-white/10 bg-white/6 px-2 py-1 text-[11px] text-blue-100/70 transition hover:bg-white/10">
                                            <span class="material-symbols-outlined text-[13px] text-cyan-200/70">
                                                {{ str_starts_with($attachment['mime'] ?? '', 'image/') ? 'image' : 'insert_drive_file' }}
                                            </span>
                                            <span class="truncate">{{ $attachment['name'] }}</span>
                                        </a>
                                        @endforeach
                                    </div>
                                    @endif

                                    <p class="mt-1 text-[11px] text-blue-100/40">
                                        {{ $node['update']->created_at?->format('M d, Y g:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @empty

                        <div class="py-10 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-cyan-200">
                                <span class="material-symbols-outlined text-3xl">updates</span>
                            </div>

                            <p class="mt-4 font-semibold text-white">
                                No activity yet
                            </p>

                            <p class="mx-auto mt-1 max-w-md text-sm text-blue-100/50">
                                Project updates and comments from the team will appear here.
                            </p>
                        </div>

                        @endforelse

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>