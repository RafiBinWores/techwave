<?php

use App\Jobs\SplitPdfJob;
use App\Models\SplitPdf;
use App\Models\ToolCategory;
use App\Services\GhostscriptService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('PDF Splitter')] class extends Component {
    use WithFileUploads;

    public $file = null;

    public ?int $fileSize = null;

    public ?int $pageCount = null;

    public bool $previewLoading = false;

    public bool $previewReady = false;

    public bool $previewFailed = false;

    /** @var array<int, array{page: int, range: ?int, rangeLabel: ?string, url: string}> */
    public array $previewUrls = [];

    public string $mode = 'all';

    /** @var array<int, array{start: int, end: int}> */
    public array $ranges = [['start' => 1, 'end' => 2]];

    public bool $combineRanges = false;

    public string $customPages = '';

    public bool $processing = false;

    public ?string $startedAt = null;

    public int $progressPercent = 0;

    /** @var array<int, SplitPdf> */
    public array $records = [];

    private ?ToolCategory $category = null;

    public function boot(): void
    {
        $this->category = ToolCategory::query()->where('slug', 'pdf-tools')->first();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:' . (int) (config('pdf-compressor.max_upload_size') / 1024)],

            'mode' => ['required', 'in:all,range,custom'],

            'ranges' => ['required_if:mode,range', 'array', 'min:1', 'max:20'],

            'ranges.*.start' => ['required_if:mode,range', 'integer', 'min:1'],

            'ranges.*.end' => ['required_if:mode,range', 'integer', 'min:1', 'gte:ranges.*.start'],

            'customPages' => ['required_if:mode,custom', 'string', 'regex:/^[\d\s,\-]+$/'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'file' => 'PDF file',
            'mode' => 'split mode',
            'ranges' => 'page range',
            'ranges.*.start' => 'start page',
            'ranges.*.end' => 'end page',
            'customPages' => 'custom pages',
        ];
    }

    public function getMaxFilesProperty(): int
    {
        return 1;
    }

    public function getIsPremiumUserProperty(): bool
    {
        return $this->category !== null && auth()->check() && auth()->user()->hasActiveToolSubscription($this->category);
    }

    public function getMaxUploadSizeMbProperty(): int
    {
        return (int) (config('pdf-compressor.max_upload_size') / 1024 / 1024);
    }

    public function getModeLabelProperty(): string
    {
        return match ($this->mode) {
            'range' => count($this->ranges) > 1 ? 'Extract page ranges' : 'Extract page range',
            'custom' => 'Custom pages',
            default => 'Split all pages',
        };
    }

    private function retentionSettings(): array
    {
        if ($this->isPremiumUser) {
            $expiresAt = now()->addDays(30);

            return [
                'is_backup_enabled' => true,
                'expires_at' => $expiresAt,
                'backup_expires_at' => $expiresAt,
            ];
        }

        return [
            'is_backup_enabled' => false,
            'expires_at' => now()->addHour(),
            'backup_expires_at' => null,
        ];
    }

    public function updatedFile(): void
    {
        if ($this->file && is_object($this->file) && method_exists($this->file, 'getSize')) {
            try {
                $this->fileSize = (int) $this->file->getSize();
            } catch (\Throwable) {
                $realPath = method_exists($this->file, 'getRealPath') ? $this->file->getRealPath() : null;

                $this->fileSize = $realPath && is_file($realPath) ? (int) filesize($realPath) : null;
            }
        } else {
            $this->fileSize = null;
        }

        $this->resetValidation('file');
        $this->records = [];
        $this->processing = false;
        $this->progressPercent = 0;

        $this->clearPreview();

        $realPath = is_object($this->file) && method_exists($this->file, 'getRealPath') ? $this->file->getRealPath() : null;

        if ($this->file && $realPath && is_file($realPath)) {
            try {
                $this->pageCount = app(GhostscriptService::class)->pageCount($realPath);
            } catch (\Throwable) {
                $this->pageCount = null;
            }
        } else {
            $this->pageCount = null;
        }
    }

    public function removeFile(): void
    {
        $this->clearPreview();

        $this->file = null;
        $this->fileSize = null;
        $this->pageCount = null;

        $this->records = [];
        $this->processing = false;
        $this->progressPercent = 0;

        $this->resetErrorBag();
    }

    public function loadPreview(): void
    {
        if (!$this->file || $this->previewLoading) {
            return;
        }

        $this->previewLoading = true;
        $this->previewFailed = false;

        try {
            $realPath = is_object($this->file) && method_exists($this->file, 'getRealPath') ? $this->file->getRealPath() : null;

            if (!$realPath || !is_file($realPath)) {
                $this->previewLoading = false;

                return;
            }

            if ($this->pageCount === null) {
                $this->pageCount = app(GhostscriptService::class)->pageCount($realPath);
            }

            $pagesToRender = $this->previewPages();

            $disk = Storage::disk(config('pdf-compressor.storage_disk'));

            $directory = $this->previewDirectory();

            $disk->makeDirectory($directory);

            $urls = [];

            foreach ($pagesToRender as $entry) {
                $page = $entry['page'];
                $range = $entry['range'];

                $relative = $directory . '/page-' . ($range !== null ? 'range-' . $range . '-' : '') . $page . '.png';

                app(GhostscriptService::class)->renderPageImage(
                    inputPath: $realPath,
                    page: $page,
                    outputPath: $disk->path($relative),
                    resolution: 50,
                );

                $urls[] = [
                    'page' => $page,
                    'range' => $range,
                    'rangeLabel' => $entry['rangeLabel'] ?? null,
                    'url' => $disk->temporaryUrl($relative, now()->addMinutes(30)),
                ];
            }

            $this->previewUrls = $urls;
            $this->previewReady = true;
            $this->previewLoading = false;
        } catch (\Throwable $e) {
            report($e);

            $this->previewLoading = false;
            $this->previewReady = false;
            $this->previewFailed = true;
        }
    }

    /**
     * The pages shown in the preview, based on the current split mode.
     *
     * @return array<int, array{page: int, range: ?int, rangeLabel: ?string}>
     */
    private function previewPages(): array
    {
        $pageCount = (int) $this->pageCount;

        if ($pageCount <= 0) {
            return [];
        }

        if ($this->mode === 'range') {
            return $this->previewRangePages($pageCount);
        }

        if ($this->mode === 'custom') {
            return $this->previewCustomPages($pageCount);
        }

        $pages = $pageCount > 1 ? [1, $pageCount] : [1];

        return array_map(fn (int $page) => ['page' => $page, 'range' => null, 'rangeLabel' => null], $pages);
    }

    /**
     * Preview pages for custom mode. Each comma-separated token ("1", "3", "5-7")
     * is rendered as its own group showing its first and last page.
     *
     * @return array<int, array{page: int, range: ?int, rangeLabel: ?string}>
     */
    private function previewCustomPages(int $pageCount): array
    {
        $tokens = [];

        foreach (preg_split('/[,\s]+/', trim($this->customPages)) as $part) {
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_pad(explode('-', $part, 2), 2, null);

                $start = (int) $start;
                $end = (int) $end;

                if ($start <= 0 || $end < $start) {
                    continue;
                }

                $end = min($end, $pageCount);

                if ($end < $start) {
                    continue;
                }

                $tokens[] = [
                    'pages' => array_values(array_unique([$start, $end])),
                    'label' => $part,
                ];
            } else {
                $page = (int) $part;

                if ($page <= 0 || $page > $pageCount) {
                    continue;
                }

                $tokens[] = [
                    'pages' => [$page],
                    'label' => $part,
                ];
            }
        }

        if ($tokens === []) {
            $pages = $pageCount > 1 ? [1, $pageCount] : [1];

            return array_map(fn (int $page) => ['page' => $page, 'range' => null, 'rangeLabel' => null], $pages);
        }

        $preview = [];

        foreach ($tokens as $index => $token) {
            foreach ($token['pages'] as $page) {
                $preview[] = [
                    'page' => $page,
                    'range' => $index,
                    'rangeLabel' => $token['label'],
                ];
            }
        }

        return $preview;
    }

    /**
     * Preview pages for range mode. Each range is rendered as its own group
     * showing its first and last page, so overlapping ranges stay clearly
     * separated instead of being merged into a single combined range.
     *
     * @return array<int, array{page: int, range: ?int, rangeLabel: ?string}>
     */
    private function previewRangePages(int $pageCount): array
    {
        $ranges = [];

        foreach ($this->ranges as $range) {
            $start = (int) ($range['start'] ?? 0);
            $end = (int) ($range['end'] ?? 0);

            if ($start <= 0 || $end < $start) {
                continue;
            }

            $end = min($end, $pageCount);

            if ($end < $start) {
                continue;
            }

            $ranges[] = ['start' => $start, 'end' => $end];
        }

        if ($ranges === []) {
            $pages = $pageCount > 1 ? [1, $pageCount] : [1];

            return array_map(fn (int $page) => ['page' => $page, 'range' => null, 'rangeLabel' => null], $pages);
        }

        if ($this->combineRanges) {
            $pages = array_values(array_unique([
                $ranges[0]['start'],
                $ranges[count($ranges) - 1]['end'],
            ]));

            return array_map(fn (int $page) => ['page' => $page, 'range' => null, 'rangeLabel' => null], $pages);
        }

        $preview = [];

        foreach ($ranges as $index => $range) {
            $label = $range['start'] . '-' . $range['end'];

            foreach (array_values(array_unique([$range['start'], $range['end']])) as $page) {
                $preview[] = [
                    'page' => $page,
                    'range' => $index,
                    'rangeLabel' => $label,
                ];
            }
        }

        return $preview;
    }

    public function updatedMode(): void
    {
        $this->markPreviewDirty();
    }

    public function updatedRanges(): void
    {
        $this->markPreviewDirty();
    }

    public function updatedCustomPages(): void
    {
        $this->markPreviewDirty();
    }

    private function markPreviewDirty(): void
    {
        if (!$this->file) {
            return;
        }

        $this->previewUrls = [];
        $this->previewReady = false;
        $this->previewFailed = false;
    }

    private function previewDirectory(): string
    {
        $userId = Auth::id();

        $owner = $userId !== null ? 'users/' . $userId : 'guests/' . session()->getId();

        return 'split-preview/' . $owner;
    }

    private function clearPreview(): void
    {
        $this->pageCount = null;
        $this->previewReady = false;
        $this->previewLoading = false;
        $this->previewFailed = false;
        $this->previewUrls = [];

        Storage::disk(config('pdf-compressor.storage_disk'))->deleteDirectory($this->previewDirectory());
    }

    /**
     * Expand a custom pages string such as "1,3,5-7" into an ordered list.
     *
     * @return array<int, int>
     */
    private function parseCustomPages(): array
    {
        $pages = [];

        foreach (preg_split('/[,\s]+/', trim($this->customPages)) as $part) {
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_pad(explode('-', $part, 2), 2, null);

                $start = (int) $start;
                $end = (int) $end;

                if ($start <= 0 || $end < $start) {
                    continue;
                }

                foreach (range($start, $end) as $page) {
                    $pages[] = $page;
                }
            } else {
                $page = (int) $part;

                if ($page > 0) {
                    $pages[] = $page;
                }
            }
        }

        return array_values(array_unique($pages));
    }

    public function addRange(): void
    {
        $this->ranges[] = ['start' => 1, 'end' => 2];

        $this->resetValidation('ranges');

        $this->markPreviewDirty();
    }

    public function removeRange(int $index): void
    {
        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);

            $this->ranges = array_values($this->ranges);
        }

        if ($this->ranges === []) {
            $this->ranges[] = ['start' => 1, 'end' => 2];
        }

        $this->resetValidation('ranges');

        $this->markPreviewDirty();
    }

    /**
     * @return array{first_start: ?int, first_end: ?int, ranges: array<int, array{start: int, end: int}>}
     */
    private function normalizeRanges(): array
    {
        $ranges = [];
        $first = null;

        foreach ($this->ranges as $range) {
            $start = (int) ($range['start'] ?? 0);
            $end = (int) ($range['end'] ?? 0);

            if ($start <= 0 || $end < $start) {
                continue;
            }

            $first = $first ?? ['start' => $start, 'end' => $end];

            $ranges[] = ['start' => $start, 'end' => $end];
        }

        return [
            'first_start' => $first['start'] ?? null,
            'first_end' => $first['end'] ?? null,
            'ranges' => $ranges,
        ];
    }

    public function split(): void
    {
        if (!$this->file) {
            $this->dispatch('toast', message: 'Please select a PDF file to split.', type: 'error');

            return;
        }

        $this->validate();

        $selectedPages = null;
        $rangeData = null;

        if ($this->mode === 'custom') {
            $selectedPages = $this->parseCustomPages();

            if ($selectedPages === []) {
                $this->dispatch('toast', message: 'No valid pages found in your selection.', type: 'error');

                return;
            }
        } elseif ($this->mode === 'range') {
            $rangeData = $this->normalizeRanges();

            if ($rangeData['ranges'] === []) {
                $this->dispatch('toast', message: 'Enter at least one valid page range.', type: 'error');

                return;
            }
        }

        $file = $this->file;

        if (!is_object($file) || !method_exists($file, 'getSize')) {
            $this->dispatch('toast', message: 'The uploaded file is invalid. Please upload again.', type: 'error');

            return;
        }

        $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : null;

        if (!$path || !is_file($path)) {
            $this->dispatch('toast', message: 'The uploaded file could not be read. Please upload again.', type: 'error');

            return;
        }

        $this->processing = true;
        $this->startedAt = now()->toIso8601String();
        $this->progressPercent = 5;
        $this->records = [];

        $disk = config('pdf-compressor.storage_disk');

        $userId = Auth::id();

        $sessionId = $userId === null ? session()->getId() : null;

        $ownerFolder = $userId !== null ? 'users/' . $userId : 'guests/' . $sessionId;

        $directory = 'uploaded-pdfs/' . $ownerFolder;

        $retention = $this->retentionSettings();

        try {
            Storage::disk($disk)->makeDirectory($directory);

            $originalName = $file->getClientOriginalName();

            $uniqueId = Str::random(30);

            $storedPath = $file->storeAs($directory, $uniqueId . '.pdf', $disk);

            if (!$storedPath) {
                throw new RuntimeException('Unable to store uploaded PDF.');
            }

            $originalSize = (int) Storage::disk($disk)->size($storedPath);

            $record = SplitPdf::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'original_name' => $originalName,
                'original_path' => $storedPath,
                'original_size' => $originalSize,
                'mode' => $this->mode,
                'range_start' => $rangeData !== null ? $rangeData['first_start'] : null,
                'range_end' => $rangeData !== null ? $rangeData['first_end'] : null,
                'ranges' => $rangeData !== null ? $rangeData['ranges'] : null,
                'combine_ranges' => $this->mode === 'range' ? $this->combineRanges : false,
                'selected_pages' => $selectedPages,
                'is_backup_enabled' => $retention['is_backup_enabled'],
                'expires_at' => $retention['expires_at'],
                'backup_expires_at' => $retention['backup_expires_at'],
                'status' => 'pending',
            ]);

            SplitPdfJob::dispatchSync($record->id);

            $this->records[] = $record->fresh();

            $this->file = null;
            $this->fileSize = null;

            $this->dispatch('toast', message: 'PDF split successfully.', type: 'success');
        } catch (\Throwable $e) {
            report($e);

            $this->processing = false;

            $this->dispatch('toast', message: 'Upload failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function pollStatus(): void
    {
        if (empty($this->records)) {
            return;
        }

        $allDone = true;

        foreach ($this->records as $index => $record) {
            $refreshed = SplitPdf::find($record->id);

            if (!$refreshed) {
                continue;
            }

            $this->records[$index] = $refreshed;

            if (!$refreshed->isCompleted() && !$refreshed->isFailed()) {
                $allDone = false;
            }
        }

        if ($allDone) {
            $this->progressPercent = 100;
            $this->processing = false;

            return;
        }

        $elapsedSeconds = $this->startedAt ? (int) \Carbon\Carbon::parse($this->startedAt)->diffInSeconds(now()) : 0;

        $estimatedProgress = min(90, 8 + (int) floor($elapsedSeconds / 2));

        $this->progressPercent = max($this->progressPercent, $estimatedProgress);
    }

    public function downloadResult(int $index): mixed
    {
        if (!isset($this->records[$index])) {
            return null;
        }

        $record = SplitPdf::find($this->records[$index]->id);

        if (!$record || !$record->isCompleted()) {
            return null;
        }

        if ($record->isExpired()) {
            $this->dispatch('toast', message: 'This file has expired and was removed.', type: 'error');

            return null;
        }

        if (!$record->belongsToCurrentVisitor()) {
            abort(403);
        }

        if (!$record->outputFileExists()) {
            $this->dispatch('toast', message: 'The split output file was not found.', type: 'error');

            return null;
        }

        return Storage::disk(config('pdf-compressor.storage_disk'))->download($record->output_path, $record->outputDownloadName());
    }

    public function getElapsedTimeProperty(): string
    {
        if (!$this->startedAt) {
            return '0s';
        }

        $seconds = (int) now()->diffInSeconds($this->startedAt);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return $minutes . 'm ' . $remaining . 's';
    }

    public function startOver(): void
    {
        foreach ($this->records as $record) {
            if ($record->is_backup_enabled) {
                continue;
            }

            $record->deleteOriginalFile();
            $record->deleteOutputFile();
            $record->delete();
        }

        $this->clearPreview();

        $this->reset(['file', 'fileSize', 'records', 'processing', 'mode', 'ranges', 'combineRanges', 'customPages', 'startedAt', 'progressPercent']);

        $this->mode = 'all';
        $this->ranges = [['start' => 1, 'end' => 2]];
        $this->combineRanges = false;
        $this->customPages = '';
        $this->startedAt = null;
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

<div class="min-h-screen text-white">
    <main class="mx-auto flex w-full max-w-7xl flex-col items-center px-4 pb-24 pt-8 md:pt-10 sm:px-6 lg:px-8">

        <div class="mb-10 text-center">
            <h1 class="text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl">
                Split your
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text italic text-transparent">PDFs</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 md:text-lg">
                Split a PDF into individual pages, extract a page range, or pull out only the pages you need — all
                powered by Ghostscript.
            </p>
        </div>

        @php
        $hasRecords = !empty($this->records);
        $allCompleted = $hasRecords && collect($this->records)->every(fn($r) => $r->isCompleted() || $r->isFailed());
        $anyProcessing = $hasRecords && collect($this->records)->contains(fn($r) => $r->isProcessing());
        @endphp

        @if ($hasRecords && $anyProcessing)
        <div wire:poll.2s.keep-alive="pollStatus" class="w-full">
            @elseif ($hasRecords && $allCompleted)
            <div class="w-full">
                @else
                <div class="w-full">
                    @endif

                    {{-- Results View --}}
                    @if ($hasRecords && $allCompleted)
                    <section
                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)] backdrop-blur-2xl sm:p-8">
                        <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                        </div>

                        <div class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-extrabold text-white">Split complete</h2>
                                <p class="mt-1 text-sm text-blue-100/55">
                                    {{ count($this->records) }} file(s) processed.
                                </p>
                            </div>
                            <button type="button" wire:click="startOver" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/8 px-4 py-2 text-sm font-semibold text-white transition hover:border-cyan-400/30 hover:bg-white/12 cursor-pointer">
                                <span class="material-symbols-outlined text-base">refresh</span>
                                Start over
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach ($this->records as $index => $record)
                            <div class="relative flex items-center gap-4 rounded-xl border border-white/10 bg-slate-950/25 p-4">
                                <div
                                    class="h-2.5 w-2.5 shrink-0 rounded-full {{ $record->isFailed() ? 'bg-red-400 shadow-lg shadow-red-400/25' : 'bg-emerald-400 shadow-lg shadow-emerald-400/25' }}">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">{{ $record->original_name }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-blue-100/50">
                                        @if ($record->page_count)
                                        <span>{{ $record->page_count }} page(s)</span>
                                        @endif
                                        @if ($record->isFailed())
                                        <span class="font-semibold text-red-400">Failed:
                                            {{ $record->error_message }}</span>
                                        @elseif ($record->output_size)
                                        <span class="text-blue-100/30">&rarr;</span>
                                        <span class="font-semibold text-cyan-300">{{ $this->formatBytes($record->output_size) }}</span>
                                        @if ($record->outputIsZip())
                                        <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-cyan-300">ZIP</span>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                                @if ($record->isCompleted())
                                <button type="button" wire:click="downloadResult({{ $index }})"
                                    wire:loading.attr="disabled"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-linear-to-r from-cyan-500 to-blue-500 px-3 py-1.5 text-xs font-bold text-white transition hover:-translate-y-0.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                    Download
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Processing View --}}
                    @elseif ($hasRecords && $anyProcessing)
                    <section wire:key="pdf-split-processing-panel"
                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)] backdrop-blur-2xl sm:p-8">
                        <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                        </div>

                        <div class="relative flex flex-col items-center text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-cyan-400/10 text-cyan-300">
                                <span class="h-8 w-8 animate-spin rounded-full border-2 border-cyan-100/30 border-t-cyan-100"></span>
                            </div>
                            <h2 class="mt-5 text-2xl font-extrabold text-white">Splitting your PDF...</h2>
                            <p class="mt-2 text-sm text-blue-100/55">
                                {{ $this->modeLabel }} &middot; {{ count($this->records) }} file(s) queued.
                            </p>
                            <p class="mt-1 text-xs text-blue-100/40">
                                Elapsed: {{ $this->elapsedTime }} &middot; Large files may take a few minutes.
                            </p>

                            <div class="mt-6 w-full max-w-sm">
                                <div class="mb-2 flex items-center justify-between text-xs">
                                    <span class="text-blue-100/50">Progress</span>
                                    <span class="font-semibold text-cyan-300">
                                        {{ $progressPercent }}%
                                    </span>
                                </div>

                                <div class="h-3 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-linear-to-r from-cyan-400 to-blue-500 transition-[width] duration-700 ease-out"
                                        style="width: {{ $progressPercent }}%">
                                    </div>
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-blue-100/45">
                                Please keep this page open while your PDF is being processed.
                            </p>
                        </div>
                    </section>

                    {{-- Upload + Settings View --}}
                    @else
                    <div class="grid w-full grid-cols-1 gap-8 lg:grid-cols-12">
                        <section
                            class="relative flex min-h-140 flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl lg:col-span-8 sm:p-8">
                            <div class="pointer-events-none absolute inset-0 rounded-2xl bg-linear-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                            </div>

                            <div wire:loading.flex wire:target="file"
                                class="absolute inset-0 z-30 items-center justify-center rounded-2xl bg-slate-950/90 backdrop-blur-md">
                                <div class="flex flex-col items-center gap-4 text-center">
                                    <span class="h-10 w-10 animate-spin rounded-full border-2 border-cyan-100/30 border-t-cyan-300"></span>
                                    <div>
                                        <p class="font-semibold text-white">Uploading PDF...</p>
                                        <p class="mt-1 text-sm text-blue-100/50">Please wait a moment.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="relative mb-6 flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-extrabold text-white">PDF to Split</h2>
                                    <p class="mt-1 text-sm text-blue-100/45">
                                        @if ($this->file)
                                        1 file selected.
                                        @else
                                        No PDF selected yet.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if ($this->file && is_object($this->file) && method_exists($this->file, 'getClientOriginalName'))
                            <div class="relative flex flex-1 flex-col gap-3 rounded-xl border border-white/10 bg-slate-950/25 p-4">
                                <div wire:key="split-file"
                                    class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/4 px-4 py-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/10 text-cyan-300">
                                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-white">
                                            {{ $this->file->getClientOriginalName() }}
                                        </p>
                                        <p class="text-xs text-blue-100/50">
                                            {{ $this->formatBytes($this->fileSize) }}
                                            @if ($this->pageCount !== null)
                                                &middot; {{ $this->pageCount }} {{ Str::plural('page', $this->pageCount) }}
                                            @endif
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeFile"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-400/20 bg-red-400/10 text-red-300 transition hover:border-red-400/30 hover:bg-red-400/15 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">close</span>
                                    </button>
                                </div>

                                <div wire:key="split-preview" class="space-y-3"
                                    x-data="{ ready: @entangle('previewReady').live }"
                                    x-effect="if (!ready) { $wire.loadPreview(); }">
                                    <div class="flex items-center justify-between">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-100/50">
                                            Page Preview
                                        </p>
                                        @if ($this->pageCount !== null)
                                            <span class="text-[11px] text-blue-100/40">
                                                {{ $this->pageCount }} {{ Str::plural('page', $this->pageCount) }} total
                                            </span>
                                        @endif
                                    </div>

                                    @if ($this->previewReady && !empty($this->previewUrls))
                                        @php
                                        $previewGroups = collect($this->previewUrls)->groupBy('range')->values();
                                        @endphp
                                        <div class="{{ $this->mode === 'custom' ? 'flex flex-wrap items-start gap-3' : 'space-y-3' }}">
                                            @foreach ($previewGroups as $group)
                                                @php
                                                $groupedItems = $group->values()->all();
                                                $rangeLabel = $groupedItems[0]['rangeLabel'] ?? null;
                                                @endphp
                                                <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-950/25 p-3">
                                                    @if ($rangeLabel !== null)
                                                        <div class="mb-2.5 flex items-center gap-1.5">
                                                            <span class="material-symbols-outlined text-sm text-cyan-300">{{ $this->mode === 'custom' ? 'select_all' : 'filter_alt' }}</span>
                                                            <span class="text-[11px] font-bold uppercase tracking-wider text-cyan-200">
                                                                @if ($this->mode === 'custom')
                                                                Pages {{ $rangeLabel }}
                                                                @else
                                                                Range {{ $rangeLabel }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div class="flex flex-wrap items-center justify-center gap-3">
                                                        @foreach ($groupedItems as $thumb)
                                                            <div
                                                                class="w-20 overflow-hidden rounded-lg border border-white/10 bg-white/5 sm:w-32">
                                                                <img src="{{ $thumb['url'] }}" alt="Page {{ $thumb['page'] }}"
                                                                    class="aspect-[3/4] w-full object-cover" loading="lazy" />
                                                                <p
                                                                    class="border-t border-white/10 bg-slate-950/60 py-1 text-center text-[11px] font-bold text-cyan-200">
                                                                    Page {{ $thumb['page'] }}
                                                                </p>
                                                            </div>
                                                            @if (!$loop->last)
                                                                <span class="material-symbols-outlined shrink-0 text-lg text-blue-100/30">arrow_right_alt</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($this->previewFailed)
                                        <p class="rounded-lg border border-white/10 bg-white/4 px-3 py-2 text-[11px] text-blue-100/40">
                                            Preview unavailable.
                                        </p>
                                    @else
                                        <div class="flex h-24 items-center justify-center gap-3 rounded-lg border border-white/10 bg-white/4">
                                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-cyan-100/30 border-t-cyan-300"></span>
                                            <span class="text-xs text-blue-100/55">Generating page preview...</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @else
                            <label for="pdf-upload"
                                class="group relative flex flex-1 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-cyan-300/25 bg-slate-950/25 p-8 text-center transition hover:border-cyan-300/50 hover:bg-cyan-400/5 sm:p-12"
                                x-data="{ dragover: false }" x-on:dragover.prevent="dragover = true"
                                x-on:dragleave.prevent="dragover = false" x-on:drop.prevent="dragover = false"
                                :class="{ 'border-cyan-300/50 bg-cyan-400/5': dragover }">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/10 text-cyan-300 transition group-hover:scale-110">
                                    <span class="material-symbols-outlined text-4xl">call_split</span>
                                </div>
                                <h3 class="mt-6 text-2xl font-extrabold text-white">
                                    Drop your PDF here
                                </h3>
                                <p class="mt-2 text-sm text-blue-100/55">
                                    Or click to browse. PDF only, up to {{ $this->max_upload_size_mb }}MB.
                                </p>
                                <div
                                    class="mt-8 inline-flex items-center gap-2 rounded-xl border border-cyan-300/30 bg-cyan-400/10 px-8 py-3 font-bold text-cyan-200 transition hover:bg-cyan-400/15">
                                    <span class="material-symbols-outlined text-xl">upload_file</span>
                                    Browse Files
                                </div>
                                <div class="mt-6 flex flex-wrap justify-center gap-2">
                                    <span class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[11px] font-medium text-blue-100/60">PDF</span>
                                    <span class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[11px] text-blue-100/60">{{ $this->max_upload_size_mb }}MB</span>
                                </div>
                            </label>
                            @endif

                            <input id="pdf-upload" type="file" wire:model="file" accept="application/pdf" class="hidden" />
                        </section>

                        <aside class="flex flex-col gap-6 lg:col-span-4">
                            <div
                                class="flex h-full flex-col rounded-2xl border border-white/10 bg-white/6 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-8 lg:sticky lg:top-24">
                                <div class="mb-8 flex items-center gap-4">
                                    <div class="rounded-xl border border-cyan-300/15 bg-cyan-400/10 p-3 text-cyan-300">
                                        <span class="material-symbols-outlined">call_split</span>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-extrabold leading-none text-white">Split Mode</h2>
                                        <p class="mt-1 text-xs text-blue-100/45">Choose how to split your PDF.</p>
                                    </div>
                                </div>

                                <div class="mb-6 space-y-3">
                                    @php
                                    $modes = [
                                    'all' => ['label' => 'Split all pages', 'description' => 'Create one PDF per page and download them as a ZIP.', 'icon' => 'view_agenda'],
                                    'range' => ['label' => 'Extract page range', 'description' => 'Pull one or more ranges — one PDF per range, or combine them into one.', 'icon' => 'filter_alt'],
                                    'custom' => ['label' => 'Custom pages', 'description' => 'Pick specific pages, e.g. 1,3,5-7.', 'icon' => 'select_all'],
                                    ];
                                    @endphp
                                    @foreach ($modes as $key => $option)
                                    <label wire:key="mode-{{ $key }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition {{ $option === $this->mode ? 'border-cyan-400/30 bg-cyan-400/5' : 'border-white/10 bg-slate-950/25 hover:border-cyan-400/20 hover:bg-cyan-400/3' }}">
                                        <span class="relative mt-0.5 shrink-0">
                                            <input type="radio" wire:model.live="mode" value="{{ $key }}"
                                                class="peer sr-only" />
                                            <span
                                                class="block h-5 w-5 rounded-full border-2 border-white/20 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-400/20"></span>
                                            <span class="absolute inset-0 m-auto hidden h-2 w-2 rounded-full bg-cyan-300 peer-checked:block"></span>
                                        </span>
                                        <span class="flex-1">
                                            <span class="flex items-center gap-2 text-sm font-bold text-white">
                                                <span class="material-symbols-outlined text-base text-cyan-300">{{ $option['icon'] }}</span>
                                                {{ $option['label'] }}
                                            </span>
                                            <span class="mt-0.5 block text-[11px] leading-5 text-blue-100/45">{{ $option['description'] }}</span>
                                        </span>
                                    </label>
                                    @endforeach
                                </div>

                                @if ($mode === 'range')
                                <div class="mb-6 space-y-3 rounded-xl border border-white/10 bg-slate-950/25 p-4">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-blue-100/50">
                                        Page ranges
                                    </p>
                                    <div class="space-y-3">
                                        @foreach ($this->ranges as $index => $range)
                                        <div wire:key="range-row-{{ $index }}" class="flex items-end gap-2">
                                            <div class="min-w-0 flex-1">
                                                <label for="range-start-{{ $index }}"
                                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-blue-100/50">
                                                    Start
                                                </label>
                                                <input id="range-start-{{ $index }}" type="number"
                                                    wire:model.live.debounce.600ms="ranges.{{ $index }}.start" min="1"
                                                    class="w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2.5 text-sm font-semibold text-white focus:border-cyan-400/40 focus:outline-none" />
                                                @error('ranges.' . $index . '.start')
                                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <label for="range-end-{{ $index }}"
                                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-blue-100/50">
                                                    End
                                                </label>
                                                <input id="range-end-{{ $index }}" type="number"
                                                    wire:model.live.debounce.600ms="ranges.{{ $index }}.end" min="1"
                                                    class="w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2.5 text-sm font-semibold text-white focus:border-cyan-400/40 focus:outline-none" />
                                                @error('ranges.' . $index . '.end')
                                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <button type="button" wire:click="removeRange({{ $index }})"
                                                title="Remove range"
                                                class="inline-flex h-10.5 w-10 shrink-0 items-center justify-center rounded-lg border border-red-400/20 bg-red-400/10 text-red-300 transition hover:border-red-400/30 hover:bg-red-400/15 cursor-pointer">
                                                <span class="material-symbols-outlined text-lg">close</span>
                                            </button>
                                        </div>
                                        @endforeach
                                    </div>
                                    <button type="button" wire:click="addRange"
                                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-white/10 bg-white/8 px-3 py-2 text-xs font-bold text-white transition hover:border-cyan-400/30 hover:bg-white/12 cursor-pointer">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        Add range
                                    </button>
                                    <label class="mt-1 flex cursor-pointer items-center gap-2.5 rounded-lg border border-white/10 bg-white/4 px-3 py-2.5 transition hover:border-cyan-400/25">
                                        <span class="relative shrink-0">
                                            <input type="checkbox" wire:model.live="combineRanges"
                                                class="peer sr-only" />
                                            <span
                                                class="block h-5 w-5 rounded-md border-2 border-white/20 transition peer-checked:border-cyan-400 peer-checked:bg-cyan-400/20"></span>
                                            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                                class="absolute inset-0 m-auto h-3 w-3 text-cyan-300 opacity-0 transition peer-checked:opacity-100">
                                                <path d="M2 6l2.5 2.5L10 3" />
                                            </svg>
                                        </span>
                                        <span class="flex-1 text-xs font-semibold text-blue-100/70">
                                            Combine all ranges into one PDF
                                        </span>
                                    </label>
                                    @error('ranges')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                                @if ($mode === 'custom')
                                <div class="mb-6 space-y-3 rounded-xl border border-white/10 bg-slate-950/25 p-4">
                                    <label for="custom-pages" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-blue-100/50">
                                        Pages (e.g. 1,3,5-7)
                                    </label>
                                <input id="custom-pages" type="text" wire:model.live.debounce.600ms="customPages"
                                    placeholder="1,3,5-7"
                                        class="w-full rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2.5 text-sm font-semibold text-white placeholder:text-blue-100/30 focus:border-cyan-400/40 focus:outline-none" />
                                    @error('customPages')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                                @if (!$this->file)
                                <button type="button" disabled
                                    class="mt-8 flex w-full cursor-not-allowed items-center justify-center gap-3 rounded-xl bg-white/8 px-6 py-4 font-extrabold text-white/30">
                                    <span class="material-symbols-outlined">call_split</span>
                                    Select a PDF to split
                                </button>
                                @else
                                <button type="button" wire:click="split" wire:loading.attr="disabled"
                                    class="group relative mt-8 flex w-full items-center justify-center gap-3 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-4 font-extrabold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-[0_0_25px_rgba(34,211,238,0.35)] active:translate-y-0 disabled:opacity-60 cursor-pointer">
                                    <span class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                                    <span wire:loading.remove wire:target="split" class="relative flex items-center gap-2">
                                        <span class="material-symbols-outlined">call_split</span>
                                        Split PDF
                                    </span>
                                    <span wire:loading wire:target="split" class="relative flex items-center gap-2">
                                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        Splitting...
                                    </span>
                                </button>
                                @endif
                            </div>
                        </aside>
                    </div>

                    <div class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-slate-950/90 backdrop-blur-xl lg:hidden">
                        <div class="mx-auto max-w-3xl px-4 py-3">
                            @if ($this->file)
                            <div class="flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">1 PDF selected</p>
                                    <p class="text-xs text-blue-100/45">
                                        {{ $this->modeLabel }}
                                    </p>
                                </div>
                                <button type="button" wire:click="split" wire:loading.attr="disabled"
                                    class="group relative flex shrink-0 items-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition active:scale-95 disabled:opacity-60 cursor-pointer">
                                    <span wire:loading.remove wire:target="split" class="relative flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-base">call_split</span>
                                        Split
                                    </span>
                                    <span wire:loading wire:target="split" class="relative flex items-center gap-1.5">
                                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        Working...
                                    </span>
                                </button>
                            </div>
                            @else
                            <label for="pdf-upload"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-500 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25">
                                <span class="material-symbols-outlined text-base">upload_file</span>
                                Choose PDF to Split
                            </label>
                            @endif
                        </div>
                    </div>

                    <div class="h-20 lg:hidden"></div>
                    @endif
                </div>

                <div class="mt-12 grid w-full gap-6 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/6 p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500/15 text-cyan-300">
                            <span class="material-symbols-outlined">upload</span>
                        </div>
                        <h3 class="mt-4 font-semibold text-white">1. Upload</h3>
                        <p class="mt-2 text-sm text-blue-100/62">
                            Select a PDF up to {{ $this->max_upload_size_mb }}MB from your device.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/6 p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/15 text-blue-300">
                            <span class="material-symbols-outlined">call_split</span>
                        </div>
                        <h3 class="mt-4 font-semibold text-white">2. Choose Mode</h3>
                        <p class="mt-2 text-sm text-blue-100/62">
                            Split all pages, extract a range, or pick custom pages.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/6 p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                            <span class="material-symbols-outlined">download</span>
                        </div>
                        <h3 class="mt-4 font-semibold text-white">3. Download</h3>
                        <p class="mt-2 text-sm text-blue-100/62">
                            Download your split PDFs, or a ZIP when split into many pages.
                        </p>
                    </div>
                </div>
    </main>
</div>