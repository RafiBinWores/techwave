<?php

use App\Models\Proposal;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('Create Proposal')] class extends Component {
    public ?int $user_id = null;
    public ?int $company_id = null;

    public string $customer_search = '';
    public bool $customer_dropdown_open = false;

    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $company_name = '';

    public string $subject = '';
    public string $note = '';

    public string $discount_type = 'none';
    public string $discount_value = '0';

    public string $valid_until = '';

    public ?int $selected_service_id = null;
    public ?int $selected_service_plan_id = null;
    public ?int $selected_pricing_plan_id = null;

    public string $custom_title = '';
    public string $custom_description = '';
    public string $custom_quantity = '';
    public string $custom_unit_price = '';

    public array $items = [];

    protected function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],

            'customer_name' => ['required', 'string', 'max:180'],
            'customer_email' => ['nullable', 'email', 'max:180'],
            'customer_phone' => ['nullable', 'string', 'max:80'],
            'company_name' => ['nullable', 'string', 'max:180'],

            'subject' => ['required', 'string', 'max:220'],
            'note' => ['nullable', 'string', 'max:2000'],

            'discount_type' => ['required', 'in:none,fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],

            'valid_until' => ['nullable', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:220'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'Please add at least one service, plan, pricing, or custom item.',
            'items.min' => 'Please add at least one service, plan, pricing, or custom item.',
        ];
    }

    public function users()
    {
        $search = trim($this->customer_search);

        return User::query()
            ->with('company')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('company', function ($companyQuery) use ($search) {
                            $companyQuery
                                ->where('company_name', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%')
                                ->orWhere('address', 'like', '%' . $search . '%')
                                ->orWhere('website', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->limit(8)
            ->get();
    }

    public function selectCustomer(int $userId): void
    {
        $user = User::query()->with('company')->findOrFail($userId);

        $company = $user->company;

        $this->user_id = $user->id;
        $this->company_id = $company?->id;

        $this->customer_name = $user->name ?? '';
        $this->customer_email = $user->email ?? '';

        // Your users table has no phone column, so company phone is used.
        $this->customer_phone = $company?->phone ?? '';

        $this->company_name = $company?->company_name ?? '';

        $this->customer_search = trim(($user->name ?? '') . ' - ' . ($user->email ?? ''));
        $this->customer_dropdown_open = false;

        $this->resetValidation(['user_id', 'company_id', 'customer_name', 'customer_email', 'customer_phone', 'company_name']);
    }

    public function clearCustomer(): void
    {
        $this->user_id = null;
        $this->company_id = null;

        $this->customer_search = '';
        $this->customer_name = '';
        $this->customer_email = '';
        $this->customer_phone = '';
        $this->company_name = '';

        $this->customer_dropdown_open = false;
    }

    public function services()
    {
        return Service::query()->where('is_active', true)->orderBy('card_title')->get();
    }

    public function servicePlans()
    {
        return ServicePlan::query()->with('service')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function pricingPlans()
    {
        if (!class_exists(\App\Models\PricingPlan::class)) {
            return collect();
        }

        return \App\Models\PricingPlan::query()->orderBy('title')->get();
    }

    public function proposalNo(): string
    {
        return 'PROP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    public function addService(): void
    {
        if (!$this->selected_service_id) {
            $this->dispatch('toast', message: 'Please select a service first.', type: 'warning');
            return;
        }

        $service = Service::findOrFail($this->selected_service_id);

        $this->items[] = [
            'item_type' => 'service',
            'item_id' => $service->id,
            'title' => $service->card_title,
            'description' => $service->short_description ?? '',
            'quantity' => 1,
            'unit_price' => 0,
        ];

        $this->selected_service_id = null;
        $this->resetValidation('items');
    }

    public function addServicePlan(): void
    {
        if (!$this->selected_service_plan_id) {
            $this->dispatch('toast', message: 'Please select a service plan first.', type: 'warning');
            return;
        }

        $plan = ServicePlan::with('service')->findOrFail($this->selected_service_plan_id);

        $this->items[] = [
            'item_type' => 'service_plan',
            'item_id' => $plan->id,
            'title' => ($plan->service?->card_title ? $plan->service->card_title . ' - ' : '') . $plan->name,
            'description' => $plan->description ?? '',
            'quantity' => 1,
            'unit_price' => $plan->price ?? 0,
        ];

        $this->selected_service_plan_id = null;
        $this->resetValidation('items');
    }

    public function addPricingPlan(): void
    {
        if (!$this->selected_pricing_plan_id || !class_exists(\App\Models\PricingPlan::class)) {
            $this->dispatch('toast', message: 'Please select a pricing plan first.', type: 'warning');
            return;
        }

        $plan = \App\Models\PricingPlan::findOrFail($this->selected_pricing_plan_id);

        $price = $plan->yearly_price ?? ($plan->monthly_price ?? 0);

        $this->items[] = [
            'item_type' => 'pricing_plan',
            'item_id' => $plan->id,
            'title' => $plan->title ?? 'Pricing Plan',
            'description' => $plan->description ?? '',
            'quantity' => 1,
            'unit_price' => $price,
        ];

        $this->selected_pricing_plan_id = null;
        $this->resetValidation('items');
    }

    public function addCustomItem(): void
    {
        $title = trim($this->custom_title);

        if ($title === '') {
            $this->dispatch('toast', message: 'Please enter custom service title.', type: 'warning');
            return;
        }

        $this->items[] = [
            'item_type' => 'custom',
            'item_id' => null,
            'title' => $title,
            'description' => trim($this->custom_description),
            'quantity' => (float) ($this->custom_quantity ?: 1),
            'unit_price' => (float) ($this->custom_unit_price ?: 0),
        ];

        $this->custom_title = '';
        $this->custom_description = '';
        $this->custom_quantity = '1';
        $this->custom_unit_price = '';

        $this->resetValidation('items');
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }

    public function subtotal(): float
    {
        return collect($this->items)->sum(fn($item) => (float) $item['quantity'] * (float) $item['unit_price']);
    }

    public function discountAmount(): float
    {
        $subtotal = $this->subtotal();
        $discount = (float) ($this->discount_value ?: 0);

        return match ($this->discount_type) {
            'percentage' => ($subtotal * $discount) / 100,
            'fixed' => $discount,
            default => 0,
        };
    }

    public function grandTotal(): float
    {
        return max($this->subtotal() - $this->discountAmount(), 0);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $proposal = Proposal::create([
            'user_id' => $validated['user_id'] ?: null,
            'company_id' => $validated['company_id'] ?: null,

            'proposal_no' => $this->proposalNo(),

            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?: null,
            'customer_phone' => $validated['customer_phone'] ?: null,
            'company_name' => $validated['company_name'] ?: null,

            'subject' => $validated['subject'],
            'note' => $validated['note'] ?: null,

            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'] ?: 0,

            'status' => 'draft',
            'valid_until' => $validated['valid_until'] ?: null,
        ]);

        foreach ($this->items as $item) {
            $proposal->items()->create([
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'title' => $item['title'],
                'description' => $item['description'] ?: null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Proposal created successfully.',
        ]);

        $this->redirectRoute('admin.proposals.index', navigate: true);
    }

    public function discard(): void
    {
        $this->reset();

        $this->discount_type = 'none';
        $this->discount_value = '0';
        $this->custom_quantity = '1';
        $this->items = [];
        $this->customer_dropdown_open = false;

        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes discarded.', type: 'info');
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Create Proposal</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Build a custom proposal with multiple services, plans, discounts and customer details.
            </p>
        </div>

        <a href="{{ route('admin.proposals.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Proposals
        </a>
    </div>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6 lg:col-span-8">
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">person</span>
                        Customer Information
                    </h3>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">
                                Select Customer From Users
                            </label>

                            <div class="relative" x-data @click.outside="$wire.set('customer_dropdown_open', false)">
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                        search
                                    </span>

                                    <input type="search" wire:model.live.debounce.300ms="customer_search"
                                        wire:focus="$set('customer_dropdown_open', true)"
                                        placeholder="Search by name, email, company..."
                                        class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-12 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10" />

                                    @if ($user_id)
                                        <button type="button" wire:click="clearCustomer"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-red-500">
                                            <span class="material-symbols-outlined text-lg">close</span>
                                        </button>
                                    @endif
                                </div>

                                @if ($customer_dropdown_open)
                                    <div
                                        class="absolute z-30 mt-2 max-h-80 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                                        @forelse ($this->users() as $user)
                                            <button type="button" wire:click="selectCustomer({{ $user->id }})"
                                                class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50">
                                                <div
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                    <span class="material-symbols-outlined text-[20px]">
                                                        {{ $user->type === 'company' ? 'business' : 'person' }}
                                                    </span>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="truncate text-sm font-semibold text-on-surface">
                                                            {{ $user->name }}
                                                        </p>

                                                        <span
                                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase text-slate-600">
                                                            {{ $user->type }}
                                                        </span>
                                                    </div>

                                                    <p class="truncate text-xs text-secondary">
                                                        {{ $user->email }}
                                                    </p>

                                                    @if ($user->company)
                                                        <p class="mt-1 truncate text-xs text-blue-700">
                                                            {{ $user->company->company_name }}
                                                        </p>
                                                    @else
                                                        <p class="mt-1 text-xs text-amber-600">
                                                            No company attached
                                                        </p>
                                                    @endif
                                                </div>
                                            </button>
                                        @empty
                                            <div class="px-4 py-6 text-center">
                                                <p class="text-sm font-medium text-slate-600">
                                                    No customer found
                                                </p>

                                                <p class="text-xs text-secondary">
                                                    You can still type customer details manually below.
                                                </p>
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>

                            @if ($user_id)
                                <p class="text-xs text-emerald-600">
                                    Customer selected. You can still edit details below.
                                </p>
                            @else
                                <p class="text-xs text-secondary">
                                    Search and select an existing user, or fill customer details manually.
                                </p>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <input type="hidden" wire:model="user_id">
                            <input type="hidden" wire:model="company_id">

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Customer Name</label>

                                <input type="text" wire:model.live="customer_name"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                    placeholder="Customer name" />

                                @error('customer_name')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Customer Email</label>

                                <input type="email" wire:model.live="customer_email"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                    placeholder="customer@email.com" />

                                @error('customer_email')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Customer Phone</label>

                                <input type="text" wire:model.live="customer_phone"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                    placeholder="+880..." />

                                @error('customer_phone')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Company Name</label>

                                <input type="text" wire:model.live="company_name"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                    placeholder="Company name" />

                                @error('company_name')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">request_quote</span>
                        Proposal Details
                    </h3>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Subject</label>

                            <input type="text" wire:model.live="subject"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                placeholder="Managed IT Service Proposal" />

                            @error('subject')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">
                                Note
                                <span class="text-xs font-normal text-secondary">(optional)</span>
                            </label>

                            <textarea wire:model.live="note" rows="4"
                                class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                placeholder="Example: Special discount valid for 7 days. Free initial setup included."></textarea>

                            @error('note')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-8 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">add_shopping_cart</span>
                        Add Services / Plans
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Select Service</label>
                            <select wire:model.live="selected_service_id"
                                class="w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="">Select service</option>
                                @foreach ($this->services() as $service)
                                    <option value="{{ $service->id }}">{{ $service->card_title }}</option>
                                @endforeach
                            </select>

                            <button type="button" wire:click="addService"
                                class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                Add Service
                            </button>
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Select Service Plan</label>
                            <select wire:model.live="selected_service_plan_id"
                                class="w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="">Select service plan</option>
                                @foreach ($this->servicePlans() as $plan)
                                    <option value="{{ $plan->id }}">
                                        {{ $plan->service?->card_title }} - {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" wire:click="addServicePlan"
                                class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                Add Plan
                            </button>
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Select Pricing</label>
                            <select wire:model.live="selected_pricing_plan_id"
                                class="w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="">Select pricing plan</option>
                                @foreach ($this->pricingPlans() as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->title }}</option>
                                @endforeach
                            </select>

                            <button type="button" wire:click="addPricingPlan"
                                class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                Add Pricing
                            </button>
                        </div>
                    </div>

                    <div class="mt-8 rounded-xl border border-slate-100 bg-slate-50 p-5">
                        <h4 class="mb-4 font-semibold text-on-surface">Custom Service</h4>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <input type="text" wire:model.live="custom_title"
                                class="rounded border border-outline-variant px-4 py-2.5 md:col-span-2"
                                placeholder="Custom service title" />
                            <input type="number" wire:model.live="custom_quantity"
                                class="rounded border border-outline-variant px-4 py-2.5" placeholder="Qty" />
                            <input type="number" wire:model.live="custom_unit_price"
                                class="rounded border border-outline-variant px-4 py-2.5" placeholder="Price" />

                            <textarea wire:model.live="custom_description" rows="2"
                                class="rounded border border-outline-variant px-4 py-2.5 md:col-span-4" placeholder="Custom service description"></textarea>
                        </div>

                        <button type="button" wire:click="addCustomItem"
                            class="mt-4 rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white cursor-pointer">
                            Add Custom Service
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="mb-6 flex items-center gap-2 text-h3 font-h2">
                        <span class="material-symbols-outlined text-primary">receipt_long</span>
                        Proposal Items
                    </h3>

                    @error('items')
                        <p class="mb-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="space-y-4">
                        @forelse ($items as $index => $item)
                            <div wire:key="proposal-item-{{ $index }}"
                                class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                                    <div class="md:col-span-4">
                                        <label class="text-xs text-secondary">Title</label>
                                        <input wire:model.live="items.{{ $index }}.title"
                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm" />
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="text-xs text-secondary">Description</label>
                                        <input wire:model.live="items.{{ $index }}.description"
                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="text-xs text-secondary">Qty</label>
                                        <input type="number" wire:model.live="items.{{ $index }}.quantity"
                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="text-xs text-secondary">Unit Price</label>
                                        <input type="number" wire:model.live="items.{{ $index }}.unit_price"
                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm" />
                                    </div>

                                    <div class="flex items-end justify-end md:col-span-1">
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                            class="text-red-500 cursor-pointer">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-secondary">
                                No items added yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" wire:click="discard" wire:loading.attr="disabled"
                            class="rounded-lg border border-outline-variant px-5 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50 cursor-pointer">
                            Discard Changes
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 cursor-pointer">
                            <span wire:loading.remove wire:target="save">Save Proposal</span>

                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Discount & Validity</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-on-surface">Discount Type</label>
                            <select wire:model.live="discount_type"
                                class="mt-2 w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="none">No Discount</option>
                                <option value="fixed">Fixed Discount</option>
                                <option value="percentage">Percentage Discount</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-on-surface">Discount Value</label>
                            <input type="number" wire:model.live="discount_value"
                                class="mt-2 w-full rounded border border-outline-variant px-4 py-2.5" />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-on-surface">Valid Until</label>
                            <input type="date" wire:model.live="valid_until"
                                class="mt-2 w-full rounded border border-outline-variant px-4 py-2.5" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Proposal Summary</h3>

                    <div class="space-y-3 rounded-2xl bg-slate-50 p-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary">Subtotal</span>
                            <span class="font-mono">{{ number_format($this->subtotal(), 2) }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-secondary">Discount</span>
                            <span
                                class="font-mono text-red-600">-{{ number_format($this->discountAmount(), 2) }}</span>
                        </div>

                        <div class="border-t border-slate-200 pt-3">
                            <div class="flex justify-between">
                                <span class="font-bold text-on-surface">Grand Total</span>
                                <span class="font-mono text-xl font-bold text-primary">
                                    {{ number_format($this->grandTotal(), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl bg-blue-50 p-4 text-sm leading-relaxed text-blue-800">
                        Use the discount field to encourage your customer to buy faster. For example:
                        “Special 10% discount valid until this week.”
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
