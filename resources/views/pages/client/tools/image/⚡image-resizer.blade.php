<?php

use App\Models\ToolCategory;
use App\Models\ToolUsage;
use App\Models\UserResizedImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Image Resizer')] class extends Component {
    use WithFileUploads;

    private const MAX_IMAGE_SIZE_KB = 10240;

    private ?ToolCategory $category = null;

    public array $images = [];
    public array $results = [];

    public int $width = 800;
    public int $height = 600;
    public bool $maintainAspectRatio = true;
    public string $resizeMode = 'fit';
    public string $resizeUnit = 'px';

    public bool $processing = false;
    public int $dailyUsage = 0;

    public int $resizeProgress = 0;
    public int $totalImages = 0;
    public int $processedImages = 0;

    public function boot(): void
    {
        $this->category = ToolCategory::query()->where('slug', 'image-tools')->first();

        if (auth()->check()) {
            $this->dailyUsage = ToolUsage::query()
                ->where('user_id', auth()->id())
                ->where('tool_type', 'image_resizer')
                ->where('period', now()->format('Y-m-d'))
                ->sum('usage_count');
        }
    }

    public function getMaxImagesProperty(): int
    {
        if (!$this->category) {
            return 30;
        }

        return auth()->user()?->maxFileUploadFor($this->category) ?? ($this->category->free_max_file_upload ?? 30);
    }

    public function getIsPremiumUserProperty(): bool
    {
        return $this->category && auth()->check() && auth()->user()->hasActiveToolSubscription($this->category);
    }

    public function updatedImages(): void
    {
        if (count($this->images) > $this->max_images) {
            $this->images = array_slice($this->images, 0, $this->max_images);

            $this->dispatch('toast', message: 'You selected more than ' . $this->max_images . ' images. Only the first ' . $this->max_images . ' images were added.', type: 'warning');
        }

        try {
            $this->validate(
                [
                    'images' => ['required', 'array', 'max:' . $this->max_images],
                    'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_SIZE_KB],
                ],
                $this->messages(),
            );

            $this->resetErrorBag();
            $this->results = [];
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $this->uploadErrorMessage($e), type: 'error');

            $this->resetErrorBag();
            $this->results = [];
        }
    }

    protected function messages(): array
    {
        return [
            'images.required' => 'Please upload at least one image.',
            'images.array' => 'Please upload valid image files.',
            'images.max' => 'You selected more than ' . $this->max_images . ' images. Only the first ' . $this->max_images . ' images will be processed.',

            'images.*.uploaded' => 'One image failed to upload. Please check file size, type, or upload fewer images.',
            'images.*.image' => 'One selected file is not a valid image.',
            'images.*.mimes' => 'Only JPG, JPEG, PNG, and WebP images are allowed.',
            'images.*.max' => 'Each image must be 10MB or smaller.',
        ];
    }

    private function uploadErrorMessage(ValidationException $e): string
    {
        $errors = $e->validator->errors()->messages();

        foreach ($errors as $field => $messages) {
            if (preg_match('/^images\.(\d+)/', $field, $matches)) {
                $imageNumber = ((int) $matches[1]) + 1;

                return "Image #{$imageNumber} failed to upload. Please check file size, type, or upload fewer images.";
            }
        }

        if (isset($errors['images'])) {
            return $errors['images'][0] ?? 'Image upload failed. Please try again.';
        }

        return 'Image upload failed. Please check file size, type, or upload fewer images.';
    }

    public function removeImage(int $index): void
    {
        if (!isset($this->images[$index])) {
            return;
        }

        unset($this->images[$index]);

        $this->images = array_values($this->images);
        $this->results = [];

        $this->resetErrorBag();
    }

    public function resize(): void
    {
        $validImages = $this->processableImages();

        if (empty($validImages)) {
            $this->dispatch('toast', message: 'No valid images found. Please upload again.', type: 'error');

            return;
        }

        if (count($this->images) > count($validImages)) {
            $this->dispatch('toast', message: count($this->images) - count($validImages) . ' image(s) were skipped because upload failed or temporary file expired.', type: 'warning');
        }

        $count = count($validImages);

        $this->processing = true;
        $this->results = [];
        $this->resizeProgress = 0;
        $this->processedImages = 0;
        $this->totalImages = $count;

        $this->streamProgress(0, "Preparing {$count} image(s)...");

        try {
            foreach ($validImages as $image) {
                $this->results[] = $this->resizeSingle($image);

                $this->processedImages++;
                $this->resizeProgress = (int) round(($this->processedImages / $this->totalImages) * 100);

                $this->streamProgress($this->resizeProgress, "Resized {$this->processedImages} of {$this->totalImages} image(s)");
            }

            $successfulCount = collect($this->results)->filter(fn($result) => ($result['status'] ?? null) === 'success')->count();

            if ($successfulCount > 0 && auth()->check()) {
                $usage = ToolUsage::query()->firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'tool_type' => 'image_resizer',
                        'period' => now()->format('Y-m-d'),
                    ],
                    ['usage_count' => 0],
                );

                $usage->increment('usage_count', $successfulCount);

                $this->dailyUsage += $successfulCount;
            }

            $this->streamProgress(100, 'Resizing completed.');

            if ($successfulCount > 0) {
                $this->dispatch('toast', message: $successfulCount . ' image(s) resized successfully!', type: 'success');
            } else {
                $this->dispatch('toast', message: 'Resizing failed. Please upload again and try fewer images.', type: 'error');
            }
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('toast', message: 'Resizing failed: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->processing = false;
        }
    }

    private function processableImages(): array
    {
        return collect($this->images)
            ->filter(function ($image) {
                if (!is_object($image)) {
                    return false;
                }

                if (!method_exists($image, 'getClientOriginalName') || !method_exists($image, 'getSize') || !method_exists($image, 'getRealPath')) {
                    return false;
                }

                $path = $image->getRealPath();

                return $path && is_file($path);
            })
            ->take($this->max_images)
            ->values()
            ->all();
    }

    private function streamProgress(int $progress, string $status): void
    {
        $progress = max(0, min(100, $progress));

        $bar = '<div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 transition-all duration-300" style="width: ' . $progress . '%"></div>';

        $this->stream(to: 'resize-progress', content: (string) $progress, replace: true);
        $this->stream(to: 'resize-status', content: e($status), replace: true);
        $this->stream(to: 'resize-bar', content: $bar, replace: true);
    }

    private function resizeSingle($image): array
    {
        try {
            $originalName = $image->getClientOriginalName();
            $originalSize = (int) $image->getSize();

            $ext = strtolower($image->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                throw new \RuntimeException('Unsupported image format: ' . $ext);
            }

            $sourcePath = $image->getRealPath();

            if (!$sourcePath || !is_file($sourcePath)) {
                throw new \RuntimeException('Uploaded temporary image file not found.');
            }

            [$origWidth, $origHeight, $origType] = getimagesize($sourcePath);

            if (!$origWidth || !$origHeight) {
                throw new \RuntimeException('Could not read image dimensions.');
            }

            if ($this->resizeUnit === '%') {
                $targetWidth = (int) round(($origWidth * max(1, min(200, $this->width))) / 100);
                $targetHeight = (int) round(($origHeight * max(1, min(200, $this->height))) / 100);
            } else {
                $targetWidth = max(1, $this->width);
                $targetHeight = max(1, $this->height);
            }

            if ($this->maintainAspectRatio) {
                $srcAspect = $origWidth / $origHeight;
                $dstAspect = $targetWidth / $targetHeight;

                if ($this->resizeMode === 'fit') {
                    if ($srcAspect > $dstAspect) {
                        $targetHeight = (int) round($targetWidth / $srcAspect);
                    } else {
                        $targetWidth = (int) round($targetHeight * $srcAspect);
                    }
                } elseif ($this->resizeMode === 'fill') {
                    if ($srcAspect > $dstAspect) {
                        $targetWidth = (int) round($targetHeight * $srcAspect);
                    } else {
                        $targetHeight = (int) round($targetWidth / $srcAspect);
                    }
                }
            }

            $outputFormat = $ext;

            $uniqueId = Str::random(20);
            $storagePath = 'temp/resizer/' . $uniqueId . '.' . $outputFormat;
            $outputFullPath = Storage::disk('public')->path($storagePath);

            Storage::disk('public')->makeDirectory('temp/resizer');

            $srcImage = $this->createImageFromSource($sourcePath, $origType);

            if (!$srcImage) {
                throw new \RuntimeException('Could not decode source image.');
            }

            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            if (!$dstImage) {
                throw new \RuntimeException('Could not create destination image.');
            }

            if ($origType === IMAGETYPE_PNG || $origType === IMAGETYPE_WEBP) {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
                imagefill($dstImage, 0, 0, $transparent);
            }

            if ($this->maintainAspectRatio && $this->resizeMode === 'fill') {
                $srcX = 0;
                $srcY = 0;
                $srcW = $origWidth;
                $srcH = $origHeight;

                $srcRatio = $origWidth / $origHeight;
                $dstRatio = $this->width / $this->height;

                if ($srcRatio > $dstRatio) {
                    $newSrcW = (int) round($origHeight * $dstRatio);
                    $srcX = (int) round(($origWidth - $newSrcW) / 2);
                    $srcW = $newSrcW;
                } else {
                    $newSrcH = (int) round($origWidth / $dstRatio);
                    $srcY = (int) round(($origHeight - $newSrcH) / 2);
                    $srcH = $newSrcH;
                }

                $dstW = $this->width;
                $dstH = $this->height;

                imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
            } else {
                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);
            }

            $saved = $this->saveImage($dstImage, $outputFullPath, $outputFormat);

            imagedestroy($dstImage);
            imagedestroy($srcImage);

            if (!$saved) {
                throw new \RuntimeException('Could not save resized image.');
            }

            clearstatcache(true, $outputFullPath);

            if (!is_file($outputFullPath)) {
                throw new \RuntimeException('Resized image was not saved.');
            }

            $resizedSize = (int) filesize($outputFullPath);

            $dimensions = $targetWidth . '×' . $targetHeight;

            $note = null;

            if ($origWidth === (int) $this->width && $origHeight === (int) $this->height) {
                $note = 'Image already matches target dimensions.';
            } elseif ($origWidth !== $targetWidth || $origHeight !== $targetHeight) {
                $note = 'Resized from ' . $origWidth . '×' . $origHeight . ' to ' . $dimensions . '.';
            }

            if ($this->is_premium_user) {
                $persistentPath = 'resized/users/' . auth()->id() . '/' . $uniqueId . '.' . $outputFormat;

                Storage::disk('public')->writeStream($persistentPath, Storage::disk('public')->readStream($storagePath));

                UserResizedImage::query()->create([
                    'user_id' => auth()->id(),
                    'tool_category_id' => $this->category->id,
                    'original_name' => $originalName,
                    'resized_path' => $persistentPath,
                    'resized_ext' => $outputFormat,
                    'original_size' => $originalSize,
                    'resized_size' => $resizedSize,
                    'expires_at' => now()->addMonth(),
                ]);
            }

            return [
                'original_name' => $originalName,
                'original_size' => $originalSize,
                'original_dimensions' => $origWidth . '×' . $origHeight,

                'resized_size' => $resizedSize,
                'resized_path' => $storagePath,
                'resized_ext' => $outputFormat,
                'resized_dimensions' => $dimensions,
                'resized_note' => $note,
                'backed_up' => $this->is_premium_user,

                'status' => 'success',
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'original_name' => $image->getClientOriginalName(),
                'original_size' => (int) $image->getSize(),
                'original_dimensions' => '',
                'resized_size' => 0,
                'resized_path' => '',
                'resized_ext' => strtolower($image->getClientOriginalExtension() ?: ''),
                'resized_dimensions' => '',
                'resized_note' => 'Resize failed: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    private function createImageFromSource(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };
    }

    private function saveImage(\GdImage $image, string $path, string $format): bool
    {
        return match ($format) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 92),
            'png' => imagepng($image, $path, 6),
            'webp' => imagewebp($image, $path, 85),
            default => false,
        };
    }

    public function download(int $index): mixed
    {
        $result = $this->results[$index] ?? null;

        if (!$result || empty($result['resized_path']) || !Storage::disk('public')->exists($result['resized_path'])) {
            return null;
        }

        $fileName = pathinfo($result['original_name'], PATHINFO_FILENAME);
        $extension = $result['resized_ext'] ?: pathinfo($result['original_name'], PATHINFO_EXTENSION);

        $downloadName = $fileName . '_resized.' . $extension;

        return Storage::disk('public')->download($result['resized_path'], $downloadName);
    }

    public function downloadAll(): mixed
    {
        $validResults = array_filter($this->results, fn($r) => !empty($r['resized_path']) && Storage::disk('public')->exists($r['resized_path']));

        if (empty($validResults)) {
            return null;
        }

        $zip = new ZipArchive();
        $zipPath = tempnam(sys_get_temp_dir(), 'resized_') . '.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return null;
        }

        foreach ($validResults as $result) {
            $fileName = pathinfo($result['original_name'], PATHINFO_FILENAME);
            $extension = $result['resized_ext'] ?: pathinfo($result['original_name'], PATHINFO_EXTENSION);
            $downloadName = $fileName . '_resized.' . $extension;
            $filePath = Storage::disk('public')->path($result['resized_path']);

            if (is_file($filePath)) {
                $zip->addFile($filePath, $downloadName);
            }
        }

        $zip->close();

        return response()->download($zipPath, 'resized_images.zip')->deleteFileAfterSend(true);
    }

    public function resetUpload(): void
    {
        $paths = collect($this->results)->flatMap(fn($r) => [$r['resized_path'] ?? null])->filter()->unique()->reject(fn($path) => str_starts_with($path, 'resized/users/'))->toArray();

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->reset(['images', 'results', 'processing', 'resizeProgress', 'processedImages', 'totalImages']);
    }

    public function formatBytes(int $bytes): string
    {
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
    <main class="mx-auto flex w-full max-w-7xl flex-col items-center px-4 pb-24 pt-10 sm:px-6 lg:px-8">

        {{-- Hero Header --}}
        <div class="mb-10 text-center">
            <h1 class="text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl">
                Resize your
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text italic text-transparent">images</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100/60 sm:text-lg">
                Resize images to exact dimensions while keeping quality. Maintain aspect ratio or crop to fit.
            </p>
        </div>

        {{-- Progress Stepper --}}
        <div class="mb-12 flex flex-wrap items-center justify-center gap-3 sm:gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-cyan-300/40 bg-cyan-400/15 text-sm font-bold text-cyan-200 shadow-lg shadow-cyan-500/20">
                    1
                </div>
                <span class="text-xs font-bold tracking-[0.22em] text-white">UPLOAD</span>
            </div>
            <div class="hidden h-px w-10 bg-white/15 sm:block"></div>
            <div class="flex items-center gap-3 opacity-70">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-white/15 bg-white/5 text-sm font-bold text-blue-100/60">
                    2
                </div>
                <span class="text-xs font-bold tracking-[0.22em] text-blue-100/50">CONFIGURE</span>
            </div>
            <div class="hidden h-px w-10 bg-white/15 sm:block"></div>
            <div class="flex items-center gap-3 opacity-70">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-white/15 bg-white/5 text-sm font-bold text-blue-100/60">
                    3
                </div>
                <span class="text-xs font-bold tracking-[0.22em] text-blue-100/50">RESIZE</span>
            </div>
        </div>

        {{-- Resize Progress Overlay --}}
        <div wire:loading.flex wire:target="resize"
            class="fixed inset-0 z-[9999] items-center justify-center bg-slate-950/75 px-4 backdrop-blur-md">
            <div
                class="w-full max-w-md rounded-3xl border border-cyan-400/20 bg-slate-900/90 p-6 text-center shadow-2xl shadow-cyan-500/20">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-cyan-400/10 text-cyan-300">
                    <span
                        class="h-8 w-8 animate-spin rounded-full border-2 border-cyan-100/30 border-t-cyan-100"></span>
                </div>
                <h3 class="mt-5 text-xl font-bold text-white">Resizing images...</h3>
                <p class="mt-2 text-sm text-blue-100/60">
                    <span wire:stream="resize-status">Preparing images...</span>
                </p>
                <div class="mt-6">
                    <div class="mb-2 flex items-center justify-between text-xs text-blue-100/60">
                        <span>Progress</span>
                        <span><span wire:stream="resize-progress">0</span>%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-white/10">
                        <span wire:stream="resize-bar">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 transition-all duration-300"
                                style="width: 0%"></div>
                        </span>
                    </div>
                </div>
                <p class="mt-4 text-xs leading-5 text-blue-100/45">
                    Please keep this page open while your images are being processed.
                </p>
            </div>
        </div>

        <div class="w-full">
            @if (!empty($results))
                @php
                    $successfulResults = collect($results)->filter(fn($r) => ($r['status'] ?? null) === 'success');
                    $failedResults = collect($results)->filter(fn($r) => ($r['status'] ?? null) === 'error');
                    $totalOriginalSize = $successfulResults->sum('original_size');
                    $totalResizedSize = $successfulResults->sum('resized_size');
                @endphp

                {{-- Results View --}}
                <section
                    class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.06] p-6 shadow-[0_30px_100px_rgba(0,0,0,0.35)] backdrop-blur-2xl sm:p-8">
                    <div
                        class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                    </div>

                    <div class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-extrabold text-white">Resizing complete</h2>
                            <p class="mt-1 text-sm text-blue-100/55">
                                {{ $successfulResults->count() }}
                                image{{ $successfulResults->count() !== 1 ? 's' : '' }} resized
                                &middot; {{ $this->formatBytes($totalOriginalSize) }} →
                                {{ $this->formatBytes($totalResizedSize) }}
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button" wire:click="resetUpload"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/8 px-4 py-2 text-sm font-semibold text-white transition hover:border-cyan-400/30 hover:bg-white/12">
                                <span class="material-symbols-outlined text-base">refresh</span>
                                New batch
                            </button>

                            @if ($successfulResults->count() === 1)
                                @php $singleResultIndex = collect($results)->search(fn($r) => ($r['status'] ?? null) === 'success'); @endphp
                                <button type="button" wire:click="download({{ $singleResultIndex }})"
                                    wire:loading.attr="disabled"
                                    class="group relative inline-flex items-center gap-1.5 overflow-hidden rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:opacity-60">
                                    <span
                                        class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                                    <span class="material-symbols-outlined relative text-base">download</span>
                                    <span class="relative">Download</span>
                                </button>
                            @elseif ($successfulResults->count() > 1)
                                <button type="button" wire:click="downloadAll" wire:loading.attr="disabled"
                                    class="group relative inline-flex items-center gap-1.5 overflow-hidden rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 disabled:opacity-60">
                                    <span
                                        class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                                    <span class="material-symbols-outlined relative text-base">folder_zip</span>
                                    <span class="relative">Download all</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($failedResults->count() > 0)
                        <div
                            class="relative mb-4 flex items-center gap-2 rounded-xl border border-red-400/15 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            <span class="material-symbols-outlined text-base text-red-300">error</span>
                            {{ $failedResults->count() }} image{{ $failedResults->count() !== 1 ? 's' : '' }} failed to
                            resize. Please try again.
                        </div>
                    @endif

                    <div class="relative space-y-2">
                        @foreach ($results as $index => $result)
                            @php $success = ($result['status'] ?? null) === 'success'; @endphp

                            <div
                                class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-950/25 px-4 py-3 transition hover:border-cyan-400/25 hover:bg-white/[0.08]">
                                <div @class([
                                    'h-2.5 w-2.5 shrink-0 rounded-full shadow-lg',
                                    'bg-emerald-400 shadow-emerald-400/25' => $success,
                                    'bg-red-400 shadow-red-400/25' => !$success,
                                ])></div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">{{ $result['original_name'] }}
                                    </p>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-blue-100/50">
                                        @if ($success)
                                            <span>{{ $this->formatBytes($result['original_size']) }}</span>
                                            <span class="text-blue-100/30">→</span>
                                            <span
                                                class="font-semibold text-cyan-300">{{ $this->formatBytes($result['resized_size']) }}</span>
                                            <span
                                                class="rounded-md border border-cyan-300/15 bg-cyan-400/10 px-1.5 py-0.5 font-medium text-cyan-300">{{ $result['resized_dimensions'] }}</span>
                                            @if (!empty($result['resized_ext']))
                                                <span
                                                    class="rounded-md border border-white/10 bg-white/8 px-1.5 py-0.5 uppercase">{{ $result['resized_ext'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-red-300/70">Resize failed</span>
                                        @endif
                                        @if (!empty($result['resized_note']))
                                            <span class="text-amber-300/70">· {{ $result['resized_note'] }}</span>
                                        @endif
                                        @if (!empty($result['backed_up']))
                                            <span class="text-cyan-300/70">· <span
                                                    class="material-symbols-outlined text-[11px] leading-none align-middle">backup</span>
                                                Backed up</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($success)
                                    <button type="button" wire:click="download({{ $index }})"
                                        class="shrink-0 rounded-lg border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:border-cyan-400/30 hover:bg-cyan-400/10 hover:text-cyan-200">
                                        <span class="material-symbols-outlined text-sm leading-none">download</span>
                                    </button>
                                @else
                                    <span
                                        class="shrink-0 rounded-lg bg-red-500/15 px-3 py-1.5 text-xs text-red-300">Failed</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                {{-- Upload + Settings View --}}
                <div class="grid w-full grid-cols-1 gap-8 lg:grid-cols-12">
                    {{-- Upload Section --}}
                    <section
                        class="relative flex min-h-[560px] flex-col rounded-2xl border border-white/10 bg-white/[0.06] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl lg:col-span-8 sm:p-8">
                        <div
                            class="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-br from-cyan-400/5 via-transparent to-blue-500/5">
                        </div>

                        {{-- Upload loading overlay --}}
                        <div wire:loading.flex wire:target="images"
                            class="absolute inset-0 z-30 items-center justify-center rounded-2xl bg-slate-950/90 backdrop-blur-md">
                            <div class="flex flex-col items-center gap-4 text-center">
                                <span
                                    class="h-10 w-10 animate-spin rounded-full border-2 border-cyan-100/30 border-t-cyan-300"></span>
                                <div>
                                    <p class="font-semibold text-white">Uploading images...</p>
                                    <p class="mt-1 text-sm text-blue-100/50">Please wait a moment.</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative mb-6 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-extrabold text-white">Files to Process</h2>
                                <p class="mt-1 text-sm text-blue-100/45">
                                    {{ empty($images) ? 'No images selected yet.' : count($images) . ' image' . (count($images) > 1 ? 's' : '') . ' ready to resize.' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full border border-white/10 bg-slate-950/30 px-3 py-1 text-xs font-semibold text-blue-100/55">
                                    {{ count($images) }} / {{ $this->max_images }} images
                                </span>

                                @if (!empty($images))
                                    <label for="resizer-image-upload"
                                        class="hidden cursor-pointer items-center gap-1.5 rounded-xl border border-white/10 bg-white/8 px-3 py-1.5 text-xs font-semibold text-white transition hover:border-cyan-400/30 hover:bg-white/12 sm:inline-flex">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        Add more
                                    </label>
                                @endif
                            </div>
                        </div>

                        @if (empty($images))
                            <label for="resizer-image-upload"
                                class="group relative flex flex-1 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-cyan-300/25 bg-slate-950/25 p-8 text-center transition hover:border-cyan-300/50 hover:bg-cyan-400/5 sm:p-12">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full border border-cyan-300/15 bg-cyan-400/10 text-cyan-300 transition group-hover:scale-110">
                                    <span class="material-symbols-outlined text-4xl">cloud_upload</span>
                                </div>
                                <h3 class="mt-6 text-2xl font-extrabold text-white">Drop images here</h3>
                                <p class="mt-2 text-sm text-blue-100/55">Supports JPG, PNG, and WebP up to 10MB each.
                                </p>

                                <div
                                    class="mt-8 inline-flex items-center gap-2 rounded-xl border border-cyan-300/30 bg-cyan-400/10 px-8 py-3 font-bold text-cyan-200 transition hover:bg-cyan-400/15">
                                    <span class="material-symbols-outlined text-xl">add_photo_alternate</span>
                                    Browse Files
                                </div>

                                <div class="mt-6 flex flex-wrap justify-center gap-2">
                                    @foreach (['JPG', 'PNG', 'WebP'] as $fmt)
                                        <span
                                            class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[11px] font-medium text-blue-100/60">{{ $fmt }}</span>
                                    @endforeach
                                    <span
                                        class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[11px] text-blue-100/60">Max
                                        10MB</span>
                                    <span
                                        class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[11px] text-blue-100/60">Up
                                        to {{ $this->max_images }} files</span>
                                </div>
                            </label>
                        @else
                            <div
                                class="relative grid max-h-[520px] gap-3 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($images as $index => $img)
                                    <div wire:key="selected-image-{{ $index }}-{{ md5($img->getClientOriginalName() . $img->getSize()) }}"
                                        class="group relative overflow-hidden rounded-xl border border-white/10 bg-slate-950/25 transition hover:border-cyan-400/30 hover:bg-white/[0.04]">
                                        <button type="button" wire:click="removeImage({{ $index }})"
                                            class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white/70 opacity-0 backdrop-blur-sm transition hover:bg-red-500/70 hover:text-white group-hover:opacity-100"
                                            title="Remove">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                        <div
                                            class="flex h-36 items-center justify-center overflow-hidden bg-white/[0.03]">
                                            <img src="{{ $img->temporaryUrl() }}" alt="Preview"
                                                class="h-full w-full object-contain p-2" />
                                        </div>
                                        <div class="px-3 py-2.5">
                                            <p class="truncate text-xs font-semibold text-white"
                                                title="{{ $img->getClientOriginalName() }}">
                                                {{ $img->getClientOriginalName() }}
                                            </p>
                                            <div class="mt-1.5 flex items-center gap-1.5">
                                                <span
                                                    class="rounded bg-white/8 px-1.5 py-0.5 text-[10px] font-medium uppercase text-blue-100/55">
                                                    {{ $img->extension() }}
                                                </span>
                                                <span class="text-[11px] text-blue-100/45">
                                                    {{ $this->formatBytes((int) $img->getSize()) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <input id="resizer-image-upload" type="file" wire:model="images" multiple
                            accept="image/png,image/jpeg,image/jpg,image.webp" class="hidden" />
                    </section>

                    {{-- Settings Sidebar --}}
                    <aside class="flex flex-col gap-6 lg:col-span-4">
                        <div
                            class="flex h-full flex-col rounded-2xl border border-white/10 bg-white/[0.06] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur-2xl sm:p-8 lg:sticky lg:top-24">
                            <div class="mb-8 flex items-center gap-4">
                                <div class="rounded-xl border border-cyan-300/15 bg-cyan-400/10 p-3 text-cyan-300">
                                    <span class="material-symbols-outlined">tune</span>
                                </div>
                                <div>
                                    <h2 class="text-lg font-extrabold leading-none text-white">Resize Strategy</h2>
                                    <p class="mt-1 text-xs text-blue-100/45">Tailor dimensions to your needs.</p>
                                </div>
                            </div>

                            <label for="resizer-image-upload"
                                class="mb-6 flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-cyan-300/25 bg-cyan-400/10 px-5 py-3 text-sm font-bold text-cyan-200 transition hover:border-cyan-300/40 hover:bg-cyan-400/15">
                                <span class="material-symbols-outlined text-lg">add_photo_alternate</span>
                                {{ empty($images) ? 'Choose Images' : 'Add More Images' }}
                            </label>

                            {{-- Resize Unit Toggle --}}
                            <div class="mb-8">
                                <label
                                    class="mb-4 block text-xs font-bold uppercase tracking-[0.22em] text-blue-100/45">Resize
                                    Mode</label>
                                <div
                                    class="grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-slate-950/30 p-1">
                                    <button type="button"
                                        wire:click="$set('resizeUnit', 'px'); $set('width', 800); $set('height', 600)"
                                        @class([
                                            'flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold transition',
                                            'border border-cyan-400/30 bg-white/10 text-white shadow-lg shadow-cyan-500/10' =>
                                                $resizeUnit === 'px',
                                            'text-blue-100/55 hover:bg-white/8 hover:text-white' =>
                                                $resizeUnit !== 'px',
                                        ])>
                                        <span class="material-symbols-outlined text-base">straighten</span>
                                        Exact Pixels
                                    </button>
                                    <button type="button"
                                        wire:click="$set('resizeUnit', '%'); $set('width', 100); $set('height', 100)"
                                        @class([
                                            'flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold transition',
                                            'border border-cyan-400/30 bg-white/10 text-white shadow-lg shadow-cyan-500/10' =>
                                                $resizeUnit === '%',
                                            'text-blue-100/55 hover:bg-white/8 hover:text-white' => $resizeUnit !== '%',
                                        ])>
                                        <span class="text-lg leading-none">%</span>
                                        By Percent
                                    </button>
                                </div>
                            </div>

                            <div class="flex-grow space-y-6" x-data="{ updating: false, baseRatio: null }">
                                {{-- Pixel Inputs --}}
                                <div class="space-y-5" x-show="$wire.resizeUnit === 'px'">
                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-bold uppercase tracking-[0.22em] text-blue-100/45">Width
                                            (px)</label>
                                        <input type="number" wire:model.live.debounce.300ms="width" min="1"
                                            max="10000" x-ref="width"
                                            x-on:focus="baseRatio = (parseInt($refs.width.value) || 1) / (parseInt($refs.height.value) || 1)"
                                            x-on:input="
                                                if (updating) return;
                                                if ($wire.maintainAspectRatio && baseRatio) {
                                                    updating = true;
                                                    $wire.set('height', Math.round((parseInt($refs.width.value) || 1) / baseRatio)).then(() => { updating = false; });
                                                }
                                            "
                                            class="w-full rounded-xl border border-white/10 bg-slate-950/30 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/30 focus:border-cyan-400/50 focus:ring-1 focus:ring-cyan-400/30" />
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-bold uppercase tracking-[0.22em] text-blue-100/45">Height
                                            (px)</label>
                                        <input type="number" wire:model.live.debounce.300ms="height" min="1"
                                            max="10000" x-ref="height"
                                            x-on:focus="baseRatio = (parseInt($refs.width.value) || 1) / (parseInt($refs.height.value) || 1)"
                                            x-on:input="
                                                if (updating) return;
                                                if ($wire.maintainAspectRatio && baseRatio) {
                                                    updating = true;
                                                    $wire.set('width', Math.round((parseInt($refs.height.value) || 1) * baseRatio)).then(() => { updating = false; });
                                                }
                                            "
                                            class="w-full rounded-xl border border-white/10 bg-slate-950/30 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/30 focus:border-cyan-400/50 focus:ring-1 focus:ring-cyan-400/30" />
                                    </div>
                                </div>

                                {{-- Percent Slider --}}
                                <div class="space-y-3" x-show="$wire.resizeUnit === '%'">
                                    <div>
                                        <div class="mb-3 flex items-center justify-between">
                                            <label
                                                class="text-xs font-bold uppercase tracking-[0.22em] text-blue-100/45">Scale</label>
                                            <span class="text-lg font-extrabold text-cyan-300"
                                                x-text="$wire.width + '%'"></span>
                                        </div>
                                        <input type="range" wire:model.live.debounce.300ms="width" min="1"
                                            max="200" step="1"
                                            x-on:input="$wire.set('height', parseInt($el.value))"
                                            class="h-2 w-full cursor-pointer appearance-none rounded-full bg-white/10 outline-none"
                                            style="background: linear-gradient(to right, rgb(34 211 238) 0%, rgb(59 130 246) 100%); height: 8px; -webkit-appearance: none; border-radius: 4px;" />
                                        <div class="mt-2 flex justify-between text-[11px] text-blue-100/40">
                                            <span>1%</span>
                                            <span>100%</span>
                                            <span>200%</span>
                                        </div>
                                    </div>
                                </div>

                                @if ($resizeUnit === 'px')
                                    {{-- Maintain Aspect Ratio --}}
                                    <label
                                        class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-white/10 bg-slate-950/30 p-4 transition hover:border-cyan-400/25 hover:bg-cyan-400/5">
                                        <span class="flex items-center gap-3">
                                            <span class="text-cyan-300">
                                                <span class="material-symbols-outlined text-xl">aspect_ratio</span>
                                            </span>
                                            <span>
                                                <span class="block text-sm font-bold text-white">Maintain aspect
                                                    ratio</span>
                                                <span class="mt-0.5 block text-[11px] text-blue-100/45">Lock
                                                    width/height proportions</span>
                                            </span>
                                        </span>

                                        <span class="relative shrink-0">
                                            <input type="checkbox" wire:model.live="maintainAspectRatio"
                                                class="peer sr-only" />
                                            <span
                                                class="block h-6 w-11 rounded-full bg-white/10 transition peer-checked:bg-cyan-400/40"></span>
                                            <span
                                                class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-lg shadow-black/20 transition peer-checked:translate-x-5 peer-checked:bg-cyan-100"></span>
                                        </span>
                                    </label>

                                    @if ($maintainAspectRatio)
                                        <div class="space-y-3">
                                            <label
                                                class="block text-xs font-bold uppercase tracking-[0.22em] text-blue-100/45">Fit
                                                Mode</label>
                                            <div
                                                class="grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-slate-950/30 p-1">
                                                <button type="button" wire:click="$set('resizeMode', 'fit')"
                                                    @class([
                                                        'flex flex-col items-center justify-center rounded-lg px-3 py-2.5 text-xs font-bold transition',
                                                        'border border-cyan-400/30 bg-white/10 text-white' => $resizeMode === 'fit',
                                                        'text-blue-100/55 hover:bg-white/8 hover:text-white' =>
                                                            $resizeMode !== 'fit',
                                                    ])>
                                                    <span class="material-symbols-outlined text-base">fit_screen</span>
                                                    Fit
                                                </button>
                                                <button type="button" wire:click="$set('resizeMode', 'fill')"
                                                    @class([
                                                        'flex flex-col items-center justify-center rounded-lg px-3 py-2.5 text-xs font-bold transition',
                                                        'border border-cyan-400/30 bg-white/10 text-white' =>
                                                            $resizeMode === 'fill',
                                                        'text-blue-100/55 hover:bg-white/8 hover:text-white' =>
                                                            $resizeMode !== 'fill',
                                                    ])>
                                                    <span class="material-symbols-outlined text-base">crop</span>
                                                    Fill & Crop
                                                </button>
                                            </div>
                                            <p class="text-[11px] leading-5 text-blue-100/40">
                                                @if ($resizeMode === 'fit')
                                                    Scales to fit within the target dimensions. No cropping.
                                                @else
                                                    Fills the target dimensions exactly. May crop edges.
                                                @endif
                                            </p>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            @if (!empty($images))
                                <button type="button" wire:click="resize" wire:loading.attr="disabled"
                                    class="group relative mt-8 flex w-full items-center justify-center gap-3 overflow-hidden rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-4 font-extrabold text-white shadow-lg shadow-cyan-500/25 transition hover:-translate-y-0.5 hover:shadow-[0_0_25px_rgba(34,211,238,0.35)] active:translate-y-0 disabled:opacity-60">
                                    <span
                                        class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                                    <span wire:loading.remove wire:target="resize"
                                        class="relative flex items-center gap-2">
                                        <span class="material-symbols-outlined">photo_size_select_large</span>
                                        Resize {{ count($images) }} Image{{ count($images) > 1 ? 's' : '' }}
                                    </span>
                                    <span wire:loading wire:target="resize" class="relative flex items-center gap-2">
                                        <span
                                            class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        Resizing...
                                    </span>
                                </button>
                            @else
                                <button type="button" disabled
                                    class="mt-8 flex w-full cursor-not-allowed items-center justify-center gap-3 rounded-xl bg-white/8 px-6 py-4 font-extrabold text-white/30">
                                    <span class="material-symbols-outlined">photo_size_select_large</span>
                                    Resize 0 Images
                                </button>
                            @endif
                        </div>
                    </aside>
                </div>

                {{-- Mobile Fixed Bottom Bar --}}
                <div
                    class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-slate-950/90 backdrop-blur-xl lg:hidden">
                    <div class="mx-auto max-w-3xl px-4 py-3">
                        @if (!empty($images))
                            <div class="flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-white">
                                        {{ count($images) }} image{{ count($images) > 1 ? 's' : '' }} selected
                                    </p>
                                    <p class="text-xs text-blue-100/45">
                                        @if ($resizeUnit === '%')
                                            {{ $width }}%
                                        @else
                                            {{ $width }}×{{ $height }}px
                                            @if ($maintainAspectRatio)
                                                · {{ $resizeMode === 'fit' ? 'Fit' : 'Fill' }}
                                            @endif
                                        @endif
                                    </p>
                                </div>

                                <label for="resizer-image-upload"
                                    class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-white/15 bg-white/8 text-white transition hover:bg-white/12">
                                    <span class="material-symbols-outlined text-lg">add</span>
                                </label>

                                <button type="button" wire:click="resize" wire:loading.attr="disabled"
                                    class="group relative flex shrink-0 items-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/25 transition active:scale-95 disabled:opacity-60">
                                    <span
                                        class="absolute inset-y-0 -left-1/2 w-1/2 skew-x-[-20deg] bg-white/20 transition-all duration-700 group-hover:left-full"></span>
                                    <span wire:loading.remove wire:target="resize"
                                        class="relative flex items-center gap-1.5">
                                        <span
                                            class="material-symbols-outlined text-base">photo_size_select_large</span>
                                        Resize
                                    </span>
                                    <span wire:loading wire:target="resize"
                                        class="relative flex items-center gap-1.5">
                                        <span
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        Working...
                                    </span>
                                </button>
                            </div>
                        @else
                            <label for="resizer-image-upload"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-500/25">
                                <span class="material-symbols-outlined text-base">add_photo_alternate</span>
                                Choose Images to Resize
                            </label>
                        @endif
                    </div>
                </div>

                <div class="h-20 lg:hidden"></div>
            @endif
        </div>

        {{-- How It Works --}}
        <div class="mt-12 grid w-full gap-6 sm:grid-cols-3">
            <div
                class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500/15 text-cyan-300">
                    <span class="material-symbols-outlined">upload</span>
                </div>
                <h3 class="mt-4 font-semibold text-white">1. Upload</h3>
                <p class="mt-2 text-sm text-blue-100/62">
                    Select JPG, PNG, or WebP images — up to {{ $this->max_images }} at a time.
                </p>
            </div>

            <div
                class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/15 text-blue-300">
                    <span class="material-symbols-outlined">photo_size_select_large</span>
                </div>
                <h3 class="mt-4 font-semibold text-white">2. Set Size</h3>
                <p class="mt-2 text-sm text-blue-100/62">
                    Choose width, height, and mode — fit within or crop to fill.
                </p>
            </div>

            <div
                class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 text-center shadow-[0_20px_60px_rgba(0,0,0,0.18)] backdrop-blur-xl">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                    <span class="material-symbols-outlined">download</span>
                </div>
                <h3 class="mt-4 font-semibold text-white">3. Download</h3>
                <p class="mt-2 text-sm text-blue-100/62">
                    Download individually or grab all resized images as a ZIP.
                </p>
            </div>
        </div>
    </main>
</div>
