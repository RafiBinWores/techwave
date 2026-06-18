<?php

use App\Models\SiteSetting;
use App\Models\ToolCategory;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

    public string $moduleStyle = 'square';
    public string $eyeStyle = 'square';

    public array $eyeInnerColors = ['#111827', '#111827', '#111827'];
    public array $eyeOuterColors = ['#111827', '#111827', '#111827'];

    public ?string $centerLogo = 'site';
    public ?string $presetLogo = null;
    public $customLogo = null;

    public ?string $generatedQr = null;
    public ?string $generatedFormat = null;

    public int $size = 420;
    public int $margin = 2;

    public function mount(): void
    {
        $siteSetting = SiteSetting::current();

        $this->centerLogo = filled($siteSetting->logo) ? 'site' : null;

        $this->input = '';
        $this->generatedQr = null;
        $this->generatedFormat = null;
    }

    public function getIsPremiumProperty(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $category = ToolCategory::query()->where('slug', 'business-tools')->first();

        if (!$category) {
            return false;
        }

        return auth()->user()?->hasActiveToolSubscription($category) ?? false;
    }

    public function getPresetIconsProperty(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#1877F2"/><path fill="#fff" d="M38 22h5v-8h-6c-7 0-11 4-11 11v5h-6v8h6v16h9V38h7l1-8h-8v-4c0-3 1-4 3-4z"/></svg>',
            ],
            'instagram' => [
                'label' => 'Instagram',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" x2="1" y1="1" y2="0"><stop stop-color="#feda75"/><stop offset=".4" stop-color="#d62976"/><stop offset=".7" stop-color="#962fbf"/><stop offset="1" stop-color="#4f5bd5"/></linearGradient></defs><rect width="64" height="64" rx="18" fill="url(#g)"/><rect x="17" y="17" width="30" height="30" rx="9" fill="none" stroke="#fff" stroke-width="4"/><circle cx="32" cy="32" r="8" fill="none" stroke="#fff" stroke-width="4"/><circle cx="42" cy="22" r="3" fill="#fff"/></svg>',
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#25D366"/><path fill="#fff" d="M32 14a18 18 0 0 0-15 28l-3 9 10-3a18 18 0 1 0 8-34zm9 27c-1 2-4 3-6 2-8-2-13-8-15-15 0-2 1-5 3-6 1-1 2 0 3 1l2 5c0 1 0 2-1 3l-1 1c2 4 4 6 8 8l2-2c1-1 2-1 3 0l4 2c1 0 1 1 0 1z"/></svg>',
            ],
            'wifi' => [
                'label' => 'WiFi',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#0f172a"/><path fill="#fff" d="M32 46a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-14-12 6 6a12 12 0 0 1 16 0l6-6a20 20 0 0 0-28 0zm-8-9 6 6a28 28 0 0 1 32 0l6-6a36 36 0 0 0-44 0z"/></svg>',
            ],
            'scan' => [
                'label' => 'Scan',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="18" fill="#06b6d4"/><path fill="#fff" d="M16 16h14v14H16V16zm18 0h14v14H34V16zM16 34h14v14H16V34zm21 0h5v5h-5v-5zm7 7h4v7h-4v-7zm-10 3h7v4h-7v-4z"/></svg>',
            ],
        ];
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
        if ($property === 'customLogo' || $property === 'activeTab') {
            return;
        }

        $this->refreshPreview(showErrors: false);
    }

    private function refreshPreview(bool $showErrors = false): void
    {
        $data = $this->buildQrData();

        if (!filled($data)) {
            $this->generatedQr = null;
            $this->generatedFormat = null;

            if ($showErrors) {
                $this->addError('input', $this->contentErrorMessage());
            } else {
                $this->resetErrorBag('input');
            }

            return;
        }

        try {
            $svg = $this->buildQrSvg($data);

            $this->generatedQr = 'data:image/svg+xml;base64,' . base64_encode($svg);
            $this->generatedFormat = 'svg';

            $this->resetErrorBag();
        } catch (\Throwable $e) {
            $this->generatedQr = null;
            $this->generatedFormat = null;
            $this->addError('input', 'QR generation failed. Please check your input and design settings.');
        }
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

        $moduleStyle = in_array($this->moduleStyle, ['square', 'dot', 'round'], true) ? $this->moduleStyle : 'square';

        $eyeStyle = in_array($this->eyeStyle, ['square', 'circle'], true) ? $this->eyeStyle : 'square';

        $qr = QrCode::format('svg')
            ->size(max(200, min(900, $this->size)))
            ->margin(max(0, min(10, $this->margin)))
            ->errorCorrection('H')
            ->style($moduleStyle)
            ->eye($eyeStyle)
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

        $svg = (string) $qr->generate($data);

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

    private function embedLogoSvg(string $svg): string
    {
        $siteSetting = SiteSetting::current();

        if (!$siteSetting->logo || !Storage::disk('public')->exists($siteSetting->logo)) {
            return $svg;
        }

        $path = Storage::disk('public')->path($siteSetting->logo);
        $data = base64_encode((string) file_get_contents($path));
        $mime = mime_content_type($path) ?: 'image/png';

        return $data ? $this->insertCenterImage($svg, $data, $mime) : $svg;
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
        if (!$this->customLogo) {
            return $svg;
        }

        $data = base64_encode((string) $this->customLogo->get());
        $mime = $this->customLogo->getMimeType() ?: 'image/png';

        return $data ? $this->insertCenterImage($svg, $data, $mime) : $svg;
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
        [$x0, $y0, $qrW, $qrH] = $this->svgViewBox($qrSvg);

        $padding = 34;
        $textHeight = filled($this->frameText) ? 62 : 26;

        $newW = $qrW + $padding * 2;
        $newH = $qrH + $padding * 2 + $textHeight;

        $qrX = $padding;
        $qrY = $this->framePreset === 'scan-top' ? $padding + $textHeight : $padding;

        $textY = $this->framePreset === 'scan-top' ? 43 : $qrY + $qrH + 43;

        if ($this->framePreset === 'card') {
            $newH = $qrH + 120;
            $qrX = (int) round(($newW - $qrW) / 2);
            $qrY = 34;
            $textY = $qrY + $qrH + 48;
        }

        $qrData = base64_encode($qrSvg);
        $safeFrameText = $this->xmlEscape($this->frameText ?: 'SCAN ME');

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">
                <rect width="%d" height="%d" rx="34" fill="%s"/>
                <rect x="%d" y="%d" width="%d" height="%d" rx="24" fill="%s"/>
                <image x="%d" y="%d" width="%d" height="%d" href="data:image/svg+xml;base64,%s" preserveAspectRatio="xMidYMid meet"/>
                <text x="%d" y="%d" text-anchor="middle" fill="%s" font-size="28" font-family="Arial, sans-serif" font-weight="800" letter-spacing="3">%s</text>
            </svg>',
            $newW,
            $newH,
            $newW,
            $newH,
            $newW,
            $newH,
            $this->framePreset === 'card' ? '#ffffff' : $this->frameColor,
            $qrX - 10,
            $qrY - 10,
            $qrW + 20,
            $qrH + 20,
            $this->backgroundColor,
            $qrX,
            $qrY,
            $qrW,
            $qrH,
            $qrData,
            (int) round($newW / 2),
            $textY,
            $this->framePreset === 'card' ? $this->frameColor : $this->frameTextColor,
            $safeFrameText,
        );
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
        if (!$this->isPremium) {
            $this->customLogo = null;
            $this->centerLogo = 'site';
            $this->presetLogo = null;

            $this->refreshPreview(showErrors: false);
            return;
        }

        $this->validate([
            'customLogo' => ['image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $this->centerLogo = 'custom';
        $this->presetLogo = null;

        $this->refreshPreview(showErrors: false);
    }

    public function selectPreset(string $preset): void
    {
        if (!$this->isPremium) {
            $this->centerLogo = 'site';
            $this->presetLogo = null;
            $this->customLogo = null;

            $this->refreshPreview(showErrors: false);
            return;
        }

        $this->presetLogo = $preset;
        $this->centerLogo = 'preset';
        $this->customLogo = null;

        $this->refreshPreview(showErrors: false);
    }

    public function selectSiteLogo(): void
    {
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

        $content = base64_decode(explode(',', $this->generatedQr)[1] ?? '');

        return response()->streamDownload(fn() => print $content, 'techwave-qr-code.svg', ['Content-Type' => 'image/svg+xml']);
    }

    private function xmlEscape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
};
?>

<div class="min-h-screen overflow-x-hidden text-white">
    <section class="mx-auto max-w-350 px-3 py-6 sm:px-6 lg:px-8">
        <div class="relative mb-6 text-center sm:mb-10">
            <p class="text-[10px] font-black uppercase tracking-[0.28em] text-cyan-300 sm:text-xs sm:tracking-[0.35em]">
                Business Tools
            </p>
            <h1 class="mt-2 text-xl font-black tracking-tight sm:mt-4 sm:text-5xl">
                QR Code Generator
            </h1>
            <p class="mx-auto mt-2 max-w-2xl text-xs leading-5 text-blue-100/65 sm:mt-4 sm:text-sm sm:leading-6">
                Create branded QR codes with frames, colors, logos and scan-ready SVG export.
            </p>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="min-w-0 space-y-5">
                <div
                    class="overflow-hidden rounded-3xl border border-white/10 bg-white/6 p-4 shadow-xl shadow-cyan-950/10 backdrop-blur-xl sm:p-5">
                    <div class="mb-4 flex items-center gap-3 sm:mb-5">
                        <div
                            class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-cyan-400/15 text-cyan-200">
                            <span class="material-symbols-outlined">edit_square</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-black sm:text-lg">1. Add your content</h2>
                            <p class="text-xs text-blue-100/45">
                                QR code will generate only after valid content is added.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-warp overflow-x-auto">
                        @foreach ([
        'url' => ['label' => 'URL', 'icon' => 'link'],
        'text' => ['label' => 'Text', 'icon' => 'notes'],
        'wifi' => ['label' => 'WiFi', 'icon' => 'wifi'],
        'email' => ['label' => 'Email', 'icon' => 'mail'],
        'phone' => ['label' => 'Phone', 'icon' => 'call'],
    ] as $val => $cfg)
                            <button type="button" wire:click="$set('inputType', '{{ $val }}')"
                                class="group flex cursor-pointer items-center justify-center gap-2 rounded-2xl border px-3 py-1.5 text-center transition
                                {{ $this->inputType === $val ? 'border-cyan-300/40 bg-cyan-400/15 text-cyan-100 shadow-lg shadow-cyan-500/10' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                <span class="material-symbols-outlined">{{ $cfg['icon'] }}</span>
                                <span
                                    class="block text-xs md:font-medium uppercase tracking-wider">{{ $cfg['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-5">
                        @if ($this->inputType === 'wifi')
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
                        @elseif ($this->inputType === 'email')
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
                        @elseif ($this->inputType === 'phone')
                            <input wire:model.live.debounce.300ms="phone" type="text" placeholder="+8801XXXXXXXXX"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">
                        @elseif ($this->inputType === 'text')
                            <textarea wire:model.live.debounce.300ms="input" rows="4" placeholder="Write your text here..."
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10"></textarea>
                        @else
                            <input wire:model.live.debounce.300ms="input" type="url"
                                placeholder="https://example.com"
                                class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-400/10">
                        @endif

                        @error('input')
                            <p class="mt-2 text-xs font-semibold text-red-300">{{ $message }}</p>
                        @enderror

                        <p class="mt-3 flex items-center gap-2 text-xs font-semibold text-blue-100/40">
                            <span class="material-symbols-outlined text-sm">bolt</span>
                            QR code updates automatically when valid content is entered.
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-3xl border border-white/10 bg-white/6 shadow-xl shadow-cyan-950/10 backdrop-blur-xl">
                    <div
                        class="flex items-center gap-1 overflow-x-auto border-b border-white/10 px-3 pt-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ([
        'frame' => ['label' => 'Frame', 'icon' => 'dialogs', 'premium' => false],
        'colors' => ['label' => 'Colors', 'icon' => 'palette', 'premium' => false],
        'shape' => ['label' => 'Shape', 'icon' => 'category', 'premium' => true],
        'logo' => ['label' => 'Logo', 'icon' => 'image', 'premium' => true],
    ] as $tab => $cfg)
                            @php $locked = $cfg['premium'] && !$this->isPremium; @endphp

                            <button type="button"
                                wire:click="{{ $locked ? '' : '$set(\'activeTab\', \'' . $tab . '\')' }}"
                                class="flex shrink-0 cursor-pointer items-center gap-1.5 border-b-2 px-3 py-3 text-[11px] font-black uppercase tracking-wider transition sm:text-xs
                                {{ $locked ? 'cursor-not-allowed opacity-40' : 'hover:text-cyan-100' }}
                                {{ $this->activeTab === $tab && !$locked ? 'border-cyan-300 text-cyan-100' : 'border-transparent text-blue-100/45' }}">
                                <span class="material-symbols-outlined text-base">{{ $cfg['icon'] }}</span>
                                {{ $cfg['label'] }}
                                @if ($locked)
                                    <span class="material-symbols-outlined text-sm">lock</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="p-4 sm:p-5">
                        @if ($this->activeTab === 'frame')
                            <div class="space-y-5">
                                <div>
                                    <label class="mb-2 block text-xs font-bold text-blue-100/55">Layout</label>
                                    <div class="flex gap-2">
                                        @foreach (['none' => 'None', 'scan-bottom' => 'Bottom', 'scan-top' => 'Top', 'card' => 'Card'] as $val => $label)
                                            <button type="button"
                                                wire:click="$set('framePreset', '{{ $val }}')"
                                                class="flex-1 rounded-xl py-2.5 text-xs font-bold uppercase tracking-wider transition
                                                    {{ $this->framePreset === $val ? 'bg-cyan-500/20 text-cyan-200 ring-1 ring-cyan-400/40' : 'bg-white/5 text-blue-100/50 hover:bg-white/10' }}">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">Frame
                                            text</label>
                                        <input wire:model.live.debounce.300ms="frameText" type="text"
                                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">Frame
                                            color</label>
                                        <div class="flex gap-2">
                                            <input wire:model.live="frameColor" type="color"
                                                class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                            <input wire:model.live.debounce.300ms="frameColor" type="text"
                                                class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">Text
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
                        @elseif ($this->activeTab === 'colors')
                            <div class="space-y-5">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">QR color</label>
                                        <div class="flex gap-2">
                                            <input wire:model.live="foregroundColor" type="color"
                                                class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12 sm:w-14">
                                            <input wire:model.live.debounce.300ms="foregroundColor" type="text"
                                                class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-black/25 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-300/40 sm:px-4 sm:py-3">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">QR
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
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">Size</label>
                                        <input wire:model.live.debounce.300ms="size" type="number" min="200"
                                            max="900" step="20"
                                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-blue-100/55">Margin</label>
                                        <input wire:model.live.debounce.300ms="margin" type="number" min="0"
                                            max="10" step="1"
                                            class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-sm text-white outline-none focus:border-cyan-300/40">
                                    </div>
                                </div>

                                @if ($this->isPremium)
                                    <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                        <label
                                            class="mb-2 block text-xs font-black uppercase tracking-wider text-cyan-100">
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
                                                        class="mb-1 block text-xs font-bold text-blue-100/55">Start</label>
                                                    <input wire:model.live="gradientStart" type="color"
                                                        class="h-10 w-full cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12">
                                                </div>
                                                <div>
                                                    <label
                                                        class="mb-1 block text-xs font-bold text-blue-100/55">End</label>
                                                    <input wire:model.live="gradientEnd" type="color"
                                                        class="h-10 w-full cursor-pointer rounded-xl border border-white/10 bg-black/25 p-1 sm:h-12">
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @elseif ($this->activeTab === 'shape')
                            @if (!$this->isPremium)
                                <div class="py-10 text-center">
                                    <span class="material-symbols-outlined text-5xl text-blue-100/25">lock</span>
                                    <h3 class="mt-3 text-lg font-black">Unlock QR shapes</h3>
                                    <p class="mx-auto mt-2 max-w-sm text-sm text-blue-100/45">
                                        Upgrade to customize module shape and eye colors.
                                    </p>
                                    <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                                        class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-cyan-500/20">
                                        <span class="material-symbols-outlined text-base">workspace_premium</span>
                                        Upgrade Now
                                    </a>
                                </div>
                            @else
                                <div class="space-y-5">
                                    <div>
                                        <label
                                            class="mb-3 block text-xs font-black uppercase tracking-wider text-blue-100/55">
                                            Module style
                                        </label>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3">
                                            @foreach (['square' => 'Square', 'dot' => 'Dot', 'round' => 'Round'] as $val => $label)
                                                <button type="button"
                                                    wire:click="$set('moduleStyle', '{{ $val }}')"
                                                    class="rounded-2xl border px-4 py-3 text-xs font-black transition sm:py-4
                                                    {{ $this->moduleStyle === $val ? 'border-cyan-300/50 bg-cyan-400/10 text-cyan-100' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                                    {{ $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-3 block text-xs font-black uppercase tracking-wider text-blue-100/55">
                                            Eye style
                                        </label>
                                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                            @foreach (['square' => 'Square eye', 'circle' => 'Circle eye'] as $val => $label)
                                                <button type="button"
                                                    wire:click="$set('eyeStyle', '{{ $val }}')"
                                                    class="rounded-2xl border px-4 py-3 text-xs font-black transition sm:py-4
                                                    {{ $this->eyeStyle === $val ? 'border-cyan-300/50 bg-cyan-400/10 text-cyan-100' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                                    {{ $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @elseif ($this->activeTab === 'logo')
                            @if (!$this->isPremium)
                                <div class="py-10 text-center">
                                    <span class="material-symbols-outlined text-5xl text-blue-100/25">lock</span>
                                    <h3 class="mt-3 text-lg font-black">Logo customization is premium</h3>
                                    <p class="mx-auto mt-2 max-w-sm text-sm text-blue-100/45">
                                        Free users will use the default site logo. Upgrade to remove logo, change icon,
                                        or upload custom logo.
                                    </p>
                                    <a href="{{ route('account.tool-subscriptions') }}" wire:navigate
                                        class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-cyan-500/20">
                                        <span class="material-symbols-outlined text-base">workspace_premium</span>
                                        Upgrade Now
                                    </a>
                                </div>
                            @else
                                <div class="space-y-5">
                                    <div>
                                        <label
                                            class="mb-3 block text-xs font-black uppercase tracking-wider text-blue-100/55">
                                            Center logo
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="removeLogo"
                                                class="rounded-2xl border px-4 py-2.5 text-xs font-black transition
                                                {{ $this->centerLogo === null ? 'border-cyan-300/50 bg-cyan-400/10 text-cyan-100' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                                None
                                            </button>

                                            <button type="button" wire:click="selectSiteLogo"
                                                class="rounded-2xl border px-4 py-2.5 text-xs font-black transition
                                                {{ $this->centerLogo === 'site' ? 'border-cyan-300/50 bg-cyan-400/10 text-cyan-100' : 'border-white/10 bg-white/4 text-blue-100/55 hover:bg-white/8' }}">
                                                Site Logo
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-3 block text-xs font-black uppercase tracking-wider text-blue-100/55">
                                            Preset icons
                                        </label>
                                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 sm:gap-3">
                                            @foreach ($this->presetIcons as $key => $icon)
                                                <button type="button"
                                                    wire:click="selectPreset('{{ $key }}')"
                                                    class="rounded-2xl border p-2 text-center transition sm:p-3
                                                    {{ $this->presetLogo === $key ? 'border-cyan-300/50 bg-cyan-400/10' : 'border-white/10 bg-white/4 hover:bg-white/8' }}">
                                                    <img src="data:image/svg+xml;base64,{{ base64_encode($icon['svg']) }}"
                                                        alt="{{ $icon['label'] }}"
                                                        class="mx-auto h-9 w-9 rounded-xl sm:h-10 sm:w-10">
                                                    <span
                                                        class="mt-2 block text-[10px] font-bold text-blue-100/60 sm:text-[11px]">
                                                        {{ $icon['label'] }}
                                                    </span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="border-t border-white/10 pt-5">
                                        <label
                                            class="block cursor-pointer rounded-2xl border border-dashed border-white/15 bg-white/4 px-5 py-6 text-center transition hover:bg-white/8">
                                            <span
                                                class="material-symbols-outlined text-3xl text-blue-100/35">cloud_upload</span>
                                            <p class="mt-2 text-sm font-black text-blue-100/70">Upload custom logo</p>
                                            <p class="mt-1 text-xs text-blue-100/35">PNG, JPG, WebP or SVG. Max 2MB.
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
                        @endif
                    </div>
                </div>
            </div>

            <div class="min-w-0 xl:sticky xl:top-24 xl:self-start">
                <div
                    class="overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/6 shadow-2xl shadow-cyan-950/20 backdrop-blur-xl">
                    <div class="border-b border-white/10 p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-black sm:text-lg">Live preview</h2>
                                <p class="mt-1 text-xs text-blue-100/45">QR appears only after valid content.</p>
                            </div>
                            <span
                                class="rounded-full bg-emerald-400/10 px-3 py-1.5 text-xs font-black text-emerald-200">
                                SVG
                            </span>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div
                            class="grid min-h-[320px] place-items-center rounded-3xl border border-dashed border-white/15 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.12),_transparent_45%),rgba(255,255,255,0.03)] p-3 sm:min-h-[400px] sm:p-5">
                            @if ($this->generatedQr)
                                <img src="{{ $this->generatedQr }}" alt="Generated QR Code" x-ref="qrImg"
                                    class="max-h-[280px] max-w-full drop-shadow-2xl sm:max-h-[390px]">
                            @else
                                <div
                                    class="w-full max-w-[250px] rounded-[1.5rem] border border-white/10 bg-white p-4 text-center shadow-2xl sm:max-w-[310px] sm:rounded-[2rem] sm:p-6">
                                    <div
                                        class="mx-auto grid h-40 w-40 grid-cols-7 gap-1 rounded-2xl bg-slate-50 p-3 sm:h-56 sm:w-56 sm:p-4">
                                        @foreach (range(1, 49) as $i)
                                            <div
                                                class="rounded-sm {{ in_array($i, [1, 2, 3, 4, 5, 8, 12, 15, 19, 22, 23, 24, 25, 26, 29, 33, 36, 40, 43, 44, 45, 46, 47, 7, 14, 21, 35, 42, 49, 28, 31, 38]) ? 'bg-slate-900' : 'bg-slate-200' }}">
                                            </div>
                                        @endforeach
                                    </div>

                                    <div
                                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-slate-500 sm:mt-5 sm:px-4 sm:text-xs">
                                        <span class="material-symbols-outlined text-base">qr_code_2</span>
                                        QR Preview
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-500 sm:text-sm">
                                        Enter valid content to generate your QR code.
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if ($this->generatedQr)
                            <button type="button" x-data x-on:click="window.downloadQr($event)"
                                class="mt-5 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:shadow-cyan-500/30">
                                <span class="material-symbols-outlined text-base">download</span>
                                <span class="btn-text">Download QR Code</span>
                            </button>
                        @endif

                        <p class="mt-4 text-center text-xs leading-5 text-blue-100/40">
                            For best scan result, keep strong contrast between QR color and background.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
    <script>
    window.downloadQr = async function(event) {
        const btn = event.currentTarget;
        const txt = btn.querySelector('.btn-text');
        const orig = txt.textContent;
        txt.textContent = 'Downloading...';
        btn.disabled = true;

        try {
            const imgEl = document.querySelector('[x-ref="qrImg"]');
            if (!imgEl) return;

            const resp = await fetch(imgEl.src);
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);

            const img = new Image();
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
                img.src = url;
            });

            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth || 400;
            canvas.height = img.naturalHeight || 400;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);

            const link = document.createElement('a');
            link.download = 'techwave-qr-code.png';
            link.href = canvas.toDataURL('image/png');
            link.click();

            URL.revokeObjectURL(url);
        } finally {
            txt.textContent = orig;
            btn.disabled = false;
        }
    };
</script>

@endpush