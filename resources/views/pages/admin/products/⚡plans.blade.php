<?php

use App\Models\Product;
use App\Models\ProductPlan;
use App\Services\Products\ProductContent;
use App\Services\Products\ProductPricing;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Pricing plans')] class extends Component
{
    #[Locked]
    public Product $product;

    #[Locked]
    public ?int $editing = null;

    #[Locked]
    public ?int $deleting = null;

    #[Locked]
    public string $deletingName = '';

    public bool $showForm = false;

    public string $name = '';

    public string $badge = '';

    public string $features_text = '';

    public string $pricing_mode = 'subscription';

    public bool $monthly_enabled = true;

    public bool $yearly_enabled = false;

    public string $monthly_price = '';

    public string $yearly_price = '';

    public string $one_time_price = '';

    public string $monthly_checkout_url = '';

    public string $yearly_checkout_url = '';

    public string $one_time_checkout_url = '';

    public bool $is_active = true;

    public $sort_order = 0;

    public array $limits = [];

    public function boot(): void
    {
        ProductContent::authorizeAdmin();
    }

    public function create(): void
    {
        $this->resetExcept('product');
        $this->resetValidation();
        $this->showForm = true;
    }

    /**
     * Load the saved plan into the compact editor.
     */
    public function edit(int $id): void
    {
        $this->create();
        $plan = $this->product->plans()->findOrFail($id);
        $this->editing = $plan->id;
        foreach (['name', 'badge', 'pricing_mode', 'monthly_checkout_url', 'yearly_checkout_url', 'one_time_checkout_url'] as $field) {
            $this->$field = $plan->$field ?? '';
        }
        $prices = $plan->effectivePricing();
        $this->monthly_enabled = $prices['monthly'] !== null;
        $this->yearly_enabled = $prices['yearly'] !== null;
        $this->monthly_price = $prices['monthly'] ?? '';
        $this->yearly_price = $prices['yearly'] ?? '';
        $this->one_time_price = $prices['one_time'] ?? '';
        $this->features_text = implode("\n", $plan->features ?? []);
        $this->limits = $plan->limits ?? [];
        $this->is_active = $plan->is_active;
        $this->sort_order = $plan->sort_order;
    }

    public function closeEditor(): void
    {
        $this->showForm = false;
        $this->editing = null;
        $this->resetValidation();
    }

    public function requestDelete(int $id): void
    {
        ProductContent::authorizeAdmin();
        $plan = Product::findOrFail($this->product->id)->plans()->findOrFail($id);
        $this->deleting = $plan->id;
        $this->deletingName = $plan->name;
    }

    public function cancelDelete(): void
    {
        $this->reset('deleting', 'deletingName');
    }

    public function updatedLimits(mixed $value, string $key): void
    {
        if (str_ends_with($key, '.unlimited') && $value) {
            $limitKey = substr($key, 0, -strlen('.unlimited'));
            $this->limits[$limitKey]['amount'] = '';
        }
    }

    public function save(): void
    {
        ProductContent::authorizeAdmin();
        $product = Product::findOrFail($this->product->id);
        $this->validate([
            'pricing_mode' => ['required', Rule::in(['subscription', 'one_time', 'quotation'])],
            'monthly_enabled' => 'boolean',
            'yearly_enabled' => 'boolean',
        ]);
        $plan = $this->editing ? $product->plans()->findOrFail($this->editing) : null;
        app(ProductPricing::class)->savePlan($product, [
            'name' => $this->name,
            'badge' => $this->badge,
            'features' => array_values(array_filter(
                array_map('trim', explode("\n", $this->features_text)),
                fn ($text) => $text !== '',
            )),
            'pricing_mode' => $this->pricing_mode,
            'monthly_price' => $this->monthly_enabled ? $this->monthly_price : null,
            'yearly_price' => $this->yearly_enabled ? $this->yearly_price : null,
            'one_time_price' => $this->one_time_price,
            'monthly_checkout_url' => $this->monthly_enabled ? $this->monthly_checkout_url : null,
            'yearly_checkout_url' => $this->yearly_enabled ? $this->yearly_checkout_url : null,
            'one_time_checkout_url' => $this->one_time_checkout_url,
            'limits' => $this->limits,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            // Saving this admin form confirms the requested pricing update.
            'confirm_pricing_change' => true,
        ], $plan);
        $this->closeEditor();
        $this->dispatch('toast', message: 'Pricing plan saved successfully.', type: 'success');
    }

    public function delete(int $id): void
    {
        ProductContent::authorizeAdmin();
        Product::findOrFail($this->product->id)->plans()->findOrFail($id)->delete();
        if ($this->editing === $id) {
            $this->closeEditor();
        }
        $this->cancelDelete();
        $this->dispatch('toast', message: 'Plan deleted. Existing billing and license records have been retained.', type: 'success');
    }

    public function with(): array
    {
        return [
            'plans' => $this->product->plans()->with('product', 'toolPlan')->get(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Existing plan cards and the compact create/edit modal. --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <a
                href="{{ route('admin.products.edit', $product) }}"
                wire:navigate
                class="inline-flex items-center gap-1 text-sm text-secondary hover:text-primary"
            >
                <span class="material-symbols-outlined text-base">
                    arrow_back
                </span>
                {{ $product->name }}
            </a>
            <h1 class="mt-2 text-h1 font-h1 text-on-surface">
                Pricing plans
            </h1>
            <p class="mt-1 text-sm text-secondary">
                Create the right tiers, billing periods and allowances for this software.
            </p>
        </div>
        <button
            type="button"
            wire:click="create"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white"
        >
            <span class="material-symbols-outlined text-lg">
                add
            </span>
            Add plan
        </button>
    </div>
    @unless ($product->pricing_enabled)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Pricing is hidden for this product. You can prepare plans here and enable pricing in the product settings.
        </div>
    @endunless
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($plans as $plan)
            <section
                wire:key="plan-{{ $plan->id }}"
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <span
                        @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700'=> $plan->is_active, 'bg-slate-100 text-slate-600' => !$plan->is_active])
                    >
                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if ($plan->badge)
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-primary">
                            {{ $plan->badge }}
                        </span>
                    @endif
                </div>
                <h2 class="mt-5 font-manrope text-xl font-bold">
                    {{ $plan->name }}
                </h2>
                <div class="my-5 space-y-2 border-y border-slate-100 py-5">
                    @foreach ($plan->effectivePricing() as $cycle => $price)
                        @if ($price !== null)
                            <p>
                                <span class="text-2xl font-bold text-on-surface">
                                    ৳{{ number_format((float) $price, 2) }}
                                </span>
                                <span class="ml-1 text-sm text-secondary">
                                    {{ ['monthly' => '/ month', 'yearly' => '/ year', 'one_time' => 'one-time'][$cycle] }}
                                </span>
                            </p>
                        @endif
                    @endforeach
                    @if ($plan->pricing_mode === 'quotation')
                        <p class="text-sm text-secondary">
                            Custom subscription · Contact us
                        </p>
                    @endif
                </div>
                <dl class="space-y-2 text-sm">
                    @foreach ($plan->effectiveLimits() as $limit)
                        <div class="flex justify-between gap-3">
                            <dt class="text-secondary">
                                {{ $limit['label'] }}
                            </dt>
                            <dd class="font-semibold">
                                {{ !empty($limit['unlimited']) ? 'Unlimited' : number_format($limit['amount']) }} {{ $limit['unit'] ?? '' }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
                @if ($plan->features)
                    <ul class="mt-4 space-y-2 text-sm text-secondary">
                        @foreach ($plan->features as $feature)
                            <li class="flex gap-2">
                                <span class="material-symbols-outlined text-base text-emerald-600">
                                    check
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-auto flex items-center justify-between gap-3 pt-6">
                    <span class="text-xs text-secondary">
                        Order: {{ $plan->sort_order }}
                    </span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="edit({{ $plan->id }})"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium hover:bg-slate-50"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            wire:click="requestDelete({{ $plan->id }})"
                            aria-label="Delete {{ $plan->name }}"
                            class="rounded-lg p-2 text-red-600 hover:bg-red-50"
                        >
                            <span class="material-symbols-outlined text-xl">
                                delete
                            </span>
                        </button>
                    </div>
                </div>
            </section>
        @empty
            <div
                class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center md:col-span-2 xl:col-span-3"
            >
                <span class="material-symbols-outlined text-4xl text-slate-400">
                    payments
                </span>
                <h2 class="mt-4 text-lg font-semibold">
                    No pricing plans yet
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-secondary">
                    Start with a plan such as Basic or Pro. Choose its billing periods and set its own limits.
                </p>
                <button
                    type="button"
                    wire:click="create"
                    class="mt-5 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white"
                >
                    Add your first plan
                </button>
            </div>
        @endforelse
    </div>
    {{-- One compact form keeps plan editing focused without nested cards or a sidebar. --}}
    @if ($showForm)
        <dialog
            wire:key="plan-editor-{{ $editing ?? 'new' }}"
            wire:ignore.self
            aria-labelledby="plan-editor-title"
            class="fixed inset-0 m-auto max-h-[calc(100dvh-3rem)] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto
                rounded-2xl border border-slate-200 bg-white p-5 text-on-surface shadow-2xl
                backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm sm:p-6"
            x-data="{
                init() {
                    this.$el.showModal();
                    document.documentElement.classList.add('overflow-hidden');
                },
                destroy() {
                    document.documentElement.classList.remove('overflow-hidden');
                }
            }"
            @cancel.prevent="$wire.closeEditor()"
        >
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2 id="plan-editor-title" class="text-xl font-semibold text-on-surface">{{ $editing ? 'Edit plan' : 'Add plan' }}</h2>
                    <p class="mt-1 text-sm text-secondary">Configure pricing and allowances for {{ $product->name }}.</p>
                </div>
                <button type="button" wire:click="closeEditor" aria-label="Close plan editor"
                    class="rounded-lg border border-outline-variant bg-white p-2.5 text-secondary transition-colors hover:bg-slate-50">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>

            <form wire:submit="save" novalidate>
                @if ($errors->any())
                    <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <p class="font-semibold">Please check the following:</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="contents">
                        <div class="sm:col-span-2">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="plan-name" class="block font-label-md text-on-surface">Plan name <span class="text-red-500">*</span></label>
                                    <input id="plan-name" wire:model="name" maxlength="160" placeholder="e.g. Professional" autofocus
                                        aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="plan-name-error"
                                        class="w-full rounded border border-outline-variant px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 @error('name') border-red-500! @enderror">
                                    @error('name') <p id="plan-name-error" role="alert" class="text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="plan-badge" class="block font-label-md text-on-surface">Badge</label>
                                    <input id="plan-badge" wire:model="badge" maxlength="80" placeholder="Popular, Best Value, Recommended"
                                        class="w-full rounded border border-outline-variant px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @error('badge') <p role="alert" class="text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <div class="mb-4 space-y-2">
                                <label for="plan-pricing-mode" class="block font-label-md text-on-surface">Pricing type</label>
                                <select id="plan-pricing-mode" wire:model.live="pricing_mode"
                                    class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    <option value="subscription">Subscription</option>
                                    <option value="one_time">One-time purchase</option>
                                    <option value="quotation">Custom subscription (contact us)</option>
                                </select>
                                @error('pricing_mode') <p role="alert" class="text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            @if ($pricing_mode === 'subscription')
                                <div class="grid items-start gap-3 sm:grid-cols-2">
                                    @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $cycle => $label)
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <label for="plan-{{ $cycle }}-enabled" class="font-semibold text-on-surface">{{ $label }} Pricing</label>
                                                    
                                                </div>
                                                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                                    <input id="plan-{{ $cycle }}-enabled" type="checkbox" role="switch"
                                                        wire:model.live="{{ $cycle }}_enabled" class="peer sr-only">
                                                    <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-0.5 after:left-0.5
                                                        after:size-5 after:rounded-full after:border after:border-gray-300 after:bg-white
                                                        after:transition-transform after:content-[''] peer-checked:bg-primary
                                                        peer-checked:after:translate-x-full peer-checked:after:border-white
                                                        peer-focus-visible:ring-4 peer-focus-visible:ring-blue-100"></span>
                                                </label>
                                            </div>
                                            @if ($this->{$cycle.'_enabled'})
                                                <div class="mt-3 grid grid-cols-1 gap-3">
                                                    <div class="space-y-2">
                                                        <input id="plan-{{ $cycle }}-price" aria-label="{{ $label }} price" wire:model="{{ $cycle }}_price"
                                                            type="number" min="0.01" step="0.01" placeholder="e.g. 1500"
                                                            aria-invalid="{{ $errors->has($cycle.'_price') ? 'true' : 'false' }}"
                                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                                    </div>
                                                </div>
                                            @endif
                                            {{-- A missing-price error remains visible even when both billing toggles are off. --}}
                                            @error($cycle.'_price') <p id="plan-{{ $cycle }}-price-error" role="alert" class="mt-3 text-sm text-red-500">{{ $message }}</p> @enderror
                                            @error($cycle.'_enabled') <p role="alert" class="mt-3 text-sm text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($pricing_mode === 'quotation')
                                <p class="rounded-lg border border-sky-100 bg-sky-50 p-3 text-sm text-secondary">
                                    Clients can contact you to agree on subscription pricing and limits for this plan.
                                </p>
                            @elseif ($pricing_mode === 'one_time')
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <h4 class="font-semibold text-on-surface">One-time Pricing</h4>
                                    <div class="mt-3 grid grid-cols-1 gap-3">
                                        <div class="space-y-2">
                                            <label for="plan-one-time-price" class="block font-label-md text-on-surface">One-time Price (৳)</label>
                                            <input id="plan-one-time-price" wire:model="one_time_price" type="number" min="0.01" step="0.01"
                                                class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error('one_time_price') <p role="alert" class="text-sm text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="space-y-2">
                                            <label for="plan-one-time-checkout" class="block font-label-md text-on-surface">Checkout URL (optional)</label>
                                            <input id="plan-one-time-checkout" wire:model="one_time_checkout_url" type="url" placeholder="https://"
                                                class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error('one_time_checkout_url') <p role="alert" class="text-sm text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <label for="plan-features" class="mb-2 block font-label-md text-on-surface">Included features</label>
                            <textarea id="plan-features" wire:model="features_text" rows="2" placeholder="Priority support&#10;Advanced reports"
                                class="w-full rounded border border-outline-variant px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"></textarea>
                            <p class="mt-2 text-xs text-secondary">One feature per line, up to 40 features.</p>
                            @error('features') <p role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                            @foreach ($errors->get('features.*') as $messages)
                                @foreach ($messages as $message)
                                    <p role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                    <div class="contents">
                        @if ($product->limit_definitions)
                            <div class="sm:col-span-2">
                                <h3 class="mb-1 font-label-md text-on-surface">Plan allowances</h3>
                                <p class="mb-3 text-xs leading-5 text-secondary">Leave an optional amount empty to omit it from this plan.</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($product->limit_definitions as $definition)
                                        <div wire:key="plan-limit-{{ $definition['key'] }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                            <label for="allowance-{{ $definition['key'] }}" class="block font-label-md text-on-surface">
                                                {{ $definition['label'] }}
                                                @if (!empty($definition['unit'])) <span class="text-secondary">({{ $definition['unit'] }})</span> @endif
                                            </label>
                                                <input id="allowance-{{ $definition['key'] }}" wire:model="limits.{{ $definition['key'] }}.amount" type="number" min="1" step="1"
                                                    @disabled(!empty($limits[$definition['key']]['unlimited']))
                                                    class="mt-2 w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-slate-100">
                                                <div class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-sky-100 bg-sky-50/50 p-3">
                                                    <label for="unlimited-{{ $definition['key'] }}" class="text-sm font-medium text-on-surface">Unlimited</label>
                                                    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                                        <input id="unlimited-{{ $definition['key'] }}" type="checkbox" role="switch"
                                                            wire:model.live="limits.{{ $definition['key'] }}.unlimited" class="peer sr-only">
                                                        <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-0.5 after:left-0.5
                                                            after:size-5 after:rounded-full after:border after:border-gray-300 after:bg-white
                                                            after:transition-transform after:content-[''] peer-checked:bg-sky-500
                                                            peer-checked:after:translate-x-full peer-checked:after:border-white
                                                            peer-focus-visible:ring-4 peer-focus-visible:ring-sky-100"></span>
                                                    </label>
                                                </div>
                                                @error('limits.'.$definition['key'].'.amount') <p role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                                @error('limits') <p role="alert" class="mt-3 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="space-y-2">
                            <label for="plan-sort-order" class="block font-label-md text-on-surface">Display order</label>
                            <input id="plan-sort-order" wire:model="sort_order" type="number" min="0" max="100000"
                                class="w-full rounded border border-outline-variant px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                            @error('sort_order') <p role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2"><h3 class="font-label-md text-on-surface">Plan status</h3>
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50 p-3">
                                <div>
                                    <label for="plan-is-active" class="block text-label-md font-label-md text-on-surface">{{ $is_active ? 'Active plan' : 'Inactive plan' }}</label>
                                    <p class="mt-1 text-xs text-secondary">Show or hide this plan publicly.</p>
                                </div>
                                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                    <input id="plan-is-active" type="checkbox" role="switch" wire:model.live="is_active" class="peer sr-only">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-0.5 after:left-0.5
                                        after:size-5 after:rounded-full after:border after:border-gray-300 after:bg-white
                                        after:transition-transform after:content-[''] peer-checked:bg-primary
                                        peer-checked:after:translate-x-full peer-checked:after:border-white
                                        peer-focus-visible:ring-4 peer-focus-visible:ring-blue-100"></span>
                                </label>
                            </div>
                            @error('is_active') <p role="alert" class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" wire:click="closeEditor"
                        class="cursor-pointer rounded-lg border border-outline-variant px-5 py-3 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3
                            text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90
                            disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save plan</span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                            <span class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                            Saving…
                        </span>
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    {{-- Deletion is a separate modal; opening or cancelling it never changes the plan. --}}
    @if ($deleting)
        <dialog
            wire:key="delete-plan-{{ $deleting }}"
            wire:ignore.self
            aria-labelledby="delete-plan-title"
            aria-describedby="delete-plan-description"
            class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200
                bg-white p-6 text-on-surface shadow-2xl backdrop:bg-slate-950/40 backdrop:backdrop-blur-sm"
            x-data="{
                init() {
                    this.$el.showModal();
                    document.documentElement.classList.add('overflow-hidden');
                },
                destroy() {
                    document.documentElement.classList.remove('overflow-hidden');
                }
            }"
            @cancel.prevent="$wire.cancelDelete()"
        >
            <div class="mb-4 flex items-center justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-xl bg-red-50 text-red-600">
                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                </span>
                <button type="button" wire:click="cancelDelete" aria-label="Close delete confirmation"
                    class="rounded-lg p-2 text-secondary hover:bg-slate-100">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>
            <h2 id="delete-plan-title" class="text-h3 font-h2">Delete pricing plan?</h2>
            <p id="delete-plan-description" class="mt-3 text-sm leading-6 text-secondary">
                Delete <strong class="font-semibold text-on-surface">{{ $deletingName }}</strong>?
                Existing subscriptions and licenses will remain intact.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="cancelDelete" autofocus
                    class="rounded-lg border border-outline-variant px-4 py-2.5 text-label-md font-label-md hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" wire:click="delete({{ $deleting }})" wire:loading.attr="disabled"
                    class="rounded-lg bg-red-600 px-4 py-2.5 text-label-md font-label-md text-white
                        transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="delete">Delete plan</span>
                    <span wire:loading wire:target="delete">Deleting…</span>
                </button>
            </div>
        </dialog>
    @endif
</div>
