<?php

use App\Models\SiteSetting;
use App\Support\QrCode\DesignGenerator;
use App\Support\QrCode\FrameRenderer;
use App\Support\QrCode\LogoPresets;
use App\Support\QrCode\LogoOptimizer;
use App\Support\QrCode\PreviewStore;
use App\Support\QrCode\ScanTracker;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('QR Code Generator')] class extends Component {
    use WithFileUploads;

    public string $activeTab = 'frame';

    public string $input = '';
    public string $inputType = 'url';

    public string $wifiSsid = '';
    public string $wifiPassword = '';
    public string $wifiEncryption = 'WPA';
    public bool $wifiHidden = false;

    public string $email = '';
    public string $emailSubject = '';
    public string $emailBody = '';
    public string $phone = '';

    public string $foregroundColor = '#111827';
    public string $backgroundColor = '#ffffff';

    public string $gradientType = 'none';
    public string $gradientStart = '#06b6d4';
    public string $gradientEnd = '#2563eb';

    public string $framePreset = 'scan-bottom';
    public string $frameColor = '#111827';
    public string $frameText = 'SCAN ME';
    public string $frameTextColor = '#ffffff';
    public string $frameFont = 'sans';

    public string $moduleStyle = 'square';
    public string $eyeStyle = 'square';
    public string $eyeCenterStyle = 'square';

    public array $eyeInnerColors = ['#111827', '#111827', '#111827'];
    public array $eyeOuterColors = ['#111827', '#111827', '#111827'];

    public ?string $centerLogo = 'site';
    public ?string $presetLogo = null;
    public $customLogo = null;

    #[Locked]
    public string $previewToken = '';

    #[Locked]
    public ?string $previewVersion = null;

    #[Locked]
    public ?string $generatedQr = null;

    public int $uiRevision = 0;
    public ?string $generatedFormat = null;

    public int $size = 420;
    public int $margin = 4;

    public function mount(): void
    {
        $this->previewToken = Str::random(40);
        $siteSetting = SiteSetting::current();

        $this->centerLogo = filled($siteSetting->logo) ? 'site' : null;

        $this->input = '';
        $this->generatedQr = null;
        $this->generatedFormat = null;
    }

    private ?bool $premiumCache = null;
    private bool $siteLogoResolved = false;
    private ?array $siteLogoData = null;

    public function getIsPremiumProperty(): bool
    {
        // Resolved once per request: the blade calls this on every tab/button,
        // and each call used to hit the database twice.
        if ($this->premiumCache !== null) {
            return $this->premiumCache;
        }

        if (!auth()->check()) {
            return $this->premiumCache = false;
        }

        return $this->premiumCache = app(ScanTracker::class)->premium(auth()->user());
    }

    public function getPresetIconsProperty(): array
    {
        return LogoPresets::all();
    }

    private function buildQrData(): ?string
    {
        return match ($this->inputType) {
            'url' => $this->normalizeUrl($this->input),

            'wifi' => filled($this->wifiSsid) ? sprintf('WIFI:T:%s;S:%s;P:%s;H:%s;', $this->wifiEncryption, $this->wifiEscape($this->wifiSsid), $this->wifiEscape($this->wifiPassword), $this->wifiHidden ? 'true' : 'false') : null,

            'email' => filter_var(trim($this->email), FILTER_VALIDATE_EMAIL) ? sprintf('mailto:%s?subject=%s&body=%s', trim($this->email), rawurlencode($this->emailSubject), rawurlencode($this->emailBody)) : null,

            'phone' => strlen(preg_replace('/[^0-9+]/', '', $this->phone)) >= 6 ? 'tel:' . preg_replace('/[^0-9+]/', '', $this->phone) : null,

            default => filled(trim($this->input)) ? trim($this->input) : null,
        };
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);

        if (!filled($url)) {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!$host || !str_contains($host, '.')) {
            return null;
        }

        return $url;
    }

    private function wifiEscape(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            ';' => '\;',
            ',' => '\,',
            ':' => '\:',
            '"' => '\"',
        ]);
    }

    public function generate(): void
    {
        $this->refreshPreview(showErrors: true);
    }

    public function updated(mixed $property): void
    {
        if ($property === 'customLogo' || $property === 'activeTab' || $property === 'uiRevision') {
            return;
        }

        if ($property === 'framePreset') {
            $plainCaption = in_array($this->framePreset, ['minimal-top', 'minimal-bottom'], true);
            $captionBackground = $plainCaption ? $this->backgroundColor : $this->frameColor;
            if (strcasecmp($this->frameTextColor, $captionBackground) === 0) {
                [$red, $green, $blue] = $this->hexToRgb($captionBackground);
                $this->frameTextColor = ($red * 299 + $green * 587 + $blue * 114) / 1000 > 128 ? '#111827' : '#ffffff';
            }
        }

        // The shared color picker applies the same color to all three finder eyes.
        if ($property === 'eyeOuterColors.0') {
            $this->eyeOuterColors = array_fill(0, 3, $this->eyeOuterColors[0]);
        } elseif ($property === 'eyeInnerColors.0') {
            $this->eyeInnerColors = array_fill(0, 3, $this->eyeInnerColors[0]);
        }

        $this->refreshPreview(showErrors: false);
    }

    private function refreshPreview(bool $showErrors = false): void
    {
        $data = $this->buildQrData();
        if (! filled($data)) {
            $this->generatedQr = null;
            $this->previewVersion = null;
            $this->generatedFormat = null;
            app(PreviewStore::class)->forgetPreview($this->previewToken);
            if ($showErrors) {
                $this->addError('input', $this->contentErrorMessage());
            } else {
                $this->resetErrorBag('input');
            }
            $this->previewUpdated();
            return;
        }
        try {
            $svg = $this->buildQrSvg($data);
            $preview = app(PreviewStore::class)->store($this->previewToken, $svg);
            $this->generatedQr = $preview['url'];
            $this->previewVersion = $preview['version'];
            $this->generatedFormat = 'svg';
            $this->resetErrorBag();
            $this->previewUpdated();
        } catch (ValidationException $e) {
            $this->generatedQr = null;
            $this->previewVersion = null;
            $this->generatedFormat = null;
            $this->setErrorBag($e->errors());
            $this->previewUpdated('QR generation is unavailable.');
        } catch (\Throwable $e) {
            report($e);
            $this->generatedQr = null;
            $this->previewVersion = null;
            $this->generatedFormat = null;
            $this->addError('input', 'QR generation failed. Please check your input and design settings.');
            $this->previewUpdated('QR generation failed.');
        }
    }

    private function previewUpdated(?string $error = null): void
    {
        $this->dispatch('qr-preview-updated',
            id: $this->getId(), revision: $this->uiRevision, url: $this->generatedQr,
            choices: $this->uiChoices(), error: $error,
        );
    }

    public function uiChoices(): array
    {
        return [
            'framePreset' => $this->framePreset, 'moduleStyle' => $this->moduleStyle,
            'eyeStyle' => $this->eyeStyle, 'eyeCenterStyle' => $this->eyeCenterStyle,
            'logo' => $this->centerLogo === 'preset' ? 'preset:'.$this->presetLogo : ($this->centerLogo ?? 'none'),
        ];
    }

    private function contentErrorMessage(): string
    {
        return match ($this->inputType) {
            'url' => 'Please enter a valid URL before generating QR code.',
            'wifi' => 'Please enter WiFi network name before generating QR code.',
            'email' => 'Please enter a valid email address before generating QR code.',
            'phone' => 'Please enter a valid phone number before generating QR code.',
            default => 'Please enter some text before generating QR code.',
        };
    }

    private function buildQrSvg(string $data): string
    {
        [$fr, $fg, $fb] = $this->hexToRgb($this->foregroundColor);
        [$br, $bg, $bb] = $this->hexToRgb($this->backgroundColor);

        $premium = $this->isPremium;
        if (! $premium) {
            if (! $this->resolveSiteLogo()) {
                throw ValidationException::withMessages(['input' => 'Free QR codes require a valid site logo. Please contact the site administrator to restore it.']);
            }
        }
        $encodedData = app(ScanTracker::class)->link(
            $this->previewToken, $this->inputType, $data, auth()->id(),
        );
        $qr = (new DesignGenerator)
            ->format('svg')
            ->size(max(200, min(900, $this->size)))
            ->margin(max(4, min(10, $this->margin)))
            ->errorCorrection('H')
            ->design(
                $premium ? $this->moduleStyle : 'square',
                $premium ? $this->eyeStyle : 'square',
                $premium ? $this->eyeCenterStyle : 'square',
            )
            ->color($fr, $fg, $fb)
            ->backgroundColor($br, $bg, $bb);

        if ($this->isPremium && $this->gradientType !== 'none') {
            [$gr, $gg, $gb] = $this->hexToRgb($this->gradientStart);
            [$ger, $geg, $geb] = $this->hexToRgb($this->gradientEnd);

            $qr->gradient($gr, $gg, $gb, $ger, $geg, $geb, $this->gradientType);
        }

        if ($this->isPremium) {
            foreach (range(0, 2) as $i) {
                [$ir, $ig, $ib] = $this->hexToRgb($this->eyeInnerColors[$i] ?? $this->foregroundColor);
                [$or, $og, $ob] = $this->hexToRgb($this->eyeOuterColors[$i] ?? $this->foregroundColor);

                $qr->eyeColor($i, $ir, $ig, $ib, $or, $og, $ob);
            }
        }

        $svg = (string) $qr->generate($encodedData);

        $effectiveLogo = $this->isPremium ? $this->centerLogo : 'site';

        if ($effectiveLogo === 'site') {
            $svg = $this->embedLogoSvg($svg);
        } elseif ($effectiveLogo === 'preset' && $this->isPremium && $this->presetLogo) {
            $svg = $this->embedPresetLogoSvg($svg, $this->presetLogo);
        } elseif ($effectiveLogo === 'custom' && $this->isPremium && $this->customLogo) {
            $svg = $this->embedCustomLogoSvg($svg);
        }

        if ($this->framePreset !== 'none') {
            return $this->wrapWithFrame($svg);
        }

        return $svg;
    }

    private function resolveSiteLogo(): ?array
    {
        if ($this->siteLogoResolved) {
            return $this->siteLogoData;
        }
        $this->siteLogoResolved = true;
        $siteSetting = SiteSetting::current();
        if (! $siteSetting->logo || ! Storage::disk('public')->exists($siteSetting->logo)) {
            return null;
        }
        $contents = Storage::disk('public')->get($siteSetting->logo);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'image/png';
        try {
            app(LogoOptimizer::class)->normalize($contents, $mime);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return $this->siteLogoData = ['data' => base64_encode($contents), 'mime' => $mime];
    }

    private function embedLogoSvg(string $svg): string
    {
        $logo = $this->resolveSiteLogo();
        if (! $logo) {
            if (! $this->isPremium) {
                throw ValidationException::withMessages(['input' => 'Free QR codes require a valid site logo. Please contact the site administrator to restore it.']);
            }

            return $svg;
        }

        return $this->insertCenterImage($svg, $logo['data'], $logo['mime']);
    }

    private function embedPresetLogoSvg(string $svg, string $preset): string
    {
        $icons = $this->presetIcons;
        $icon = $icons[$preset] ?? null;

        if (!$icon) {
            return $svg;
        }

        return $this->insertCenterImage($svg, base64_encode($icon['svg']), 'image/svg+xml');
    }

    private function embedCustomLogoSvg(string $svg): string
    {
        if (! $this->customLogo) {
            return $svg;
        }
        $store = app(PreviewStore::class);
        $logo = $store->logo($this->previewToken);
        if (! $logo) {
            $logo = app(LogoOptimizer::class)->normalize((string) $this->customLogo->get(), $this->customLogo->getMimeType());
            $store->putLogo($this->previewToken, $logo);
        }

        return $this->insertCenterImage($svg, base64_encode($logo['contents']), $logo['mime']);
    }

    private function insertCenterImage(string $svg, string $base64Data, string $mime): string
    {
        [$x0, $y0, $qrWidth, $qrHeight] = $this->svgViewBox($svg);

        $logoSize = (int) round(min($qrWidth, $qrHeight) * 0.21);
        $x = max(0, (int) round(($qrWidth - $logoSize) / 2));
        $y = max(0, (int) round(($qrHeight - $logoSize) / 2));

        $platePadding = (int) round($logoSize * 0.14);
        $plateX = max(0, $x - $platePadding);
        $plateY = max(0, $y - $platePadding);
        $plateSize = $logoSize + $platePadding * 2;

        $logo = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="%s"/>
            <image x="%d" y="%d" width="%d" height="%d" href="data:%s;base64,%s" preserveAspectRatio="xMidYMid meet"/>',
            $plateX,
            $plateY,
            $plateSize,
            $plateSize,
            (int) round($plateSize * 0.2),
            $this->backgroundColor,
            $x,
            $y,
            max(1, $logoSize),
            max(1, $logoSize),
            $mime,
            $base64Data,
        );

        return substr_replace($svg, $logo . "\n", strrpos($svg, '</svg>'), 0);
    }

    private function wrapWithFrame(string $qrSvg): string
    {
        return (new FrameRenderer)->render(
            $qrSvg,
            $this->framePreset,
            $this->frameText,
            $this->frameColor,
            $this->frameTextColor,
            $this->backgroundColor,
            $this->frameFont,
        );
    }

    private ?array $designPreviewsCache = null;

    public function getDesignPreviewsProperty(): array
    {
        if ($this->designPreviewsCache !== null) {
            return $this->designPreviewsCache;
        }

        $toDataUri = fn (string $svg) => 'data:image/svg+xml;base64,' . base64_encode($svg);
        $sample = (string) (new DesignGenerator)->format('svg')->size(96)->margin(4)->generate('QR');
        $previews = ['frame' => [], 'module' => [], 'border' => [], 'center' => []];
        foreach (FrameRenderer::layouts() as $key => $label) {
            $previews['frame'][$key] = $key === 'none'
                ? $toDataUri('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M22 22L42 42M42 22L22 42" stroke="#111827" stroke-width="9" stroke-linecap="round"/></svg>')
                : $toDataUri((new FrameRenderer)->render($sample, $key));
        }
        foreach (DesignGenerator::MODULES as $key => $label) {
            $previews['module'][$key] = $toDataUri((string) (new DesignGenerator)->format('svg')->size(96)->margin(4)->design($key, 'square', 'square')->generate('QR'));
        }
        foreach (DesignGenerator::BORDERS as $key => $label) {
            $previews['border'][$key] = $toDataUri(DesignGenerator::eyePreview($key, true));
        }
        foreach (DesignGenerator::CENTERS as $key => $label) {
            $previews['center'][$key] = $toDataUri(DesignGenerator::eyePreview($key, false));
        }

        return $this->designPreviewsCache = $previews;
    }

    private function svgViewBox(string $svg): array
    {
        preg_match('/viewBox="([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.]+)\s+([\d\.]+)"/', $svg, $viewBox);

        return [(int) round((float) ($viewBox[1] ?? 0)), (int) round((float) ($viewBox[2] ?? 0)), (int) round((float) ($viewBox[3] ?? $this->size)), (int) round((float) ($viewBox[4] ?? $this->size))];
    }

    private function hexToRgb(string $hex): array
    {
        $hex = trim($hex);

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return [17, 24, 39];
        }

        return sscanf($hex, '#%02x%02x%02x') ?: [17, 24, 39];
    }

    public function updatedCustomLogo(): void
    {
        if (! $this->isPremium) {
            $this->customLogo = null;
            $this->centerLogo = 'site';
            $this->presetLogo = null;
            $this->refreshPreview(showErrors: false);
            return;
        }
        try {
            $this->validate(['customLogo' => ['image:allow_svg', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048']]);
            $logo = app(LogoOptimizer::class)->normalize((string) $this->customLogo->get(), $this->customLogo->getMimeType());
            app(PreviewStore::class)->putLogo($this->previewToken, $logo);
        } catch (ValidationException $e) {
            $this->previewUpdated('The logo upload was rejected.');
            throw $e;
        } catch (\InvalidArgumentException $e) {
            $this->addError('customLogo', $e->getMessage());
            $this->previewUpdated($e->getMessage());
            return;
        }
        $this->centerLogo = 'custom';
        $this->presetLogo = null;
        $this->refreshPreview(showErrors: false);
    }

    public function selectPreset(string $preset): void
    {
        if (!isset($this->presetIcons[$preset])) {
            return;
        }

        if (!$this->isPremium) {
            $this->centerLogo = 'site';
            $this->presetLogo = null;
            $this->customLogo = null;

            $this->refreshPreview(showErrors: false);
            return;
        }

        app(PreviewStore::class)->forgetLogo($this->previewToken);
        $this->presetLogo = $preset;
        $this->centerLogo = 'preset';
        $this->customLogo = null;

        $this->refreshPreview(showErrors: false);
    }

    public function selectSiteLogo(): void
    {
        app(PreviewStore::class)->forgetLogo($this->previewToken);
        $this->centerLogo = 'site';
        $this->presetLogo = null;
        $this->customLogo = null;

        $this->refreshPreview(showErrors: false);
    }

    public function removeLogo(): void
    {
        if (!$this->isPremium) {
            $this->centerLogo = 'site';
            $this->presetLogo = null;
            $this->customLogo = null;

            $this->refreshPreview(showErrors: false);
            return;
        }

        app(PreviewStore::class)->forgetLogo($this->previewToken);
        $this->centerLogo = null;
        $this->presetLogo = null;
        $this->customLogo = null;

        $this->refreshPreview(showErrors: false);
    }

    public function download()
    {
        if (!$this->generatedQr) {
            return null;
        }

        $content = app(PreviewStore::class)->get($this->previewToken, $this->previewVersion);
        if ($content === null) {
            $this->generate();
            $content = app(PreviewStore::class)->get($this->previewToken, $this->previewVersion);
        }
        if ($content === null) return null;

        return response()->streamDownload(fn() => print $content, 'techwave-qr-code.svg', ['Content-Type' => 'image/svg+xml']);
    }
};
?>

<div data-qr-tool wire:ignore.self class="min-h-screen overflow-x-hidden text-white"
    x-data="qrDesigner({ id: @js($this->getId()), revision: @js($this->uiRevision), url: @js($this->generatedQr), choices: @js($this->uiChoices()) }, $wire)"
    @input.capture="beginInput($event)"
    @qr-preview-updated.window="receive($event.detail)"
    x-on:livewire-upload-start="uploadStart()"
    x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-error="uploadError()"
    x-on:livewire-upload-cancel="uploadError()">
    {{-- Tab state lives in Alpine only (no Livewire round trip), while data-active is rendered
         server side as well so the right tab is styled/visible before Alpine boots. --}}
    <style>
        [data-qr-tool] [x-cloak] { display: none !important; }
        .qr-design-option[data-active="yes"] {
            background: rgb(6 182 212 / 0.2);
            color: rgb(165 243 252);
            box-shadow: 0 0 0 1px rgb(34 211 238 / 0.4);
            border-color: rgb(103 232 249 / 0.5);
        }
        .qr-design-option[data-active="no"] { background: rgb(255 255 255 / 0.05); color: rgb(219 234 254 / 0.5); }
        .qr-design-option[data-active="no"]:hover { background: rgb(255 255 255 / 0.1); }
        .qr-logo-option[data-active="yes"] { border-color: rgb(34 211 238); background: rgb(236 254 255); box-shadow: 0 0 0 1px rgb(34 211 238 / 0.4); }
        .qr-logo-option[data-active="no"] { border-color: rgb(226 232 240); background: white; }
        .qr-logo-option[data-active="no"]:hover { border-color: rgb(103 232 249); }

        .qr-frame-gallery {
            scrollbar-width: thin;
            scrollbar-color: rgb(103 232 249 / 0.4) rgb(255 255 255 / 0.05);
        }

        .qr-gen-tab[data-active="yes"] {
            border-color: rgb(103 232 249);
            color: rgb(207 250 254);
        }

        .qr-gen-tab[data-active="no"] {
            border-color: transparent;
            color: rgb(219 234 254 / 0.45);
        }

        .qr-gen-tab[data-active="no"]:hover {
            color: rgb(207 250 254);
        }

        .qr-gen-panel[data-active="no"] {
            display: none;
        }

        .qr-input-tab[data-active="yes"] {
            border-color: rgb(103 232 249 / 0.4);
            background: rgb(34 211 238 / 0.15);
            color: rgb(207 250 254);
            box-shadow: 0 10px 15px -3px rgb(6 182 212 / 0.1), 0 4px 6px -4px rgb(6 182 212 / 0.1);
        }

        .qr-input-tab[data-active="no"] {
            border-color: rgb(255 255 255 / 0.1);
            background: rgb(255 255 255 / 0.04);
            color: rgb(219 234 254 / 0.55);
        }

        .qr-input-tab[data-active="no"]:hover {
            background: rgb(255 255 255 / 0.08);
        }
    </style>
    <section class="mx-auto max-w-350 px-3 py-6 sm:px-6 lg:px-8">
        <div class="relative mb-6 text-center sm:mb-10">
            <h1 class="mt-2 text-xl font-bold tracking-tight sm:mt-4 sm:text-5xl">
                QR Code
                <span class="bg-linear-to-r from-cyan-300 to-blue-400 bg-clip-text italic text-transparent">Generator</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-blue-100/60 md:text-lg">
                Create branded QR codes with frames, colors, logos and scan-ready export.
            </p>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="min-w-0 space-y-5">
                <div x-data="{ inputType: @js($this->inputType) }"
                    class="overflow-hidden rounded-3xl border border-white/10 bg-white/6 p-4 shadow-xl shadow-cyan-950/10 backdrop-blur-xl sm:p-5">
                    <div class="mb-4 flex items-center gap-3 sm:mb-5">
                        <div
                            class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-cyan-400/15 text-cyan-200">
                            <span class="material-symbols-outlined">edit_square</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold sm:text-lg">1. Add your content</h2>
                            <p class="text-xs text-blue-100/45">
                                QR code will generate only after valid content is added.
                            </p>
                        </div>
                    </div>

                    {{-- Tab switch happens in Alpine (instant); the Livewire $set runs in the
                         background only to keep the server in sync for QR generation. --}}
                    <div wire:ignore class="flex items-center gap-2 flex-warp overflow-x-auto">
                        @foreach ([
                        'url' => ['label' => 'URL', 'icon' => 'link'],
                        'text' => ['label' => 'Text', 'icon' => 'notes'],
                        'wifi' => ['label' => 'WiFi', 'icon' => 'wifi'],
                        'email' => ['label' => 'Email', 'icon' => 'mail'],
                        'phone' => ['label' => 'Phone', 'icon' => 'call'],
                        ] as $val => $cfg)
                        <button type="button"
                            data-active="{{ $this->inputType === $val ? 'yes' : 'no' }}"
                            @click="if (inputType !== '{{ $val }}') { inputType = '{{ $val }}'; $wire.$set('inputType', '{{ $val }}'); }"
                            :data-active="inputType === '{{ $val }}' ? 'yes' : 'no'"
                            class="group qr-input-tab flex cursor-pointer items-center justify-center gap-2 rounded-2xl border px-3 py-1.5 text-center transition">
                            <span class="material-symbols-outlined">{{ $cfg['icon'] }}</span>
                            <span
                                class="block text-xs font-medium uppercase tracking-wider">{{ $cfg['label'] }}</span>
                        </button>
                        @endforeach
                    </div>

                    <div class="mt-5">
                        {{-- Every panel stays in the DOM so Alpine can switch instantly;
                             wire:ignore.self keeps Livewire from resetting data-active. --}}
                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->inputType === 'wifi' ? 'yes' : 'no' }}"
                            :data-active="inputType === 'wifi' ? 'yes' : 'no'">
                        <div class="space-y-3">
                            <input wire:model.live.debounce.300ms="wifiSsid" type="text"
                                placeholder="Network name / SSID"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">

                            <div class="grid gap-3 sm:grid-cols-[150px_1fr]">
                                <select wire:model.live="wifiEncryption"
                                    class="rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    <option value="WPA">WPA/WPA2</option>
                                    <option value="WEP">WEP</option>
                                    <option value="nopass">No Password</option>
                                </select>

                                <input wire:model.live.debounce.300ms="wifiPassword" type="text"
                                    placeholder="WiFi password"
                                    class="rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">
                            </div>

                            <label class="flex cursor-pointer items-center gap-2 text-sm text-blue-100/60">
                                <input type="checkbox" wire:model.live="wifiHidden"
                                    class="rounded border-white/20 bg-black/20 text-cyan-500">
                                Hidden network
                            </label>
                        </div>
                        </div>

                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->inputType === 'email' ? 'yes' : 'no' }}"
                            :data-active="inputType === 'email' ? 'yes' : 'no'">
                        <div class="space-y-3">
                            <input wire:model.live.debounce.300ms="email" type="email"
                                placeholder="name@example.com"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">

                            <input wire:model.live.debounce.300ms="emailSubject" type="text"
                                placeholder="Subject"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">

                            <textarea wire:model.live.debounce.300ms="emailBody" rows="3" placeholder="Message"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10"></textarea>
                        </div>
                        </div>

                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->inputType === 'phone' ? 'yes' : 'no' }}"
                            :data-active="inputType === 'phone' ? 'yes' : 'no'">
                        <input wire:model.live.debounce.300ms="phone" type="text" placeholder="+8801XXXXXXXXX"
                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">
                        </div>

                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->inputType === 'text' ? 'yes' : 'no' }}"
                            :data-active="inputType === 'text' ? 'yes' : 'no'">
                        <textarea wire:model.live.debounce.300ms="input" rows="4" placeholder="Write your text here..."
                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10"></textarea>
                        </div>

                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->inputType === 'url' ? 'yes' : 'no' }}"
                            :data-active="inputType === 'url' ? 'yes' : 'no'">
                        <input wire:model.live.debounce.300ms="input" type="url"
                            placeholder="https://example.com"
                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">
                        </div>

                        @error('input')
                        <p class="mt-2 text-xs font-semibold text-red-300">{{ $message }}</p>
                        @enderror

                        <p class="mt-3 flex items-center gap-2 text-xs font-normal text-blue-100/40">
                            <span class="material-symbols-outlined text-sm">bolt</span>
                            QR code updates automatically when valid content is entered.
                        </p>
                        @if (! $this->isPremium)
                        <p class="mt-2 text-xs leading-5 text-blue-100/50">
                            Free QR codes include the site logo and allow {{ config('qr.free_daily_scan_limit') }} scans per day per code.
                            The limit resets at midnight ({{ config('qr.scan_timezone') }}).
                        </p>
                        @endif
                        @if ($this->inputType !== 'url')
                        <p class="mt-2 text-xs leading-5 text-blue-100/50">Scanning opens a page with your {{ $this->inputType === 'wifi' ? 'Wi-Fi details' : 'content' }} so scans can be counted.</p>
                        @endif
                        @auth
                        <a href="{{ route('account.qr-codes') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-xs text-cyan-200 hover:text-cyan-100">View QR Code Records <span aria-hidden="true">→</span></a>
                        @endauth
                    </div>
                </div>

                <div x-data="{ activeTab: @js($this->activeTab) }"
                    class="overflow-hidden rounded-3xl border border-white/10 bg-white/6 shadow-xl shadow-cyan-950/10 backdrop-blur-xl">
                    <div wire:ignore
                        class="flex items-center gap-1 overflow-x-auto border-b border-white/10 px-3 pt-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ([
                        'frame' => ['label' => 'Frame', 'icon' => 'dialogs', 'premium' => false],
                        'colors' => ['label' => 'Colors', 'icon' => 'palette', 'premium' => false],
                        'shape' => ['label' => 'Shape', 'icon' => 'category', 'premium' => true],
                        'logo' => ['label' => 'Logo', 'icon' => 'image', 'premium' => true],
                        ] as $tab => $cfg)
                        @php $locked = $cfg['premium'] && !$this->isPremium; @endphp

                        <button type="button"
                            data-active="{{ !$locked && $this->activeTab === $tab ? 'yes' : 'no' }}"
                            @if (!$locked) @click="activeTab = '{{ $tab }}'"
                            :data-active="activeTab === '{{ $tab }}' ? 'yes' : 'no'" @endif
                            class="flex shrink-0 cursor-pointer items-center gap-1.5 border-b-2 px-3 py-3 text-[11px] font-medium uppercase tracking-wider transition sm:text-xs
                                {{ $locked ? 'cursor-not-allowed border-transparent text-blue-100/45 opacity-40' : 'qr-gen-tab' }}">
                            <span class="material-symbols-outlined text-base">{{ $cfg['icon'] }}</span>
                            {{ $cfg['label'] }}
                            @if ($locked)
                            <span class="material-symbols-outlined text-sm">lock</span>
                            @endif
                        </button>
                        @endforeach
                    </div>

                    <div class="p-4 sm:p-5">
                        <div class="space-y-5 qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->activeTab === 'frame' ? 'yes' : 'no' }}"
                            :data-active="activeTab === 'frame' ? 'yes' : 'no'">
                            <div>
                                <div x-data="{}">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <span class="text-xs font-medium text-blue-100/55">Layout</span>
                                        <div class="flex gap-2">
                                            <button type="button" aria-label="Scroll frames left"
                                                @click="$refs.frameGallery.scrollBy({ left: -300, behavior: 'smooth' })"
                                                class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg bg-white/5 text-blue-100/60 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-cyan-300">
                                                <span class="material-symbols-outlined text-lg" aria-hidden="true">chevron_left</span>
                                            </button>
                                            <button type="button" aria-label="Scroll frames right"
                                                @click="$refs.frameGallery.scrollBy({ left: 300, behavior: 'smooth' })"
                                                class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg bg-white/5 text-blue-100/60 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-cyan-300">
                                                <span class="material-symbols-outlined text-lg" aria-hidden="true">chevron_right</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-ref="frameGallery" class="qr-frame-gallery flex snap-x snap-proximity gap-3 overflow-x-auto px-1 py-2 pb-4"
                                        role="group" aria-label="Frame layout">
                                        @foreach (FrameRenderer::layouts() as $val => $label)
                                        <x-qr.design-option wire:key="frame-option-{{ $val }}"
                                            property="framePreset" :value="$val"
                                            class="w-28 shrink-0 snap-start"
                                            :label="$label" :selected="$this->framePreset === $val"
                                            :preview="$this->designPreviews['frame'][$val]" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="qr-frame-font" class="mb-1 block text-xs font-medium text-blue-100/55">Phrase font</label>
                                <select id="qr-frame-font" wire:model.live="frameFont"
                                    class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    @foreach (FrameRenderer::FONTS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">Frame
                                        text</label>
                                    <input wire:model.live.debounce.300ms="frameText" type="text" maxlength="48"
                                        class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">Frame
                                        color</label>
                                    <div class="flex gap-2">
                                        <input wire:model.live="frameColor" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="frameColor" type="text"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">Text
                                        color</label>
                                    <div class="flex gap-2">
                                        <input wire:model.live="frameTextColor" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="frameTextColor" type="text"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-5 qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->activeTab === 'colors' ? 'yes' : 'no' }}"
                            :data-active="activeTab === 'colors' ? 'yes' : 'no'">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">QR color</label>
                                    <div class="flex gap-2">
                                        <input wire:model.live="foregroundColor" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="foregroundColor" type="text"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">QR
                                        background</label>
                                    <div class="flex gap-2">
                                        <input wire:model.live="backgroundColor" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="backgroundColor" type="text"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">Size</label>
                                    <input wire:model.live.debounce.300ms="size" type="number" min="200"
                                        max="900" step="20"
                                        class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-blue-100/55">Margin</label>
                                    <input wire:model.live.debounce.300ms="margin" type="number" min="4"
                                        max="10" step="1"
                                        class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                </div>
                            </div>

                            @if ($this->isPremium)
                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                <label
                                    class="mb-2 block text-xs font-medium uppercase tracking-wider text-cyan-100">
                                    Premium gradient
                                </label>

                                <select wire:model.live="gradientType"
                                    class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    <option value="none">No gradient</option>
                                    <option value="vertical">Vertical</option>
                                    <option value="horizontal">Horizontal</option>
                                    <option value="diagonal">Diagonal</option>
                                </select>

                                @if ($this->gradientType !== 'none')
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-medium text-blue-100/55">Start</label>
                                        <div class="flex gap-2">
                                            <input wire:model.live="gradientStart" type="color"
                                                class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                            <input wire:model.live.debounce.300ms="gradientStart" type="text"
                                                class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-medium text-blue-100/55">End</label>
                                        <div class="flex gap-2">
                                            <input wire:model.live="gradientEnd" type="color"
                                                class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                            <input wire:model.live.debounce.300ms="gradientEnd" type="text"
                                                class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->activeTab === 'shape' ? 'yes' : 'no' }}"
                            :data-active="activeTab === 'shape' ? 'yes' : 'no'">
                        @if (!$this->isPremium)
                        <div class="py-10 text-center">
                            <span class="material-symbols-outlined text-5xl text-blue-100/25">lock</span>
                            <h3 class="mt-3 text-lg font-semibold">Unlock QR shapes</h3>
                            <p class="mx-auto mt-2 max-w-sm text-sm text-blue-100/45">
                                Upgrade to customize module shape and eye colors.
                            </p>
                            <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                                class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3 text-xs font-semibold text-white shadow-lg shadow-cyan-500/20">
                                <span class="material-symbols-outlined text-base">workspace_premium</span>
                                Upgrade Now
                            </a>
                        </div>
                        @else
                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-xs font-medium text-blue-100/55">Module style</label>
                                <div class="flex gap-2 overflow-x-auto px-1 py-2 pb-3" role="group" aria-label="Module style">
                                    @foreach (DesignGenerator::MODULES as $val => $label)
                                    <x-qr.design-option wire:key="module-option-{{ $val }}"
                                        property="moduleStyle" :value="$val" class="w-24 shrink-0"
                                        :label="$label" :selected="$this->moduleStyle === $val"
                                        :preview="$this->designPreviews['module'][$val]" />
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-medium text-blue-100/55">Border style</label>
                                <div class="flex gap-2 overflow-x-auto px-1 py-2 pb-3" role="group" aria-label="Border style">
                                    @foreach (DesignGenerator::BORDERS as $val => $label)
                                    <x-qr.design-option wire:key="border-option-{{ $val }}"
                                        property="eyeStyle" :value="$val" class="w-24 shrink-0"
                                        :label="$label" :selected="$this->eyeStyle === $val"
                                        :preview="$this->designPreviews['border'][$val]" />
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <label for="qr-eye-border-color" class="mb-1 block text-xs font-medium text-blue-100/55">Border color</label>
                                    <div class="flex gap-2">
                                        <input id="qr-eye-border-color" wire:model.live="eyeOuterColors.0" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="eyeOuterColors.0" type="text" aria-label="Border color hex value"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-medium text-blue-100/55">Center style</label>
                                <div class="flex gap-2 overflow-x-auto px-1 py-2 pb-3" role="group" aria-label="Center style">
                                    @foreach (DesignGenerator::CENTERS as $val => $label)
                                    <x-qr.design-option wire:key="center-option-{{ $val }}"
                                        property="eyeCenterStyle" :value="$val" class="w-24 shrink-0"
                                        :label="$label" :selected="$this->eyeCenterStyle === $val"
                                        :preview="$this->designPreviews['center'][$val]" />
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <label for="qr-eye-center-color" class="mb-1 block text-xs font-medium text-blue-100/55">Eye color</label>
                                    <div class="flex gap-2">
                                        <input id="qr-eye-center-color" wire:model.live="eyeInnerColors.0" type="color"
                                            class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                        <input wire:model.live.debounce.300ms="eyeInnerColors.0" type="text" aria-label="Eye color hex value"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        </div>
                        <div class="qr-gen-panel" wire:ignore.self
                            data-active="{{ $this->activeTab === 'logo' ? 'yes' : 'no' }}"
                            :data-active="activeTab === 'logo' ? 'yes' : 'no'">
                        @if (!$this->isPremium)
                        <div class="py-10 text-center">
                            <span class="material-symbols-outlined text-5xl text-blue-100/25">lock</span>
                            <h3 class="mt-3 text-lg font-semibold">Logo customization is premium</h3>
                            <p class="mx-auto mt-2 max-w-sm text-sm text-blue-100/45">
                                Free users will use the default site logo. Upgrade to remove logo, change icon,
                                or upload custom logo.
                            </p>
                            <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                                class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3 text-xs font-semibold text-white shadow-lg shadow-cyan-500/20">
                                <span class="material-symbols-outlined text-base">workspace_premium</span>
                                Upgrade Now
                            </a>
                        </div>
                        @else
                        <div class="space-y-5">
                            <div>
                                <label class="mb-3 block text-xs font-medium text-blue-100/55">Choose a logo</label>
                                <div class="grid grid-cols-4 gap-2 sm:grid-cols-7 sm:gap-3" role="group" aria-label="Logo icons">
                                    <button type="button" wire:ignore.self @click="chooseLogo('none', 'removeLogo')" data-active="{{ $this->centerLogo === null ? 'yes' : 'no' }}"
                                        :data-active="choices.logo === 'none' ? 'yes' : 'no'" :aria-pressed="choices.logo === 'none' ? 'true' : 'false'" aria-label="No logo" title="No logo"
                                        aria-pressed="{{ $this->centerLogo === null ? 'true' : 'false' }}"
                                        class="qr-logo-option grid h-20 cursor-pointer place-items-center rounded-xl border-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 sm:h-24
                                            {{ $this->centerLogo === null ? 'border-cyan-400 bg-cyan-50 ring-1 ring-cyan-400/40' : 'border-slate-200 bg-white hover:border-cyan-300' }}">
                                        <svg viewBox="0 0 64 64" class="h-12 w-12" aria-hidden="true"><path d="M22 22L42 42M42 22L22 42" stroke="#111827" stroke-width="9" stroke-linecap="round"/></svg>
                                    </button>
                                    @foreach ($this->presetIcons as $key => $icon)
                                    @if ($icon['group'] === 'icon')
                                    <button type="button" wire:key="logo-icon-{{ $key }}" wire:ignore.self @click="chooseLogo('preset:{{ $key }}', 'selectPreset', '{{ $key }}')"
                                        data-active="{{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'yes' : 'no' }}"
                                        :data-active="choices.logo === 'preset:{{ $key }}' ? 'yes' : 'no'"
                                        :aria-pressed="choices.logo === 'preset:{{ $key }}' ? 'true' : 'false'"
                                        aria-label="{{ $icon['label'] }} logo" title="{{ $icon['label'] }}"
                                        aria-pressed="{{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'true' : 'false' }}"
                                        class="qr-logo-option grid h-20 cursor-pointer place-items-center rounded-xl border-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 sm:h-24
                                            {{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'border-cyan-400 bg-cyan-50 ring-1 ring-cyan-400/40' : 'border-slate-200 bg-white hover:border-cyan-300' }}">
                                        <img src="data:image/svg+xml;base64,{{ base64_encode($icon['svg']) }}" alt="" class="h-12 w-12 sm:h-14 sm:w-14">
                                    </button>
                                    @endif
                                    @endforeach
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-3" role="group" aria-label="Scan logos">
                                    @foreach ($this->presetIcons as $key => $icon)
                                    @if ($icon['group'] === 'scan')
                                    <button type="button" wire:key="logo-scan-{{ $key }}" wire:ignore.self @click="chooseLogo('preset:{{ $key }}', 'selectPreset', '{{ $key }}')"
                                        data-active="{{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'yes' : 'no' }}"
                                        :data-active="choices.logo === 'preset:{{ $key }}' ? 'yes' : 'no'"
                                        :aria-pressed="choices.logo === 'preset:{{ $key }}' ? 'true' : 'false'"
                                        aria-label="{{ $icon['label'] }} logo" title="{{ $icon['label'] }}"
                                        aria-pressed="{{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'true' : 'false' }}"
                                        class="qr-logo-option grid h-20 cursor-pointer place-items-center rounded-xl border-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 sm:h-24
                                            {{ $this->centerLogo === 'preset' && $this->presetLogo === $key ? 'border-cyan-400 bg-cyan-50 ring-1 ring-cyan-400/40' : 'border-slate-200 bg-white hover:border-cyan-300' }}">
                                        <img src="data:image/svg+xml;base64,{{ base64_encode($icon['svg']) }}" alt="" class="h-14 w-full max-w-28 object-contain sm:h-16">
                                    </button>
                                    @endif
                                    @endforeach
                                </div>
                                <button type="button" wire:ignore.self @click="chooseLogo('site', 'selectSiteLogo')"
                                    data-active="{{ $this->centerLogo === 'site' ? 'yes' : 'no' }}" :data-active="choices.logo === 'site' ? 'yes' : 'no'"
                                    :aria-pressed="choices.logo === 'site' ? 'true' : 'false'"
                                    aria-pressed="{{ $this->centerLogo === 'site' ? 'true' : 'false' }}"
                                    class="qr-design-option mt-4 cursor-pointer rounded-xl border px-4 py-2.5 text-xs font-medium transition focus-visible:outline-2 focus-visible:outline-cyan-300
                                        {{ $this->centerLogo === 'site' ? 'border-cyan-300/50 bg-cyan-400/10 text-cyan-100' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                    Use site logo
                                </button>
                            </div>

                            <div class="border-t border-white/10 pt-5">
                                <label
                                    wire:ignore.self data-active="{{ $this->centerLogo === 'custom' ? 'yes' : 'no' }}"
                                    :data-active="choices.logo === 'custom' ? 'yes' : 'no'"
                                    class="qr-design-option block cursor-pointer rounded-2xl border border-dashed border-white/15 bg-white/4 px-5 py-6 text-center transition hover:bg-white/8">
                                    <span
                                        class="material-symbols-outlined text-3xl text-blue-100/35">cloud_upload</span>
                                    <p class="mt-2 text-sm font-semibold text-blue-100/70">Upload custom logo</p>
                                    <p class="mt-1 text-xs text-blue-100/35">PNG, JPG, WebP or SVG. Max 2MB; raster images up to 16 megapixels.
                                    </p>
                                    <input wire:model="customLogo" type="file" accept="image/*,.svg"
                                        class="hidden">
                                </label>

                                @error('customLogo')
                                <p class="mt-2 text-xs font-semibold text-red-300">{{ $message }}</p>
                                @enderror

                                @if ($this->customLogo)
                                <div
                                    class="mt-3 flex items-center gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                                    <span
                                        class="material-symbols-outlined shrink-0 text-cyan-200">image</span>
                                    <span class="break-all text-xs font-semibold text-blue-100/60">
                                        {{ $this->customLogo->getClientOriginalName() }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="min-w-0 xl:sticky xl:top-24 xl:self-start">
                <div
                    class="overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/6 shadow-2xl shadow-cyan-950/20 backdrop-blur-xl">
                    <div class="border-b border-white/10 p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold sm:text-lg">Live preview</h2>
                                <p class="mt-1 text-xs text-blue-100/45">QR appears only after valid content.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div
                            wire:ignore :aria-busy="busy || uploading" class="relative grid min-h-[320px] place-items-center overflow-hidden rounded-3xl border border-dashed border-white/15 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.12),_transparent_45%),rgba(255,255,255,0.03)] p-3 sm:min-h-[400px] sm:p-5">
                            <img :src="displayUrl || null" x-show="displayUrl" x-cloak alt="Generated QR Code" x-ref="qrImg"
                                :class="busy || uploading ? 'opacity-40' : 'opacity-100'"
                                class="max-h-[280px] max-w-full drop-shadow-2xl transition-opacity sm:max-h-[390px]">
                            <div
                                x-show="!displayUrl" class="w-full max-w-[250px] rounded-[1.5rem] border border-white/10 bg-white p-4 text-center shadow-2xl sm:max-w-[310px] sm:rounded-[2rem] sm:p-6">
                                <div
                                    class="mx-auto grid h-40 w-40 grid-cols-7 gap-1 rounded-2xl bg-slate-50 p-3 sm:h-56 sm:w-56 sm:p-4">
                                    @foreach (range(1, 49) as $i)
                                    <div
                                        class="rounded-sm {{ in_array($i, [1, 2, 3, 4, 5, 8, 12, 15, 19, 22, 23, 24, 25, 26, 29, 33, 36, 40, 43, 44, 45, 46, 47, 7, 14, 21, 35, 42, 49, 28, 31, 38]) ? 'bg-slate-900' : 'bg-slate-200' }}">
                                    </div>
                                    @endforeach
                                </div>

                                <div
                                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-[10px] font-medium uppercase tracking-wider text-slate-500 sm:mt-5 sm:px-4 sm:text-xs">
                                    <span class="material-symbols-outlined text-base">qr_code_2</span>
                                    QR Preview
                                </div>

                                <p class="mt-3 text-xs font-normal text-slate-500 sm:text-sm">
                                    Enter valid content to generate your QR code.
                                </p>
                            </div>
                            <div x-show="busy || uploading" x-cloak role="status" aria-live="polite"
                                class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-3xl bg-slate-950/65 backdrop-blur-sm">
                                <span class="h-9 w-9 animate-spin rounded-full border-2 border-cyan-300/30 border-t-cyan-200" aria-hidden="true"></span>
                                <span class="text-sm font-medium text-cyan-100" x-text="uploading ? 'Uploading logo… ' + uploadProgress + '%' : 'Updating QR code…'"></span>
                            </div>
                            <div x-show="error" x-cloak role="alert" class="absolute inset-x-3 bottom-3 z-20 rounded-xl border border-red-300/20 bg-red-950/90 p-3 text-center text-xs text-red-100">
                                Preview could not update. Try selecting a style again.
                            </div>
                        </div>

                        <button type="button" wire:ignore x-show="displayUrl" x-cloak @click="download($event)" :disabled="busy || uploading || downloading || error || !displayUrl"
                            class="mt-5 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition disabled:cursor-wait disabled:opacity-50 hover:-translate-y-0.5 hover:shadow-cyan-500/30">
                            <span class="material-symbols-outlined text-base">download</span>
                            <span class="btn-text">Download QR Code</span>
                        </button>

                        <p wire:ignore x-show="downloadError" x-cloak role="alert" x-text="downloadError"
                            class="mt-3 text-center text-xs text-red-200"></p>

                        <p class="mt-4 text-center text-xs leading-5 text-blue-100/40">
                            For best scan result, keep strong contrast between QR color and background.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@assets
<script>
    {!! file_get_contents(resource_path('js/qr-designer.js')) !!}

</script>

@endassets
