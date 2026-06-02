<?php

use App\Models\UserBgRemovedImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My BG Removed Images')] class extends Component {
    use WithPagination;

    public array $selectedIds = [];

    public bool $selectAllVisible = false;

    public int $perPage = 15;

    public function updatedSelectAllVisible(bool $value): void
    {
        if (! $value) {
            $this->selectedIds = [];

            return;
        }

        $this->selectedIds = $this->images()
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function updatedSelectedIds(): void
    {
        $visibleIds = $this->images()
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $this->selectAllVisible = count($visibleIds) > 0
            && empty(array_diff($visibleIds, $this->selectedIds));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAllVisible = false;
    }

    public function delete(int $id): void
    {
        $image = UserBgRemovedImage::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $image->deleteFile();
        $image->delete();

        $this->selectedIds = array_values(array_filter(
            $this->selectedIds,
            fn ($selectedId) => (int) $selectedId !== $id
        ));

        $this->selectAllVisible = false;

        $this->dispatch(
            'toast',
            message: 'Image deleted from backup.',
            type: 'success'
        );
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch(
                'toast',
                message: 'Please select at least one image.',
                type: 'error'
            );

            return;
        }

        $count = 0;

        UserBgRemovedImage::query()
            ->select(['id', 'user_id', 'result_path'])
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->selectedIds)
            ->each(function ($image) use (&$count) {
                $image->deleteFile();
                $image->delete();

                $count++;
            });

        $this->clearSelection();

        $this->dispatch(
            'toast',
            message: "{$count} image(s) deleted from backup.",
            type: 'success'
        );
    }

    public function download(int $id): mixed
    {
        $image = UserBgRemovedImage::query()
            ->select([
                'id',
                'user_id',
                'original_name',
                'result_path',
                'result_ext',
                'expires_at',
            ])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        if (! $image->fileExists()) {
            $this->dispatch(
                'toast',
                message: 'File no longer available. It may have expired.',
                type: 'error'
            );

            return null;
        }

        return Storage::disk('public')->download(
            $image->result_path,
            $image->downloadName()
        );
    }

    public function downloadSelected(): mixed
    {
        if (empty($this->selectedIds)) {
            $this->dispatch(
                'toast',
                message: 'Please select at least one image.',
                type: 'error'
            );

            return null;
        }

        $images = UserBgRemovedImage::query()
            ->select([
                'id',
                'user_id',
                'original_name',
                'result_path',
                'result_ext',
                'expires_at',
            ])
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->selectedIds)
            ->get();

        if ($images->isEmpty()) {
            $this->dispatch(
                'toast',
                message: 'No files available to download.',
                type: 'error'
            );

            return null;
        }

        $zip = new \ZipArchive;
        $zipPath = tempnam(sys_get_temp_dir(), 'bg_removed_') . '.zip';

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            $this->dispatch(
                'toast',
                message: 'Failed to create ZIP archive.',
                type: 'error'
            );

            return null;
        }

        $addedFiles = 0;

        foreach ($images as $image) {
            if ($image->fileExists()) {
                $zip->addFromString(
                    $image->downloadName(),
                    Storage::disk('public')->get($image->result_path)
                );

                $addedFiles++;
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            @unlink($zipPath);

            $this->dispatch(
                'toast',
                message: 'Selected files are no longer available.',
                type: 'error'
            );

            return null;
        }

        $this->clearSelection();

        return response()
            ->download($zipPath, 'bg-removed-images.zip')
            ->deleteFileAfterSend(true);
    }

    public function images()
    {
        UserBgRemovedImage::query()
            ->select(['id', 'user_id', 'result_path', 'expires_at'])
            ->where('user_id', auth()->id())
            ->where('expires_at', '<=', now())
            ->each(function ($image) {
                $image->deleteFile();
                $image->delete();
            });

        return UserBgRemovedImage::query()
            ->select([
                'id',
                'user_id',
                'original_name',
                'result_path',
                'original_size',
                'result_size',
                'result_ext',
                'expires_at',
            ])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate($this->perPage);
    }
};
?>

