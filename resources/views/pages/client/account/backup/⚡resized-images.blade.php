<?php

use App\Models\UserResizedImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.account-app')] #[Title('My Resized Images')] class extends Component {
    use WithPagination;

    public array $selectedIds = [];

    public function delete(int $id): void
    {
        $image = UserResizedImage::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $image->deleteFile();
        $image->delete();

        $this->dispatch('toast', message: 'Image deleted from backup.', type: 'success');
    }

    public function deleteSelected(): void
    {
        $count = 0;

        UserResizedImage::query()
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->selectedIds)
            ->each(function ($image) use (&$count) {
                $image->deleteFile();
                $image->delete();
                $count++;
            });

        $this->selectedIds = [];

        $this->dispatch('toast', message: "{$count} image(s) deleted from backup.", type: 'success');
    }

    public function download(int $id): mixed
    {
        $image = UserResizedImage::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        if (!$image->fileExists()) {
            $this->dispatch('toast', message: 'File no longer available. It may have expired.', type: 'error');
            return null;
        }

        return Storage::disk('public')->download($image->resized_path, $image->downloadName());
    }

    public function downloadSelected(): mixed
    {
        $images = UserResizedImage::query()
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->selectedIds)
            ->get();

        if ($images->isEmpty()) {
            $this->dispatch('toast', message: 'No files available to download.', type: 'error');
            return null;
        }

        $zip = new \ZipArchive;
        $zipPath = tempnam(sys_get_temp_dir(), 'resized_') . '.zip';

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            $this->dispatch('toast', message: 'Failed to create ZIP archive.', type: 'error');
            return null;
        }

        foreach ($images as $image) {
            if ($image->fileExists()) {
                $zip->addFromString($image->downloadName(), Storage::disk('public')->get($image->resized_path));
            }
        }

        $zip->close();

        $this->selectedIds = [];

        return response()->download($zipPath, 'resized-images.zip')->deleteFileAfterSend(true);
    }

    public function images()
    {
        UserResizedImage::query()
            ->where('user_id', auth()->id())
            ->where('expires_at', '<=', now())
            ->each(function ($image) {
                $image->deleteFile();
                $image->delete();
            });

        return UserResizedImage::query()
            ->with('toolCategory')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);
    }
};
?>

