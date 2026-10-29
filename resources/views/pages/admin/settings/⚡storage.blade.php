<?php

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Storage Settings')] class extends Component {
    public StorageSetting $setting;

    public string $render_source = 's3';

    public string $s3_key = '';
    public string $s3_secret = '';
    public bool $s3_secret_visible = false;
    public string $s3_region = '';
    public string $s3_bucket = '';
    public string $s3_endpoint = '';
    public string $s3_url = '';
    public bool $s3_path_style = false;

    public string $r2_key = '';
    public string $r2_secret = '';
    public bool $r2_secret_visible = false;
    public string $r2_region = 'auto';
    public string $r2_bucket = '';
    public string $r2_endpoint = '';
    public string $r2_url = '';
    public bool $r2_path_style = false;

    public function mount(): void
    {
        $this->setting = StorageSetting::current();

        $this->render_source = $this->setting->renderSource();

        $this->s3_key = $this->setting->s3_key ?? '';
        $this->s3_region = $this->setting->s3_region ?? '';
        $this->s3_bucket = $this->setting->s3_bucket ?? '';
        $this->s3_endpoint = $this->setting->s3_endpoint ?? '';
        $this->s3_url = $this->setting->s3_url ?? '';
        $this->s3_path_style = (bool) $this->setting->s3_path_style;

        $this->r2_key = $this->setting->r2_key ?? '';
        $this->r2_region = filled($this->setting->r2_region) ? strtolower(trim($this->setting->r2_region)) : 'auto';
        $this->r2_bucket = $this->setting->r2_bucket ?? '';
        $this->r2_endpoint = $this->setting->r2_endpoint ?? '';
        $this->r2_url = $this->setting->r2_url ?? '';
        $this->r2_path_style = (bool) $this->setting->r2_path_style;

        // Saved secrets enter component state only when explicitly revealed.
        // A blank secret keeps the stored one.
    }

    public function toggleSecretVisibility(string $provider): void
    {
        abort_unless(in_array(auth()->user()?->role?->value, ['admin', 'manager', 'staff', 'admin_manager'], true), 403);
        abort_unless(in_array($provider, [StorageSetting::SOURCE_S3, StorageSetting::SOURCE_R2], true), 422);

        $secret = $provider . '_secret';
        $visible = $provider . '_secret_visible';
        $stored = StorageSetting::current()->{$secret} ?? '';

        if ($this->{$visible}) {
            if ($this->{$secret} === $stored) {
                $this->{$secret} = '';
            }
            $this->{$visible} = false;
        } else {
            if (blank($this->{$secret})) {
                $this->{$secret} = $stored;
            }
            $this->{$visible} = true;
        }
    }

    protected function rules(): array
    {
        return [
            'render_source' => ['required', Rule::in([StorageSetting::SOURCE_S3, StorageSetting::SOURCE_R2])],

            's3_key' => ['nullable', 'string', 'max:255'],
            's3_secret' => ['nullable', 'string', 'max:500'],
            's3_region' => ['nullable', 'string', 'max:50'],
            's3_bucket' => ['nullable', 'string', 'max:255'],
            's3_endpoint' => ['nullable', 'url', 'max:500'],
            's3_url' => ['nullable', 'url', 'max:500'],
            's3_path_style' => ['boolean'],

            'r2_key' => ['nullable', 'string', 'max:255'],
            'r2_secret' => ['nullable', 'string', 'max:500'],
            'r2_region' => ['nullable', 'string', 'max:50'],
            'r2_bucket' => ['nullable', 'string', 'max:255'],
            'r2_endpoint' => ['nullable', 'url', 'max:500'],
            'r2_url' => ['nullable', 'url', 'max:500'],
            'r2_path_style' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $s3 = $this->effectiveS3();
        $r2 = $this->effectiveR2();

        $s3Complete = StorageSetting::s3ConfiguredFrom($s3);
        $r2Complete = StorageSetting::r2ConfiguredFrom($r2);

        if (! $s3Complete && ! $r2Complete && ($this->providerTouched('s3') || $this->providerTouched('r2'))) {
            $this->addError('render_source', 'Finish the storage provider credentials before saving, or clear the fields to stay on legacy storage.');

            return;
        }

        if ($this->render_source === StorageSetting::SOURCE_S3 && ! $s3Complete && $r2Complete) {
            $this->addError('render_source', 'Amazon S3 is selected but not fully configured. Complete the S3 fields or switch the render platform to Cloudflare R2.');

            return;
        }

        if ($this->render_source === StorageSetting::SOURCE_R2 && ! $r2Complete && $s3Complete) {
            $this->addError('render_source', 'Cloudflare R2 is selected but not fully configured. Complete the R2 fields or switch the render platform to Amazon S3.');

            return;
        }

        $this->setting->update([
            'render_source' => $this->render_source,
            ...$s3,
            ...$r2,
        ]);

        $this->s3_secret = '';
        $this->r2_secret = '';
        $this->s3_secret_visible = false;
        $this->r2_secret_visible = false;

        $this->dispatch('toast', message: 'Storage settings updated.', type: 'success');
    }

    public function testConnection(string $provider): void
    {
        if (! in_array($provider, [StorageSetting::SOURCE_S3, StorageSetting::SOURCE_R2], true)) {
            return;
        }

        $complete = $provider === StorageSetting::SOURCE_S3
            ? StorageSetting::s3ConfiguredFrom($this->effectiveS3())
            : StorageSetting::r2ConfiguredFrom($this->effectiveR2());

        if (! $complete) {
            $this->dispatch('toast', message: 'Complete all credential fields before testing.', type: 'warning');

            return;
        }

        $attributes = $provider === StorageSetting::SOURCE_S3
            ? $this->effectiveS3()
            : $this->effectiveR2();

        try {
            $probe = new StorageSetting($attributes);

            $disk = Storage::build([
                ...($provider === StorageSetting::SOURCE_S3 ? $probe->s3Config() : $probe->r2Config()),
                'root' => 'storage-probes',
                'throw' => true,
            ]);

            $path = 'probe-' . Str::uuid() . '.txt';
            $startedAt = microtime(true);

            $disk->put($path, 'techwave-storage-connection-test');
            $matches = $disk->get($path) === 'techwave-storage-connection-test';
            $disk->delete($path);

            $elapsed = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $matches) {
                $this->dispatch('toast', message: 'Connection test returned unexpected content.', type: 'error');

                return;
            }

            $this->dispatch('toast', message: strtoupper($provider) . ' connection OK (' . $elapsed . 'ms).', type: 'success');
        } catch (\Throwable $exception) {
            $this->dispatch(
                'toast',
                message: strtoupper($provider) . ' connection failed: ' . Str::limit($this->connectionErrorMessage($exception), 280),
                type: 'error',
            );
        }
    }

    /**
     * Flysystem wraps SDK failures with an empty reason, so walk the
     * previous-exception chain to surface the actual AWS/R2 error.
     */
    private function connectionErrorMessage(\Throwable $exception): string
    {
        $deepest = $exception;

        while ($deepest->getPrevious() instanceof \Throwable) {
            $deepest = $deepest->getPrevious();
        }

        $message = $deepest->getMessage();

        if (filled($message) && $message !== '') {
            if (method_exists($deepest, 'getAwsErrorCode') && filled($deepest->getAwsErrorCode())) {
                $message = '[' . $deepest->getAwsErrorCode() . '] ' . $message;
            }

            return $message;
        }

        return $exception->getMessage();
    }

    public function syncExisting(): void
    {
        if (! $this->setting->isS3Configured() && ! $this->setting->isR2Configured()) {
            $this->dispatch('toast', message: 'Configure and save a storage provider first.', type: 'warning');

            return;
        }

        Artisan::queue('storage:sync-existing');

        $this->dispatch('toast', message: 'Storage sync queued. Watch the queue worker for progress.', type: 'success');
    }

    private function effectiveS3(): array
    {
        return [
            's3_key' => filled($this->s3_key) ? $this->s3_key : null,
            's3_secret' => filled($this->s3_secret) ? $this->s3_secret : $this->setting->s3_secret,
            's3_region' => filled($this->s3_region) ? $this->s3_region : null,
            's3_bucket' => filled($this->s3_bucket) ? $this->s3_bucket : null,
            's3_endpoint' => filled($this->s3_endpoint) ? $this->s3_endpoint : null,
            's3_url' => filled($this->s3_url) ? $this->s3_url : null,
            's3_path_style' => (bool) $this->s3_path_style,
        ];
    }

    private function effectiveR2(): array
    {
        return [
            'r2_key' => filled($this->r2_key) ? $this->r2_key : null,
            'r2_secret' => filled($this->r2_secret) ? $this->r2_secret : $this->setting->r2_secret,
            'r2_region' => filled($this->r2_region) ? strtolower(trim($this->r2_region)) : 'auto',
            'r2_bucket' => filled($this->r2_bucket) ? $this->r2_bucket : null,
            'r2_endpoint' => filled($this->r2_endpoint) ? $this->r2_endpoint : null,
            'r2_url' => filled($this->r2_url) ? $this->r2_url : null,
            'r2_path_style' => (bool) $this->r2_path_style,
        ];
    }

    private function providerTouched(string $provider): bool
    {
        if ($provider === StorageSetting::SOURCE_S3) {
            return filled($this->s3_key)
                || filled($this->s3_secret)
                || filled($this->s3_region)
                || filled($this->s3_bucket)
                || filled($this->s3_endpoint);
        }

        return filled($this->r2_key)
            || filled($this->r2_secret)
            || filled($this->r2_bucket)
            || filled($this->r2_endpoint);
    }
};

