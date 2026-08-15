<?php

use App\Models\CompressedPdf;
use App\Models\MergedPdf;
use App\Models\SplitPdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My PDF Files')] class extends Component {
    use WithPagination;

    public string $filter = 'all';

    public array $selectedIds = [];

    public bool $selectAllVisible = false;

    public int $perPage = 15;

    public function updatedFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAllVisible(bool $value): void
    {
        if (!$value) {
            $this->selectedIds = [];

            return;
        }

        $this->selectedIds = $this->records()->getCollection()->pluck('key')->map(fn ($key) => (string) $key)->toArray();
    }

    public function updatedSelectedIds(): void
    {
        $visibleKeys = $this->records()->getCollection()->pluck('key')->map(fn ($key) => (string) $key)->toArray();

        $this->selectAllVisible = count($visibleKeys) > 0 && empty(array_diff($visibleKeys, $this->selectedIds));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAllVisible = false;
    }

    public function records(): LengthAwarePaginator
    {
        $this->cleanupExpired();

        $items = collect();

        if ($this->filter === 'all' || $this->filter === 'merge') {
            $items = $items->concat(MergedPdf::query()
                ->where('user_id', auth()->id())
                ->where('is_backup_enabled', true)
                ->where('status', 'completed')
                ->whereNotNull('backup_expires_at')
                ->where('backup_expires_at', '>', now())
                ->latest()
                ->get()
                ->map(fn (MergedPdf $record) => [
                    'key' => 'merge-' . $record->id,
                    'type' => 'merge',
                    'typeLabel' => 'Merge',
                    'name' => $record->output_name,
                    'size' => (int) $record->output_size,
                    'expires_at' => $record->backup_expires_at,
                    'record' => $record,
                ]));
        }

        if ($this->filter === 'all' || $this->filter === 'split') {
            $items = $items->concat(SplitPdf::query()
                ->where('user_id', auth()->id())
                ->where('is_backup_enabled', true)
                ->where('status', 'completed')
                ->whereNotNull('backup_expires_at')
                ->where('backup_expires_at', '>', now())
                ->latest()
                ->get()
                ->map(fn (SplitPdf $record) => [
                    'key' => 'split-' . $record->id,
                    'type' => 'split',
                    'typeLabel' => 'Split',
                    'name' => $record->outputDownloadName(),
                    'size' => (int) $record->output_size,
                    'expires_at' => $record->backup_expires_at,
                    'record' => $record,
                ]));
        }

        if ($this->filter === 'all' || $this->filter === 'compress') {
            $items = $items->concat(CompressedPdf::query()
                ->where('user_id', auth()->id())
                ->where('is_backup_enabled', true)
                ->where('status', 'completed')
                ->whereNotNull('backup_expires_at')
                ->where('backup_expires_at', '>', now())
                ->latest()
                ->get()
                ->map(fn (CompressedPdf $record) => [
                    'key' => 'compress-' . $record->id,
                    'type' => 'compress',
                    'typeLabel' => 'Compress',
                    'name' => $record->downloadName(),
                    'size' => (int) $record->compressed_size,
                    'expires_at' => $record->backup_expires_at,
                    'record' => $record,
                ]));
        }

        $items = $items->sortByDesc(fn (array $item) => $item['record']->created_at)->values();

        $page = (int) $this->getPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function cleanupExpired(): void
    {
        foreach ([MergedPdf::class, SplitPdf::class, CompressedPdf::class] as $model) {
            $model::query()
                ->where('user_id', auth()->id())
                ->where('is_backup_enabled', true)
                ->whereNotNull('backup_expires_at')
                ->where('backup_expires_at', '<=', now())
                ->each(function ($record) {
                    $this->deleteRecordFiles($record);

                    $record->delete();
                });
        }
    }

    private function deleteRecordFiles(MergedPdf|SplitPdf|CompressedPdf $record): void
    {
        if ($record instanceof MergedPdf) {
            $record->deleteOutputFile();
        } elseif ($record instanceof SplitPdf) {
            $record->deleteOriginalFile();
            $record->deleteOutputFile();
        } elseif ($record instanceof CompressedPdf) {
            $record->deleteAllFiles();
        }
    }

    public function download(string $key): mixed
    {
        [$type, $id] = explode('-', $key, 2);

        $disk = Storage::disk(config('pdf-compressor.storage_disk'));

        if ($type === 'merge') {
            $record = MergedPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id);

            if (!$record->outputFileExists()) {
                $this->dispatch('toast', message: 'File no longer available. It may have expired.', type: 'error');

                return null;
            }

            return $disk->download($record->output_path, $record->output_name);
        }

        if ($type === 'split') {
            $record = SplitPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id);

            if (!$record->outputFileExists()) {
                $this->dispatch('toast', message: 'File no longer available. It may have expired.', type: 'error');

                return null;
            }

            return $disk->download($record->output_path, $record->outputDownloadName());
        }

        $record = CompressedPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id);

        if (!$record->downloadableFileExists()) {
            $this->dispatch('toast', message: 'File no longer available. It may have expired.', type: 'error');

            return null;
        }

        return $disk->download($record->downloadablePath(), $record->downloadName());
    }

    public function delete(string $key): void
    {
        [$type, $id] = explode('-', $key, 2);

        $record = match ($type) {
            'merge' => MergedPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id),
            'split' => SplitPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id),
            default => CompressedPdf::query()->where('user_id', auth()->id())->findOrFail((int) $id),
        };

        $this->deleteRecordFiles($record);

        $record->delete();

        $this->selectedIds = array_values(array_filter($this->selectedIds, fn (string $selectedId) => $selectedId !== $key));

        $this->selectAllVisible = false;

        $this->dispatch('toast', message: 'File deleted from backup.', type: 'success');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('toast', message: 'Please select at least one file.', type: 'error');

            return;
        }

        $count = 0;

        foreach ($this->selectedIds as $key) {
            [$type, $id] = explode('-', $key, 2);

            $record = match ($type) {
                'merge' => MergedPdf::query()->where('user_id', auth()->id())->find((int) $id),
                'split' => SplitPdf::query()->where('user_id', auth()->id())->find((int) $id),
                default => CompressedPdf::query()->where('user_id', auth()->id())->find((int) $id),
            };

            if (!$record) {
                continue;
            }

            $this->deleteRecordFiles($record);

            $record->delete();

            $count++;
        }

        $this->clearSelection();

        $this->dispatch('toast', message: "{$count} file(s) deleted from backup.", type: 'success');
    }

    public function formatBytes(?int $bytes): string
    {
        $bytes = $bytes ?? 0;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
};
?>