<div x-data="{ preview: null }">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-xl font-bold md:text-h1 text-white">Resized Images</h2>
            <p class="text-xs md:text-body-md text-blue-100/60">
                Your resized image backups.
            </p>
        </div>
    </div>

                    <div>
                        {{-- Bulk Action Bar --}}
                        <template x-if="$wire.selectedIds.length > 0">
                            <div class="mb-4 flex flex-col gap-3 rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-3 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-red-200">
                                    <span x-text="$wire.selectedIds.length"></span> image(s) selected
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="$set('selectedIds', [])"
                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white/70 transition hover:bg-white/10">
                                        Clear
                                    </button>
                                    <button type="button" wire:click="downloadSelected"
                                        class="rounded-lg bg-cyan-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-600">
                                        Download ZIP
                                    </button>
                                    <button type="button" wire:click="deleteSelected"
                                        wire:confirm="Delete all selected backups? This cannot be undone."
                                        class="rounded-lg bg-red-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-600">
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Table with horizontal scroll --}}
                        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.06] shadow-[0_20px_80px_rgba(0,0,0,0.22)] backdrop-blur-2xl">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-white/10 text-xs font-semibold uppercase tracking-wider text-blue-100/45">
                                            <th class="px-4 py-3.5"></th>
                                            <th class="px-4 py-3.5">Preview</th>
                                            <th class="px-4 py-3.5">File</th>
                                            <th class="px-4 py-3.5">Size</th>
                                            <th class="px-4 py-3.5">Expires</th>
                                            <th class="px-4 py-3.5">Status</th>
                                            <th class="px-4 py-3.5">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/10">
                                        @forelse ($this->images() as $image)
                                            <tr class="transition hover:bg-white/[0.03]">
                                                <td class="px-4 py-3">
                                                    <label class="relative flex h-5 w-5 cursor-pointer items-center justify-center">
                                                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $image->id }}"
                                                            class="peer sr-only">
                                                        <span class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/20 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>
                                                        <svg class="pointer-events-none absolute hidden peer-checked:block h-3.5 w-3.5 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($image->fileExists() && $image->previewUrl())
                                                        <button type="button" @click="preview = '{{ $image->previewUrl() }}'"
                                                            class="group relative block h-10 w-10 overflow-hidden rounded-lg bg-cyan-400/10 transition hover:scale-110">
                                                            <img src="{{ $image->previewUrl() }}"
                                                                alt="{{ $image->original_name }}"
                                                                class="h-full w-full object-contain"
                                                                loading="lazy">
                                                            <span class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition group-hover:opacity-100">
                                                                <span class="material-symbols-outlined text-sm text-white">zoom_in</span>
                                                            </span>
                                                        </button>
                                                    @else
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-400/10 text-cyan-300">
                                                            <span class="material-symbols-outlined text-lg">image</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="max-w-[200px] truncate px-4 py-3">
                                                    <p class="truncate font-semibold text-white" title="{{ $image->original_name }}">
                                                        {{ $image->original_name }}
                                                    </p>
                                                    <span class="mt-0.5 inline-block rounded bg-white/8 px-1.5 py-0.5 text-[11px] font-medium uppercase text-blue-100/50">
                                                        {{ $image->resized_ext }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3">
                                                    <p class="text-cyan-100/70">{{ number_format($image->original_size / 1024, 1) }} KB</p>
                                                    <p class="text-xs text-blue-100/40">→ {{ number_format($image->resized_size / 1024, 1) }} KB</p>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-blue-100/60">
                                                    {{ $image->expires_at->format('M d, Y') }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($image->isExpired())
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-500/15 px-2.5 py-0.5 text-xs font-semibold text-red-300">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                                                            Expired
                                                        </span>
                                                    @elseif ($image->fileExists())
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-300">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-300">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                                            Missing
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3">
                                                    <div class="flex items-center gap-1">
                                                        @if (!$image->isExpired() && $image->fileExists())
                                                            <button type="button" wire:click="download({{ $image->id }})"
                                                                class="rounded-lg p-2 text-blue-100/50 transition hover:bg-white/10 hover:text-cyan-300"
                                                                title="Download">
                                                                <span class="material-symbols-outlined text-lg">download</span>
                                                            </button>
                                                        @endif
                                                        <button type="button" wire:click="delete({{ $image->id }})"
                                                            wire:confirm="Delete this backup? This cannot be undone."
                                                            class="rounded-lg p-2 text-blue-100/50 transition hover:bg-red-500/15 hover:text-red-300"
                                                            title="Delete">
                                                            <span class="material-symbols-outlined text-lg">delete</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-12 text-center">
                                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/8">
                                                        <span class="material-symbols-outlined text-3xl text-blue-100/40">backup</span>
                                                    </div>
                                                    <h3 class="mt-4 text-lg font-semibold text-white">No backed-up images yet</h3>
                                                    <p class="mt-2 text-sm text-blue-100/50">Resize images as a premium user to get 30-day backup.</p>
                                                    <a href="{{ route('client.tools.image-resizer') }}" wire:navigate
                                                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-3 font-semibold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5">
                                                        <span class="material-symbols-outlined text-base">photo_size_select_large</span>
                                                        Resize Images
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4">
                            {{ $this->images()->links() }}
                        </div>
                    </div>

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div x-show="preview" x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
            @click="preview = null" @keydown.escape.window="preview = null">
            <button type="button" @click="preview = null"
                class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                <span class="material-symbols-outlined">close</span>
            </button>
            <img :src="preview" @click.stop
                class="max-h-[90vh] max-w-[90vw] rounded-2xl object-contain shadow-2xl">
        </div>
    </template>
</div>
