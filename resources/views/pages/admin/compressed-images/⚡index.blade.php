<?php

use App\Models\UserCompressedImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('Compressed Images')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $expiredFilter = 'all';

    public int $perPage = 10;

    public array $selectedImages = [];

    public bool $selectAllVisible = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedExpiredFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAllVisible(bool $value): void
    {
        if (! $value) {
            $this->selectedImages = [];

            return;
        }

        $this->selectedImages = $this->images()
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function updatedSelectedImages(): void
    {
        $visibleIds = $this->images()
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $this->selectAllVisible = count($visibleIds) > 0
            && empty(array_diff($visibleIds, $this->selectedImages));
    }

    public function clearSelection(): void
    {
        $this->selectedImages = [];
        $this->selectAllVisible = false;
    }

    public function delete(int $id): void
    {
        $image = UserCompressedImage::findOrFail($id);

        $image->deleteFile();
        $image->delete();

        $this->selectedImages = array_values(array_filter(
            $this->selectedImages,
            fn ($selectedId) => (int) $selectedId !== $id
        ));

        $this->selectAllVisible = false;

        $this->dispatch(
            'toast',
            message: 'Compressed image deleted successfully.',
            type: 'success'
        );
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedImages)) {
            $this->dispatch(
                'toast',
                message: 'Please select at least one image.',
                type: 'error'
            );

            return;
        }

        $images = UserCompressedImage::query()
            ->whereIn('id', $this->selectedImages)
            ->get();

        foreach ($images as $image) {
            $image->deleteFile();
            $image->delete();
        }

        $deletedCount = $images->count();

        $this->clearSelection();

        $this->dispatch(
            'toast',
            message: $deletedCount . ' compressed image' . ($deletedCount > 1 ? 's' : '') . ' deleted successfully.',
            type: 'success'
        );
    }

    public function download(int $id): mixed
    {
        $image = UserCompressedImage::findOrFail($id);

        if (! $image->fileExists()) {
            $this->dispatch(
                'toast',
                message: 'File no longer exists on disk.',
                type: 'error'
            );

            return null;
        }

        return Storage::disk('public')->download(
            $image->compressed_path,
            $image->downloadName()
        );
    }

    public function images()
    {
        $search = trim($this->search);

        return UserCompressedImage::query()
            ->with(['user', 'toolCategory'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                        ->orWhere('original_name', 'like', '%' . $search . '%');
                });
            })
            ->when($this->expiredFilter === 'active', function ($query) {
                $query->where('expires_at', '>', now());
            })
            ->when($this->expiredFilter === 'expired', function ($query) {
                $query->where('expires_at', '<=', now());
            })
            ->latest()
            ->paginate($this->perPage);
    }
};
?>

