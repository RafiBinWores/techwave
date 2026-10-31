<?php

use App\Models\NetworkLicense;
use App\Models\NetworkLicenseTrial;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Services\NetworkLicensing;
use App\Services\Products\ProductDownloads;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Download NetWatch | Techwave')] class extends Component
{
    #[Locked]
    public Product $product;

    #[Locked]
    public string $activationKey = '';

    #[Locked]
    public bool $autoDownload = false;

    public function mount(string $slug): void
    {
        abort_unless(auth()->check(), 403);
        $this->product = Product::published()->where('slug', $slug)->firstOrFail();
        abort_unless(app(NetworkLicensing::class)->isProduct($this->product)
            && app(ProductDownloads::class)->available($this->product), 404);
        $prepared = session()->pull('netwatch_download_prepared');
        if (is_array($prepared) && ($prepared['user_id'] ?? null) === auth()->id()
            && ($prepared['product_id'] ?? null) === $this->product->id
            && ($prepared['prepared_at'] ?? 0) >= now()->subMinutes(5)->timestamp) {
            $key = $prepared['activation_key'] ?? '';
            $license = NetworkLicense::where('user_id', auth()->id())->where('product_id', $this->product->id)->first();
            if ($license && ($key === '' || hash_equals($license->key_hash ?? '', hash('sha256', $key)))) {
                $this->activationKey = $key;
                $this->autoDownload = true;
            }
        }
    }

    public function prepareDownload(): void
    {
        abort_unless(auth()->check(), 403);
        $product = Product::published()->whereKey($this->product->id)->firstOrFail();
        $prepared = app(NetworkLicensing::class)->prepareDownload($product);
        $this->activationKey = $prepared['key'];
        $this->autoDownload = true;
    }

    public function with(): array
    {
        $license = NetworkLicense::with(['purchase', 'trial'])->where('user_id', auth()->id())
            ->where('product_id', $this->product->id)->first();
        $trial = NetworkLicenseTrial::where('user_id', auth()->id())->first();
        $paid = ProductPurchase::where('user_id', auth()->id())->where('product_id', $this->product->id)
            ->whereNotNull('invoice_id')->whereNotNull('verified_at');
        $activePaid = (clone $paid)->where('status', 'active')->where('expires_at', '>', now())
            ->whereIn('billing_cycle', ['monthly', 'yearly'])->exists();
        $usable = false;
        if ($license) {
            try {
                app(NetworkLicensing::class)->limits($license);
                $usable = true;
            } catch (ValidationException $exception) {
                // Display the purchase/trial state instead of exposing an unusable installer link.
            }
        }
        $trialAvailable = ! $trial && ! $license?->revoked_at;

        return [
            'trial' => $trial, 'license' => $license, 'activePaid' => $activePaid,
            'usable' => $usable, 'trialAvailable' => $trialAvailable,
            'canPrepare' => ! $license?->revoked_at && ($trialAvailable || $activePaid || $usable),
        ];
    }
};
?>