?>

<div x-data="{ guideProvider: 's3' }">
    <div class="mx-auto w-full space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-h1 font-h1 text-on-surface">Storage Settings</h1>
                <p class="mt-1 text-body-md text-secondary">
                    Uploads are stored on AWS S3 and Cloudflare R2 at the same time. Choose which platform serves files on the site.
                </p>
            </div>
            <button type="button" @click="$refs.credentialsGuide.showModal()"
                aria-haspopup="dialog" aria-controls="storage-credentials-guide"
                class="inline-flex shrink-0 items-center gap-2 self-start rounded-lg border border-outline-variant bg-white px-3 py-2 text-label-md text-on-surface hover:bg-slate-50 cursor-pointer">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">help_outline</span>
                Credentials help
            </button>
        </div>

        <form wire:submit.prevent="save" class="space-y-6">
            {{-- Render source --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="mb-2 flex items-center gap-2 text-h3 font-h2">
                    Render Platform
                </h3>
                <p class="mb-6 text-body-sm font-body-sm text-secondary">
                    Every upload is written to both configured platforms. Public files load directly from the selected platform's public URL without passing through this site. Sync existing files before switching platforms.
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label @class([ 'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-5 transition' , 'border-primary bg-primary/5'=> $render_source === 's3',
                        'border-slate-200 hover:border-slate-300' => $render_source !== 's3',
                        ])>
                        <input type="radio" wire:model.live="render_source" value="s3" class="mt-1" />
                        <span>
                            <span class="block font-label-md text-on-surface">Amazon S3</span>
                            <span class="mt-1 block text-body-sm font-body-sm text-secondary">
                                {{ $setting->isS3Configured() ? 'Credentials saved.' : 'Not configured yet.' }}
                            </span>
                        </span>
                    </label>

                    <label @class([ 'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-5 transition' , 'border-primary bg-primary/5'=> $render_source === 'r2',
                        'border-slate-200 hover:border-slate-300' => $render_source !== 'r2',
                        ])>
                        <input type="radio" wire:model.live="render_source" value="r2" class="mt-1" />
                        <span>
                            <span class="block font-label-md text-on-surface">Cloudflare R2</span>
                            <span class="mt-1 block text-body-sm font-body-sm text-secondary">
                                {{ $setting->isR2Configured() ? 'Credentials saved.' : 'Not configured yet.' }}
                            </span>
                        </span>
                    </label>
                </div>

                @error('render_source')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                {{-- AWS S3 --}}
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="mb-8 flex items-center justify-between">

                        <div class="flex items-center gap-3">
                            <img
                                src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/aws/default.svg"
                                alt="AWS"
                                width="35"
                                height="35" />
                            <h3 class="text-h3 font-h2">
                                AWS S3
                            </h3>
                        </div>
                        <span @class([ 'rounded-full px-3 py-1 text-xs font-semibold' , 'bg-green-100 text-green-700'=> $setting->isS3Configured(),
                            'bg-slate-100 text-slate-500' => ! $setting->isS3Configured(),
                            ])>
                            {{ $setting->isS3Configured() ? 'Configured' : 'Not configured' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Access Key ID<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="s3_key" autocomplete="off"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="AKIA..." />
                            @error('s3_key')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Secret Access Key<span class="text-red-500 relative -top-1">*</span></label>
                            <div class="flex items-stretch gap-2">
                                @if (! $s3_secret_visible && blank($s3_secret) && filled($setting->s3_secret))
                                <input id="s3-secret" wire:key="s3-secret-masked" type="text" value="****************" readonly
                                    aria-label="Amazon S3 secret access key, hidden" title="Click the eye icon to view or edit the saved key"
                                    class="min-w-0 flex-1 rounded border border-outline-variant px-4 py-2.5" />
                                @else
                                <input id="s3-secret" wire:key="s3-secret-editable" type="{{ $s3_secret_visible ? 'text' : 'password' }}" wire:model.blur="s3_secret" autocomplete="new-password"
                                    class="min-w-0 flex-1 rounded border border-outline-variant px-4 py-2.5"
                                    placeholder="Enter secret key" />
                                @endif
                                <button type="button" wire:click="toggleSecretVisibility('s3')" wire:loading.attr="disabled" wire:target="toggleSecretVisibility"
                                    aria-controls="s3-secret" aria-label="{{ $s3_secret_visible ? 'Hide Amazon S3 secret access key' : 'Show Amazon S3 secret access key' }}" aria-pressed="{{ $s3_secret_visible ? 'true' : 'false' }}"
                                    title="{{ $s3_secret_visible ? 'Hide secret key' : 'Show secret key' }}"
                                    class="inline-flex w-10 shrink-0 items-center justify-center rounded bg-slate-100 text-secondary hover:bg-slate-200 hover:text-on-surface focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50 cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ $s3_secret_visible ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            @error('s3_secret')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Region<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="s3_region"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="ap-southeast-1" />
                            @error('s3_region')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Bucket<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="s3_bucket"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="my-bucket" />
                            @error('s3_bucket')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Custom Endpoint</label>
                            <input type="url" wire:model.blur="s3_endpoint"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="Leave empty for standard AWS S3" />
                            @error('s3_endpoint')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Public URL / CDN</label>
                            <input type="url" wire:model.blur="s3_url"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="https://cdn.example.com" />
                            @error('s3_url')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model.blur="s3_path_style" />
                                <span class="text-body-sm font-body-sm text-on-surface">Use path-style endpoint</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="button" wire:click="testConnection('s3')" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50 cursor-pointer">
                            <span wire:loading.remove wire:target="testConnection('s3')">Test Connection</span>
                            <span wire:loading wire:target="testConnection('s3')" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-primary"></span>
                                Testing...
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Cloudflare R2 --}}
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="mb-8 flex items-center justify-between">
                        <h3 class="flex items-center gap-3 text-h3 font-h2">
                            <svg viewBox="0 0 256 116" xmlns="http://www.w3.org/2000/svg" class="size-10" preserveAspectRatio="xMidYMid">
                                <path fill="#FFF" d="m202.357 49.394-5.311-2.124C172.085 103.434 72.786 69.289 66.81 85.997c-.996 11.286 54.227 2.146 93.706 4.059 12.039.583 18.076 9.671 12.964 24.484l10.069.031c11.615-36.209 48.683-17.73 50.232-29.68-2.545-7.857-42.601 0-31.425-35.497Z" />
                                <path fill="#F4811F" d="M176.332 108.348c1.593-5.31 1.062-10.622-1.593-13.809-2.656-3.187-6.374-5.31-11.154-5.842L71.17 87.634c-.531 0-1.062-.53-1.593-.53-.531-.532-.531-1.063 0-1.594.531-1.062 1.062-1.594 2.124-1.594l92.946-1.062c11.154-.53 22.839-9.56 27.087-20.182l5.312-13.809c0-.532.531-1.063 0-1.594C191.203 20.182 166.772 0 138.091 0 111.535 0 88.697 16.995 80.73 40.896c-5.311-3.718-11.684-5.843-19.12-5.31-12.747 1.061-22.838 11.683-24.432 24.43-.531 3.187 0 6.374.532 9.56C16.996 70.107 0 87.103 0 108.348c0 2.124 0 3.718.531 5.842 0 1.063 1.062 1.594 1.594 1.594h170.489c1.062 0 2.125-.53 2.125-1.594l1.593-5.842Z" />
                                <path fill="#FAAD3F" d="M205.544 48.863h-2.656c-.531 0-1.062.53-1.593 1.062l-3.718 12.747c-1.593 5.31-1.062 10.623 1.594 13.809 2.655 3.187 6.373 5.31 11.153 5.843l19.652 1.062c.53 0 1.062.53 1.593.53.53.532.53 1.063 0 1.594-.531 1.063-1.062 1.594-2.125 1.594l-20.182 1.062c-11.154.53-22.838 9.56-27.087 20.182l-1.063 4.78c-.531.532 0 1.594 1.063 1.594h70.108c1.062 0 1.593-.531 1.593-1.593 1.062-4.25 2.124-9.03 2.124-13.81 0-27.618-22.838-50.456-50.456-50.456" />
                            </svg>
                            Cloudflare R2
                        </h3>
                        <span @class([ 'rounded-full px-3 py-1 text-xs font-semibold' , 'bg-green-100 text-green-700'=> $setting->isR2Configured(),
                            'bg-slate-100 text-slate-500' => ! $setting->isR2Configured(),
                            ])>
                            {{ $setting->isR2Configured() ? 'Configured' : 'Not configured' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Access Key ID<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="r2_key" autocomplete="off"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="R2 access key id" />
                            @error('r2_key')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Secret Access Key<span class="text-red-500 relative -top-1">*</span></label>
                            <div class="flex items-stretch gap-2">
                                @if (! $r2_secret_visible && blank($r2_secret) && filled($setting->r2_secret))
                                <input id="r2-secret" wire:key="r2-secret-masked" type="text" value="****************" readonly
                                    aria-label="Cloudflare R2 secret access key, hidden" title="Click the eye icon to view or edit the saved key"
                                    class="min-w-0 flex-1 rounded border border-outline-variant px-4 py-2.5" />
                                @else
                                <input id="r2-secret" wire:key="r2-secret-editable" type="{{ $r2_secret_visible ? 'text' : 'password' }}" wire:model.blur="r2_secret" autocomplete="new-password"
                                    class="min-w-0 flex-1 rounded border border-outline-variant px-4 py-2.5"
                                    placeholder="Enter secret key" />
                                @endif
                                <button type="button" wire:click="toggleSecretVisibility('r2')" wire:loading.attr="disabled" wire:target="toggleSecretVisibility"
                                    aria-controls="r2-secret" aria-label="{{ $r2_secret_visible ? 'Hide Cloudflare R2 secret access key' : 'Show Cloudflare R2 secret access key' }}" aria-pressed="{{ $r2_secret_visible ? 'true' : 'false' }}"
                                    title="{{ $r2_secret_visible ? 'Hide secret key' : 'Show secret key' }}"
                                    class="inline-flex w-10 shrink-0 items-center justify-center rounded bg-slate-100 text-secondary hover:bg-slate-200 hover:text-on-surface focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50 cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ $r2_secret_visible ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </div>
                            @error('r2_secret')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Region<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="r2_region"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="auto" />
                            @error('r2_region')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Bucket<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="text" wire:model.blur="r2_bucket"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="my-r2-bucket" />
                            @error('r2_bucket')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Endpoint<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="url" wire:model.blur="r2_endpoint"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="https://<account_id>.r2.cloudflarestorage.com" />
                            @error('r2_endpoint')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="block font-label-md text-on-surface">Public URL / Custom Domain<span class="text-red-500 relative -top-1">*</span></label>
                            <input type="url" wire:model.blur="r2_url"
                                class="w-full rounded border border-outline-variant px-4 py-2.5"
                                placeholder="https://pub-xxxx.r2.dev or your domain" />
                            @error('r2_url')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model.blur="r2_path_style" />
                                <span class="text-body-sm font-body-sm text-on-surface">Use path-style endpoint</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="button" wire:click="testConnection('r2')" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50 cursor-pointer">
                            <span wire:loading.remove wire:target="testConnection('r2')">Test Connection</span>
                            <span wire:loading wire:target="testConnection('r2')" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-primary"></span>
                                Testing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Existing files --}}
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="flex items-center gap-2 text-h3 font-h2">
                            <span class="material-symbols-outlined text-primary">sync</span>
                            Existing Files
                        </h3>
                        <p class="mt-1 text-body-sm font-body-sm text-secondary">
                            Copies files that already exist (from the previous storage disk or the other cloud) to every configured provider. Safe to run repeatedly — files that already exist are skipped.
                        </p>
                    </div>

                    <button type="button" wire:click="syncExisting" wire:loading.attr="disabled"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-outline-variant px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50 cursor-pointer">
                        <span wire:loading.remove wire:target="syncExisting">Sync Existing Files</span>
                        <span wire:loading wire:target="syncExisting" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-primary"></span>
                            Queuing...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Save --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex justify-end">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 cursor-pointer">
                        <span wire:loading.remove wire:target="save">Save Settings</span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <dialog id="storage-credentials-guide" x-ref="credentialsGuide" aria-labelledby="storage-credentials-title"
        @click="if ($event.target === $refs.credentialsGuide) $refs.credentialsGuide.close()"
        class="m-auto max-h-[85vh] w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-slate-200 bg-white p-0 text-on-surface shadow-xl backdrop:bg-black/40">
        <div class="flex max-h-[85vh] flex-col">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 id="storage-credentials-title" class="text-h3 font-h2">Storage credentials</h2>
                    <p class="mt-1 text-body-sm text-secondary">Where to find each AWS S3 and Cloudflare R2 setting.</p>
                </div>
                <button type="button" @click="$refs.credentialsGuide.close()" aria-label="Close credentials help"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg hover:bg-slate-100 cursor-pointer">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="flex gap-2 border-b border-slate-200 px-6 py-3">
                <button type="button" @click="guideProvider = 's3'" :aria-pressed="guideProvider === 's3'"
                    :class="guideProvider === 's3' ? 'border-primary bg-primary/5 text-primary' : 'border-slate-200 text-secondary hover:bg-slate-50'"
                    class="flex-1 rounded-lg border px-3 py-2 text-label-md cursor-pointer">
                    AWS S3 setup
                </button>
                <button type="button" @click="guideProvider = 'r2'" :aria-pressed="guideProvider === 'r2'"
                    :class="guideProvider === 'r2' ? 'border-primary bg-primary/5 text-primary' : 'border-slate-200 text-secondary hover:bg-slate-50'"
                    class="flex-1 rounded-lg border px-3 py-2 text-label-md cursor-pointer">
                    Cloudflare R2 setup
                </button>
            </div>
            @php
            $credentialsGuide = file_get_contents(base_path('storage-setup-instructions.txt'));
            $s3Guide = 'AWS S3 - Access Key ID'.Str::before(Str::after($credentialsGuide, 'AWS S3 - Access Key ID'), 'Cloudflare R2 - Access Key ID');
            $r2Guide = 'Cloudflare R2 - Access Key ID'.Str::before(Str::after($credentialsGuide, 'Cloudflare R2 - Access Key ID'), 'Official references:');
            @endphp
            <div class="overflow-y-auto px-6 py-5 text-body-sm leading-relaxed">
                <section x-show="guideProvider === 's3'" aria-label="AWS S3 setup instructions">
                    <div class="whitespace-pre-wrap break-words">{{ trim($s3Guide) }}</div>
                    <a href="https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html" target="_blank" rel="noopener" class="mt-4 inline-block text-primary hover:underline">AWS credentials documentation</a>
                </section>
                <section x-show="guideProvider === 'r2'" aria-label="Cloudflare R2 setup instructions">
                    <div class="whitespace-pre-wrap break-words">{{ trim($r2Guide) }}</div>
                    <div class="mt-4 flex flex-wrap gap-4">
                        <a href="https://developers.cloudflare.com/r2/api/tokens/" target="_blank" rel="noopener" class="text-primary hover:underline">R2 credentials documentation</a>
                        <a href="https://developers.cloudflare.com/r2/buckets/public-buckets/" target="_blank" rel="noopener" class="text-primary hover:underline">R2 public URL documentation</a>
                    </div>
                </section>
            </div>
        </div>
    </dialog>
</div>