<div>
    <div class="mx-auto w-full space-y-stack-lg">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Compressed Images
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    Manage backed-up compressed images, downloads, expiry status, and storage cleanup.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        search
                    </span>

                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search image..."
                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-72"
                    />
                </div>

                <div class="relative">
                    <select
                        wire:model.live="expiredFilter"
                        class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-44"
                    >
                        <option value="all">All Backups</option>
                        <option value="active">Active Only</option>
                        <option value="expired">Expired Only</option>
                    </select>

                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        @if (count($selectedImages) > 0)
            <div class="flex flex-col gap-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-red-700">
                    <span class="material-symbols-outlined text-[20px]">
                        check_circle
                    </span>

                    {{ count($selectedImages) }} image{{ count($selectedImages) > 1 ? 's' : '' }} selected
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        wire:click="bulkDelete"
                        wire:confirm="Delete selected compressed images permanently?"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 active:scale-[0.98]"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            delete
                        </span>
                        Delete Selected
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="w-12 px-6 py-4">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAllVisible"
                                    class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/20"
                                />
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Image
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                User
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                File
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Category
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Size
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Expires
                            </th>

                            <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->images() as $image)
                            <tr
                                wire:key="compressed-image-{{ $image->id }}"
                                class="transition-colors hover:bg-slate-50/80"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        value="{{ $image->id }}"
                                        wire:model.live="selectedImages"
                                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/20"
                                    />
                                </td>

                                <td class="px-6 py-4">
                                    @if ($image->fileExists() && $image->previewUrl())
                                        <div x-data="{ preview: null }">
                                            <button
                                                type="button"
                                                @click="preview = '{{ $image->previewUrl() }}'"
                                                class="group relative flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-100 transition hover:scale-105"
                                            >
                                                <img
                                                    src="{{ $image->previewUrl() }}"
                                                    alt="{{ $image->original_name }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                />

                                                <span class="absolute inset-0 flex items-center justify-center bg-black/35 text-white opacity-0 transition group-hover:opacity-100">
                                                    <span class="material-symbols-outlined text-[20px]">
                                                        zoom_in
                                                    </span>
                                                </span>
                                            </button>

                                            <template x-teleport="body">
                                                <div
                                                    x-cloak
                                                    x-show="preview"
                                                    x-transition.opacity.duration.200ms
                                                    class="fixed inset-0 z-9999 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
                                                    @click="preview = null"
                                                    @keydown.escape.window="preview = null"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="preview = null"
                                                        class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                                                    >
                                                        <span class="material-symbols-outlined">close</span>
                                                    </button>

                                                    <img
                                                        :src="preview"
                                                        @click.stop
                                                        class="max-h-[90vh] max-w-[90vw] rounded-2xl object-contain shadow-2xl"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                            <span class="material-symbols-outlined text-[24px]">
                                                image
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="min-w-0">
                                        <span class="block text-label-md font-label-md text-on-surface">
                                            {{ $image->user?->name ?? 'Deleted User' }}
                                        </span>

                                        <span class="block max-w-56 truncate text-body-sm font-body-sm text-secondary">
                                            {{ $image->user?->email ?: 'No email available' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="min-w-0 max-w-64">
                                        <span
                                            class="block truncate text-label-md font-label-md text-on-surface"
                                            title="{{ $image->original_name }}"
                                        >
                                            {{ $image->original_name }}
                                        </span>

                                        <span class="mt-1 inline-flex rounded-md bg-primary/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-primary">
                                            {{ $image->compressed_ext }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                                    {{ $image->toolCategory?->name ?? '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block font-mono text-body-sm text-on-surface">
                                        {{ number_format($image->original_size / 1024, 1) }} KB
                                    </span>

                                    <span class="mt-0.5 block font-mono text-xs text-secondary">
                                        → {{ number_format($image->compressed_size / 1024, 1) }} KB

                                        @if ($image->original_size > 0)
                                            <span class="font-semibold text-green-600">
                                                {{ (int) round((1 - $image->compressed_size / $image->original_size) * 100) }}%
                                            </span>
                                        @endif
                                    </span>
                                </td>

                                <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                                    {{ $image->expires_at?->format('M d, Y') }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($image->isExpired())
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                            <span class="text-body-md font-body-md text-on-surface">
                                                Expired
                                            </span>
                                        </div>
                                    @elseif ($image->fileExists())
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                                            <span class="text-body-md font-body-md text-on-surface">
                                                Active
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                            <span class="text-body-md font-body-md text-on-surface">
                                                Missing
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
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
                                            @if (! $image->isExpired() && $image->fileExists())
                                                <button
                                                    type="button"
                                                    wire:click="download({{ $image->id }})"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">
                                                        download
                                                    </span>
                                                    Download
                                                </button>
                                            @endif

                                            @if ($image->fileExists() && $image->previewUrl())
                                                <button
                                                    type="button"
                                                    @click="open = false; window.open('{{ $image->previewUrl() }}', '_blank')"
                                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">
                                                        open_in_new
                                                    </span>
                                                    Open Preview
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="delete({{ $image->id }})"
                                                wire:confirm="Delete this backup permanently?"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    delete
                                                </span>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                            <span class="material-symbols-outlined">
                                                backup
                                            </span>
                                        </div>

                                        <h3 class="text-base font-semibold text-on-surface">
                                            No compressed images found
                                        </h3>

                                        <p class="mt-1 text-sm text-secondary">
                                            Backed-up compressed images will appear here after users compress and save images.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
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

                    @if (count($selectedImages) > 0)
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            {{ count($selectedImages) }} selected
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