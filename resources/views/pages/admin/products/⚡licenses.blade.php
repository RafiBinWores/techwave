<?php

use App\Models\NetworkInstallation;
use App\Models\NetworkLicense;
use App\Services\NetworkLicensing;
use App\Services\Products\ProductContent;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin-app')] #[Title('NetWatch Licenses')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Locked]
    public string $activationKey = '';

    #[Locked]
    public ?int $viewing = null;

    public function boot(): void
    {
        ProductContent::authorizeAdmin();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        // Pick up already-paid subscriptions as well as new purchases; no separate product setup is required.
        app(NetworkLicensing::class)->syncActiveSubscriptions();
    }

    public function generateKey(int $id): void
    {
        $this->activationKey = app(NetworkLicensing::class)->rotateKey(NetworkLicense::findOrFail($id));
    }

    public function closeKey(): void
    {
        $this->activationKey = '';
    }

    public function revoke(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $license = NetworkLicense::whereKey($id)->lockForUpdate()->firstOrFail();
            $license->update(['revoked_at' => now()]);
            app(NetworkLicensing::class)->record($license, 'revoked', [], auth()->id());
        });
        $this->dispatch('toast', message: 'License revoked. Connected installations are blocked on their next license check.', type: 'success');
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $license = NetworkLicense::whereKey($id)->lockForUpdate()->firstOrFail();
            $license->update(['revoked_at' => null]);
            app(NetworkLicensing::class)->limits($license);
            app(NetworkLicensing::class)->record($license, 'restored', [], auth()->id());
        });
        $this->dispatch('toast', message: 'License restored.', type: 'success');
    }

    public function showInstallations(int $id): void
    {
        NetworkLicense::findOrFail($id);
        $this->viewing = $id;
    }

    public function closeInstallations(): void
    {
        $this->viewing = null;
    }

    public function resetInstallation(int $id): void
    {
        $installation = NetworkInstallation::findOrFail($id);
        DB::transaction(function () use ($installation): void {
            $license = NetworkLicense::whereKey($installation->network_license_id)->lockForUpdate()->firstOrFail();
            $installation->update(['credential_hash' => null, 'deactivated_at' => now()]);
            app(NetworkLicensing::class)->record($license, 'installation_reset', ['installation_id' => $installation->installation_id], auth()->id());
        });
        $this->dispatch('toast', message: 'Installation reset. The customer must activate it again.', type: 'success');
    }

    public function with(): array
    {
        return [
            'signingReady' => is_file(config('network-licenses.private_key_path')),
            'licenses' => NetworkLicense::with(['user', 'product', 'purchase', 'trial'])
                ->withCount(['installations as active_installations_count' => fn ($query) => $query->whereNull('deactivated_at')])
                ->when($this->search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
                    ->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
                ->latest()->paginate(15),
            'viewingLicense' => $this->viewing ? NetworkLicense::with(['installations', 'events' => fn ($query) => $query->limit(15)])->find($this->viewing) : null,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">NetWatch Licenses</h1>
            <p class="mt-2 text-sm text-secondary">Manage activation keys and installations using each customer's verified product subscription.</p>
        </div>
        <a href="{{ route('admin.products.licenses.public-key') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-primary">Download verification key</a>
    </div>
    @if (!$signingReady)
        <p role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Signing is not configured. Run <code>php artisan netwatch:keys</code> before activating installations.</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">
            @foreach ($errors->all() as $message) <p>{{ $message }}</p> @endforeach
        </div>
    @endif



    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <input type="search" wire:model.live.debounce.400ms="search" aria-label="Search license customers" placeholder="Search customer name or email" class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm sm:w-80">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-secondary">
                    <tr>
                        <th class="px-5 py-4">Customer / Product</th>
                        <th class="px-5 py-4">Subscription / Limits</th>
                        <th class="px-5 py-4">License</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($licenses as $license)
                        @php
                            $limitKeys = $license->purchase ? app(NetworkLicensing::class)->limitKeys($license->purchase) : [];
                            $paidLimits = $license->purchase?->limits ?? [];
                            $trial = !$license->product_purchase_id ? $license->trial : null;
                        @endphp
                        <tr wire:key="network-license-{{ $license->id }}">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $license->user?->name }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $license->user?->email }}</p>
                                <p class="mt-2 text-sm">{{ $license->purchase?->product_name ?? $license->product?->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p>{{ $license->purchase?->plan_name ?? ($trial ? '7-day free trial' : 'No active purchase') }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ ($license->purchase?->expires_at ?? $trial?->expires_at)?->format('d M Y, h:i A') ?? 'Unavailable' }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $license->active_installations_count }} active installation(s)</p>
                                @if ($trial)
                                    <p class="mt-1 text-xs text-secondary">Sensors: Unlimited</p>
                                    <p class="mt-1 text-xs text-secondary">Installations: {{ $trial->limits['installation_limit'] }}</p>
                                @endif
                                @if ($limitKeys)
                                    @foreach (['Sensors' => $limitKeys['sensor_limit'], 'Installations' => $limitKeys['installation_limit'], 'Devices' => $limitKeys['device_limit']] as $label => $limitKey)
                                        @if ($limitKey)
                                            <p class="mt-1 text-xs text-secondary">{{ $label }}: {{ !empty($paidLimits[$limitKey]['unlimited']) ? 'Unlimited' : ($paidLimits[$limitKey]['amount'] ?? 'Not set') }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $license->revoked_at ? 'Revoked' : ($license->purchase?->displayStatus() ?? ($trial ? ($trial->expires_at->isFuture() ? 'Trial active' : 'Trial expired') : 'Inactive')) }}</p>
                                <p class="mt-1 font-mono text-xs text-secondary">{{ $license->key_hint ? '••••••'.$license->key_hint : 'Key not generated' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" wire:click="generateKey({{ $license->id }})"
                                        wire:confirm="Generate a new activation key? Previous keys will stop working for new activations. Installed machines retain their credentials."
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-primary">
                                        Generate key
                                    </button>
                                    <button type="button" wire:click="showInstallations({{ $license->id }})"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold">
                                        Installations
                                    </button>
                                    @if ($license->revoked_at)
                                        <button type="button" wire:click="restore({{ $license->id }})"
                                            wire:confirm="Restore this license if its subscription is active?"
                                            class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700">
                                            Restore
                                        </button>
                                    @else
                                        <button type="button" wire:click="revoke({{ $license->id }})"
                                            wire:confirm="Revoke this license? NetWatch will block it on the next license check."
                                            class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600">
                                            Revoke
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-secondary">No verified NetWatch subscriptions yet. Licenses appear automatically after payment verification.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $licenses->links() }}</div>
    </div>

    @if ($activationKey)
        <dialog aria-label="Activation key" wire:ignore.self x-data x-init="$el.showModal()" @cancel.prevent="$wire.closeKey()" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm">
            <h2 class="text-xl font-bold">Activation key</h2>
            <p class="mt-3 text-sm leading-6 text-secondary">Copy this key now and give it to the customer. Only its hash is stored, so it cannot be displayed again.</p>
            <code class="mt-4 block break-all rounded-xl bg-slate-100 p-4 text-sm">{{ $activationKey }}</code>
            <div class="mt-5 text-right"><button type="button" wire:click="closeKey" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Done</button></div>
        </dialog>
    @endif

    @if ($viewingLicense)
        <dialog aria-label="License installations" wire:key="license-installations-{{ $viewing }}" wire:ignore.self x-data x-init="$el.showModal()" @cancel.prevent="$wire.closeInstallations()" class="m-auto max-h-[calc(100dvh-3rem)] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm">
            <div class="flex items-center justify-between gap-4"><h2 class="text-xl font-bold">License #{{ $viewing }} installations</h2><button type="button" wire:click="closeInstallations" aria-label="Close installations"><span class="material-symbols-outlined">close</span></button></div>
            <div class="mt-5 space-y-3">
                @forelse ($viewingLicense->installations as $installation)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                        <div><p class="break-all font-mono text-xs">{{ $installation->installation_id }}</p><p class="mt-2 text-xs text-secondary">{{ $installation->deactivated_at ? 'Deactivated' : 'Active' }} · Last check: {{ $installation->last_verified_at?->format('d M Y, h:i A') ?? 'Never' }}</p></div>
                        @unless ($installation->deactivated_at)
                            <button type="button" wire:click="resetInstallation({{ $installation->id }})" wire:confirm="Reset this installation and release its activation slot?" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600">Reset installation</button>
                        @endunless
                    </div>
                @empty
                    <p class="text-sm text-secondary">No installations have been activated.</p>
                @endforelse
            </div>
            <h3 class="mt-6 text-sm font-bold">Recent activity</h3>
            <ul class="mt-3 space-y-2 text-xs text-secondary">@foreach ($viewingLicense->events as $event)<li>{{ $event->created_at->format('d M Y, h:i A') }} · {{ str_replace('_', ' ', $event->action) }}</li>@endforeach</ul>
        </dialog>
    @endif
</div>
