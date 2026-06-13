<?php

use App\Models\UserResizedImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Resized Images')] class extends Component {
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
                                @click="sidebarOpen = true"
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
                                    Resized Images
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ allSelected: false }">
                        {{-- Bulk Action Bar --}}
                        <template x-if="$wire.selectedIds.length > 0">
                            <div class="mb-4 flex items-center justify-between rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-3 backdrop-blur-xl">
                                <p class="text-sm text-red-200">
                                    <span x-text="$wire.selectedIds.length"></span> image(s) selected
                                </p>
                                <div class="flex items-center gap-2">
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

                        {{-- Table --}}
                        @forelse ($this->images() as $image)
                            <div class="flex items-center gap-4 border-b border-white/10 px-2 py-3 transition hover:bg-white/[0.03]">
                                <label class="relative flex h-5 w-5 shrink-0 cursor-pointer items-center justify-center">
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $image->id }}"
                                        class="peer sr-only">
                                    <span class="block h-5 w-5 rounded-md border border-white/20 bg-white/5 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-500/20 peer-focus:ring-2 peer-focus:ring-cyan-400/30"></span>
                                    <svg class="pointer-events-none absolute hidden peer-checked:block h-3.5 w-3.5 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </label>

                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    @if ($image->fileExists() && $image->previewUrl())
                                        <button type="button" @click="preview = '{{ $image->previewUrl() }}'"
                                            class="group relative h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-cyan-400/10 transition hover:scale-105">
                                            <img src="{{ $image->previewUrl() }}"
                                                alt="{{ $image->original_name }}"
                                                class="h-full w-full object-contain"
                                                loading="lazy">
                                            <span class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition group-hover:opacity-100">
                                                <span class="material-symbols-outlined text-sm text-white">zoom_in</span>
                                            </span>
                                        </button>
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-400/10 text-cyan-300">
                                            <span class="material-symbols-outlined text-lg">image</span>
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-white" title="{{ $image->original_name }}">
                                            {{ $image->original_name }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-blue-100/50">
                                            <span class="rounded bg-white/8 px-1.5 py-0.5 uppercase">{{ $image->resized_ext }}</span>
                                            <span>{{ number_format($image->original_size / 1024, 1) }} KB</span>
                                            <span class="text-blue-100/30">→</span>
                                            <span class="text-cyan-300">{{ number_format($image->resized_size / 1024, 1) }} KB</span>
                                            @if ($image->toolCategory)
                                                <span>· {{ $image->toolCategory->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($image->isExpired())
                                        <span class="text-xs font-semibold text-red-300">Expired</span>
                                    @else
                                        <span class="text-xs text-blue-100/45">{{ $image->expires_at->format('M d, Y') }}</span>
                                    @endif

                                    @if (!$image->isExpired() && $image->fileExists())
                                        <button type="button" wire:click="download({{ $image->id }})"
                                            class="flex items-center gap-1 rounded-lg bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-white/15">
                                            <span class="material-symbols-outlined text-sm leading-none">download</span>
                                        </button>
                                    @endif

                                    <button type="button" wire:click="delete({{ $image->id }})"
                                        wire:confirm="Delete this backup? This cannot be undone."
                                        class="flex items-center gap-1 rounded-lg bg-red-500/10 px-2.5 py-1.5 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                        <span class="material-symbols-outlined text-sm leading-none">delete</span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-white/15 bg-white/[0.07] p-12 text-center backdrop-blur-2xl">
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
                            </div>
                        @endforelse

                        <div class="mt-4">
                            {{ $this->images()->links() }}
                        </div>
                    </div>

                </div>
            </div>
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