<div x-data="{ sidebarOpen: false }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
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

                    {{-- Top Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Tools Backup
                                </p>

                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                                    PDF Files
                                </h1>

                                <p class="mt-1 text-sm text-blue-100/45">
                                    Manage your backed-up merged, split and compressed PDFs and download them anytime before expiry.
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('client.tools.index') }}" wire:navigate
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5">
                            <span class="material-symbols-outlined text-base">description</span>
                            Open PDF Tools
                        </a>
                    </div>

                    {{-- Filter Pills --}}
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        @foreach ([
                        'all' => 'All',
                        'merge' => 'Merged',
                        'split' => 'Split',
                        'compress' => 'Compressed',
                        ] as $value => $label)
                        <button type="button" wire:click="$set('filter', '{{ $value }}')"
                            class="rounded-full border px-4 py-2 text-xs font-semibold transition cursor-pointer {{ $filter === $value ? 'border-cyan-400/40 bg-cyan-400/10 text-cyan-200' : 'border-white/10 bg-white/5 text-blue-100/60 hover:border-cyan-400/20 hover:text-white' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

                    {{-- Bulk Action Bar --}}
                    @if (count($selectedIds) > 0)
                    <div
                        class="mb-4 flex flex-col gap-3 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2 text-sm font-semibold text-red-100">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            {{ count($selectedIds) }} file{{ count($selectedIds) > 1 ? 's' : '' }} selected
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="clearSelection"
                                class="rounded-xl border border-white/10 bg-white/8 px-4 py-2.75 text-xs font-semibold text-white/70 transition hover:bg-white/12 cursor-pointer">
                                Clear
                            </button>

                            <button type="button" wire:click="deleteSelected"
                                wire:confirm="Delete all selected backups? This cannot be undone."
                                class="inline-flex items-center gap-2 rounded-xl bg-red-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-600 cursor-pointer">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                Delete Selected
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Table --}}
                    <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/6 shadow-[0_16px_60px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-245 border-collapse text-left">
                                <thead>
                                    <tr class="border-b border-white/10 bg-white/4">
                                        <th class="w-14 px-5 py-4">
                                            <label class="relative flex h-5 w-5 cursor-pointer items-center justify-center">
                                                <input type="checkbox" wire:model.live="selectAllVisible"
                                                    class="peer sr-only" />

                                                <span
                                                    class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/25 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>

                                                <svg class="pointer-events-none absolute hidden h-3.5 w-3.5 text-cyan-200 peer-checked:block"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </label>
                                        </th>

                                        <th class="px-5 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            Type
                                        </th>

                                        <th class="px-5 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            File Name
                                        </th>

                                        <th class="px-5 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            Size
                                        </th>

                                        <th class="px-5 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            Expires
                                        </th>

                                        <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-white/10">
                                    @forelse ($this->records() as $item)
                                    <tr wire:key="pdf-backup-{{ $item['key'] }}" class="transition hover:bg-white/4">
                                        <td class="px-5 py-4">
                                            <label class="relative flex h-5 w-5 cursor-pointer items-center justify-center">
                                                <input type="checkbox" wire:model.live="selectedIds"
                                                    value="{{ $item['key'] }}" class="peer sr-only" />

                                                <span
                                                    class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/25 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>

                                                <svg class="pointer-events-none absolute hidden h-3.5 w-3.5 text-cyan-200 peer-checked:block"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </label>
                                        </td>

                                        <td class="px-5 py-4">
                                            @php
                                            $typeStyles = [
                                            'merge' => 'bg-emerald-500/10 text-emerald-300 border-emerald-400/20',
                                            'split' => 'bg-cyan-500/10 text-cyan-300 border-cyan-400/20',
                                            'compress' => 'bg-violet-500/10 text-violet-300 border-violet-400/20',
                                            ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold {{ $typeStyles[$item['type']] ?? $typeStyles['merge'] }}">
                                                <span class="material-symbols-outlined text-[14px]">
                                                    {{ $item['type'] === 'merge' ? 'merge' : ($item['type'] === 'split' ? 'call_split' : 'compress') }}
                                                </span>
                                                {{ $item['typeLabel'] }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="min-w-0 max-w-72">
                                                <p class="truncate text-sm font-semibold text-white" title="{{ $item['name'] }}">
                                                    {{ $item['name'] }}
                                                </p>

                                                <span
                                                    class="mt-1 inline-flex items-center gap-1 rounded-md bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-cyan-200">
                                                    <span class="material-symbols-outlined text-[12px]">picture_as_pdf</span>
                                                    PDF
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p class="font-mono text-sm text-white">
                                                {{ $this->formatBytes($item['size']) }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                                {{ $item['expires_at']?->format('M d, Y') }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" wire:click="download('{{ $item['key'] }}')"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-cyan-500/20 hover:text-cyan-200"
                                                    title="Download">
                                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                                </button>

                                                <button type="button" wire:click="delete('{{ $item['key'] }}')"
                                                    wire:confirm="Delete this backup? This cannot be undone."
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-300 transition hover:bg-red-500/20"
                                                    title="Delete">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-16 text-center">
                                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/8">
                                                    <span class="material-symbols-outlined text-3xl text-blue-100/40">backup</span>
                                                </div>

                                                <h3 class="text-lg font-semibold text-white">
                                                    No backed-up PDF files yet
                                                </h3>

                                                <p class="mt-2 text-sm text-blue-100/50">
                                                    Merge, split or compress PDFs as a premium user to get backup access.
                                                </p>

                                                <a href="{{ route('client.tools.index') }}" wire:navigate
                                                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3 font-semibold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5">
                                                    <span class="material-symbols-outlined text-base">description</span>
                                                    Open PDF Tools
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="flex flex-col gap-4 border-t border-white/10 bg-white/3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-blue-100/45">Per page</span>

                                <select wire:model.live="perPage"
                                    class="rounded-xl border border-white/10 bg-white/8 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20">
                                    <option class="text-slate-900" value="10">10</option>
                                    <option class="text-slate-900" value="15">15</option>
                                    <option class="text-slate-900" value="25">25</option>
                                    <option class="text-slate-900" value="50">50</option>
                                </select>

                                @if (count($selectedIds) > 0)
                                <span class="rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-200">
                                    {{ count($selectedIds) }} selected
                                </span>
                                @endif
                            </div>

                            <div>
                                {{ $this->records()->links() }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>