<div x-data="{ sidebarOpen: false, preview: null }" class="relative min-h-screen text-white">

    <div class="mx-auto max-w-350 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-[34px] border border-white/10 bg-white/6 shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
            <div class="flex min-h-[calc(100vh-3rem)]">

                {{-- Mobile Overlay --}}
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                    @click="sidebarOpen = false"
                    style="display:none;">
                </div>

                {{-- Sidebar --}}
                <livewire:shared.user-sidebar />

                {{-- Main --}}
                <div class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">

                    {{-- Top Header --}}
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="sidebarOpen = true"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/8 text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] backdrop-blur-xl transition hover:bg-white/12 lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-blue-100/45">
                                    Tools Backup
                                </p>

                                <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">
                                    BG Removed Images
                                </h1>

                                <p class="mt-1 text-sm text-blue-100/45">
                                    Manage your backed-up background removed images and download them before expiry.
                                </p>
                            </div>
                        </div>

                        <a
                            href="{{ route('client.tools.bg-remover') }}"
                            wire:navigate
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5">
                            <span class="material-symbols-outlined text-base">magic_exchange</span>
                            Remove Background
                        </a>
                    </div>

                    {{-- Bulk Action Bar --}}
                    @if (count($selectedIds) > 0)
                        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2 text-sm font-semibold text-red-100">
                                <span class="material-symbols-outlined text-[20px]">
                                    check_circle
                                </span>

                                {{ count($selectedIds) }} image{{ count($selectedIds) > 1 ? 's' : '' }} selected
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="clearSelection"
                                    class="rounded-xl border border-white/10 bg-white/8 px-4 py-2.75 text-xs font-semibold text-white/70 transition hover:bg-white/12 cursor-pointer">
                                    Clear
                                </button>

                                <button
                                    type="button"
                                    wire:click="downloadSelected"
                                    class="inline-flex items-center gap-2 rounded-xl bg-cyan-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-cyan-600 cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]">
                                        folder_zip
                                    </span>
                                    Download ZIP
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteSelected"
                                    wire:confirm="Delete all selected backups? This cannot be undone."
                                    class="inline-flex items-center gap-2 rounded-xl bg-red-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-600 cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]">
                                        delete
                                    </span>
                                    Delete Selected
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Table --}}
                    <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.06] shadow-[0_16px_60px_rgba(0,0,0,0.18)] backdrop-blur-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse text-left">
                                <thead>
                                    <tr class="border-b border-white/10 bg-white/[0.04]">
                                        <th class="w-14 px-5 py-4">
                                            <label class="relative flex h-5 w-5 cursor-pointer items-center justify-center">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="selectAllVisible"
                                                    class="peer sr-only"
                                                />

                                                <span class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/25 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>

                                                <svg class="pointer-events-none absolute hidden h-3.5 w-3.5 text-cyan-200 peer-checked:block"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </label>
                                        </th>

                                        <th class="px-5 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-blue-100/45">
                                            Image
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
                                    @forelse ($this->images() as $image)
                                        <tr
                                            wire:key="user-bg-removed-image-{{ $image->id }}"
                                            class="transition hover:bg-white/[0.04]"
                                        >
                                            <td class="px-5 py-4">
                                                <label class="relative flex h-5 w-5 cursor-pointer items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        wire:model.live="selectedIds"
                                                        value="{{ $image->id }}"
                                                        class="peer sr-only"
                                                    />

                                                    <span class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/25 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>

                                                    <svg class="pointer-events-none absolute hidden h-3.5 w-3.5 text-cyan-200 peer-checked:block"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                </label>
                                            </td>

                                            <td class="px-5 py-4">
                                                @if ($image->fileExists() && $image->previewUrl())
                                                    <button
                                                        type="button"
                                                        @click="preview = '{{ $image->previewUrl() }}'"
                                                        class="group relative flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-cyan-400/10 transition hover:scale-105"
                                                    >
                                                        <img
                                                            src="{{ $image->previewUrl() }}"
                                                            alt="{{ $image->original_name }}"
                                                            class="h-full w-full object-contain"
                                                            loading="lazy"
                                                        />

                                                        <span class="absolute inset-0 flex items-center justify-center bg-black/35 text-white opacity-0 transition group-hover:opacity-100">
                                                            <span class="material-symbols-outlined text-[18px]">
                                                                zoom_in
                                                            </span>
                                                        </span>
                                                    </button>
                                                @else
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300">
                                                        <span class="material-symbols-outlined text-[24px]">
                                                            image
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4">
                                                <div class="min-w-0 max-w-72">
                                                    <p
                                                        class="truncate text-sm font-semibold text-white"
                                                        title="{{ $image->original_name }}"
                                                    >
                                                        {{ $image->original_name }}
                                                    </p>

                                                    <span class="mt-1 inline-flex rounded-md bg-white/8 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-cyan-200">
                                                        {{ $image->result_ext }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-5 py-4">
                                                <p class="font-mono text-sm text-white">
                                                    {{ number_format($image->original_size / 1024, 1) }} KB
                                                </p>

                                                <p class="mt-0.5 font-mono text-xs text-blue-100/45">
                                                    → {{ number_format($image->result_size / 1024, 1) }} KB
                                                </p>
                                            </td>

                                            <td class="px-5 py-4">
                                                @if ($image->isExpired())
                                                    <span class="inline-flex items-center gap-2 rounded-full bg-red-500/10 px-3 py-1 text-xs font-semibold text-red-300">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                                                        Expired
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                                        {{ $image->expires_at?->format('M d, Y') }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    @if (! $image->isExpired() && $image->fileExists())
                                                        <button
                                                            type="button"
                                                            wire:click="download({{ $image->id }})"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-cyan-500/20 hover:text-cyan-200"
                                                            title="Download"
                                                        >
                                                            <span class="material-symbols-outlined text-[18px]">
                                                                download
                                                            </span>
                                                        </button>
                                                    @endif

                                                    <button
                                                        type="button"
                                                        wire:click="delete({{ $image->id }})"
                                                        wire:confirm="Delete this backup? This cannot be undone."
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-300 transition hover:bg-red-500/20"
                                                        title="Delete"
                                                    >
                                                        <span class="material-symbols-outlined text-[18px]">
                                                            delete
                                                        </span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-16 text-center">
                                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/8">
                                                        <span class="material-symbols-outlined text-3xl text-blue-100/40">
                                                            backup
                                                        </span>
                                                    </div>

                                                    <h3 class="text-lg font-semibold text-white">
                                                        No backed-up images yet
                                                    </h3>

                                                    <p class="mt-2 text-sm text-blue-100/50">
                                                        Remove backgrounds as a premium user to get backup access.
                                                    </p>

                                                    <a
                                                        href="{{ route('client.tools.bg-remover') }}"
                                                        wire:navigate
                                                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-3 font-semibold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5"
                                                    >
                                                        <span class="material-symbols-outlined text-base">
                                                            magic_exchange
                                                        </span>
                                                        Remove Background
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col gap-4 border-t border-white/10 bg-white/[0.03] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-blue-100/45">
                                    Per page
                                </span>

                                <select
                                    wire:model.live="perPage"
                                    class="rounded-xl border border-white/10 bg-white/8 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20"
                                >
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
                                {{ $this->images()->links() }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div
            x-show="preview"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-999 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
            @click="preview = null"
            @keydown.escape.window="preview = null"
            style="display:none;"
        >
            <button
                type="button"
                @click="preview = null"
                class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
            >
                <span class="material-symbols-outlined">
                    close
                </span>
            </button>

            <img
                :src="preview"
                @click.stop
                class="max-h-[90vh] max-w-[90vw] rounded-2xl object-contain shadow-2xl"
            />
        </div>
    </template>
</div>