<section class="px-4 py-10 text-white sm:px-6 sm:py-14">
    <div class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-blue-950/30 backdrop-blur-2xl">
        <div class="border-b border-white/10 p-6 sm:p-8">
            <a href="{{ route('client.products.show', $product->slug) }}" wire:navigate class="text-xs font-semibold uppercase tracking-widest text-cyan-300">{{ $product->name }}</a>
            <h1 class="mt-3 text-3xl font-bold">Download NetWatch</h1>
            <p class="mt-3 text-sm leading-7 text-blue-100/65">Your activation key and installer are linked to your Techwave account.</p>
        </div>
        <div class="space-y-6 p-6 sm:p-8">
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-300/20 bg-red-400/10 p-4 text-sm text-red-200">
                    @foreach ($errors->all() as $message) <p>{{ $message }}</p> @endforeach
                </div>
            @endif

            @if ($license?->revoked_at)
                <p role="alert" class="rounded-xl border border-red-300/20 bg-red-400/10 p-4 text-sm leading-7 text-red-200">Your license was revoked. Contact support before generating another key or activating NetWatch.</p>
            @elseif ($activePaid)
                <div class="rounded-2xl border border-cyan-300/20 bg-cyan-300/5 p-5">
                    <h2 class="text-xl font-bold">Your paid subscription is active</h2>
                    <p class="mt-2 text-sm leading-7 text-blue-100/65">Use your subscription's saved limits and expiry. Downloading again will not change your paid period.</p>
                </div>
            @elseif ($trialAvailable)
                <div class="rounded-2xl border border-cyan-300/20 bg-cyan-300/5 p-5">
                    <h2 class="text-xl font-bold">7-day free trial</h2>
                    <p class="mt-3 text-sm leading-7 text-blue-100/70">Try NetWatch with unlimited sensors and one installation. The seven days start when you generate your trial activation key below.</p>
                    <p class="mt-2 text-xs leading-6 text-blue-100/55">One trial per customer. Reinstalling, downloading again, or creating another key will not restart it.</p>
                </div>
            @elseif ($trial && !$license?->product_purchase_id)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="text-xl font-bold">{{ $trial->expires_at->isFuture() ? 'Your free trial is active' : 'Your free trial has expired' }}</h2>
                    <p class="mt-3 text-sm text-blue-100/70">Trial expiry: <strong class="text-white">{{ $trial->expires_at->format('d M Y, h:i A T') }}</strong></p>
                    <p class="mt-3 text-sm leading-7 text-blue-100/65">{{ $trial->expires_at->isFuture() ? 'Your trial includes unlimited sensors and one installation.' : 'NetWatch will no longer activate or run licensed monitoring on the expired trial. Choose a paid subscription to continue.' }}</p>
                </div>
            @else
                <p class="rounded-xl border border-amber-300/20 bg-amber-300/10 p-4 text-sm leading-7 text-amber-100">Your license is inactive or revoked. Renew your subscription or contact our team to continue.</p>
            @endif

            @if ($activationKey)
                <div class="rounded-2xl border border-cyan-300/25 bg-slate-950/30 p-5">
                    <h2 class="text-lg font-bold">Activation key</h2>
                    <p class="mt-2 text-sm leading-7 text-blue-100/65">Copy this key now and enter it in NetWatch after installation. Only its hash is stored, so this key cannot be displayed again after leaving this page.</p>
                    <code class="mt-4 block break-all rounded-xl border border-white/10 bg-white/5 p-4 font-mono text-sm text-cyan-200">{{ $activationKey }}</code>
                    <button type="button" x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText(@js($activationKey)).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                        class="mt-3 rounded-lg border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                        <span x-text="copied ? 'Copied' : 'Copy activation key'">Copy activation key</span>
                    </button>
                </div>
            @endif

            @if ($autoDownload && $usable)
                <div wire:key="installer-start-{{ $product->id }}" x-data
                    x-init="window.location.assign(@js(route('client.products.installer', $product->slug)))"
                    role="status" class="rounded-xl border border-cyan-300/20 bg-cyan-300/5 p-4 text-sm text-cyan-100">
                    Your download is starting. If your browser does not start it, use Download installer below.
                </div>
            @endif
            <div class="flex flex-wrap items-center gap-3">
                @if ($usable)
                    <a href="{{ route('client.products.installer', $product->slug) }}" class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-sky-400 px-5 py-3 text-sm font-semibold">
                        <span class="material-symbols-outlined text-lg">download</span>Download installer
                    </a>
                @endif
                @if ($canPrepare && !$activationKey)
                    <button type="button" wire:click="prepareDownload" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl border border-cyan-300/25 bg-cyan-300/10 px-5 py-3 text-sm font-semibold text-cyan-100 disabled:opacity-50">
                        <span wire:loading.remove wire:target="prepareDownload">{{ $trialAvailable ? 'Start 7-day trial & get activation key' : 'Get activation key & download' }}</span>
                        <span wire:loading wire:target="prepareDownload">Preparing your activation key…</span>
                    </button>
                @endif
                <a href="{{ route('client.products.show', $product->slug) }}#pricing" wire:navigate class="rounded-xl border border-white/10 px-5 py-3 text-sm font-semibold text-blue-100">View paid plans</a>
                <a href="{{ route('account.products') }}" wire:navigate class="text-sm text-blue-100/60">My Products</a>
            </div>
        </div>
    </div>
</section>
