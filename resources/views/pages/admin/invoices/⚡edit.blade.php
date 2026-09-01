<?php

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Propaganistas\LaravelPhone\Rules\Phone;

new #[Layout('layouts.admin-app')] #[Title('Edit Invoice')] class extends Component {
    public Invoice $invoice;
    public InvoiceTemplate $invoiceTemplate;

    public ?int $user_id = null;
    public ?int $company_id = null;

    public string $customer_search = '';
    public bool $customer_dropdown_open = false;

    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $customer_country = 'BD';
    public string $company_name = '';

    public string $subject = '';
    public string $note = '';
    public string $terms = '';

    public string $discount_type = 'none';
    public string $discount_value = '0';
    public string $issue_date = '';
    public string $due_date = '';

    public ?int $selected_service_id = null;
    public ?int $selected_service_plan_id = null;
    public ?int $selected_service_option_id = null;
    public ?string $selected_billing_cycle = null;

    public string $custom_title = '';
    public string $custom_description = '';
    public string $custom_quantity = '1';
    public string $custom_unit_price = '';

    public array $items = [];

    public function mount(Invoice $invoice): void
    {
        $this->invoiceTemplate = InvoiceTemplate::activeTemplate();

        $this->invoice = $invoice->load(['items', 'user.company', 'company']);

        $this->fillFromInvoice();
    }

    public function fillFromInvoice(): void
    {
        $this->user_id = $this->invoice->user_id;
        $this->company_id = $this->invoice->company_id;

        $this->customer_search = $this->invoice->user
            ? trim(($this->invoice->user->name ?? '') . ' - ' . ($this->invoice->user->email ?? ''))
            : '';

        $this->customer_name = $this->invoice->customer_name;
        $this->customer_email = $this->invoice->customer_email ?? '';
        $this->customer_phone = $this->invoice->customer_phone ?? '';
        $this->customer_country = $this->invoice->user?->country ?: 'BD';
        $this->company_name = $this->invoice->company_name ?? '';

        $this->subject = $this->invoice->subject;
        $this->note = $this->invoice->note ?? '';
        $this->terms = $this->invoice->terms ?? '';

        $this->discount_type = $this->invoice->discount_type;
        $this->discount_value = (string) $this->invoice->discount_value;

        $this->issue_date = $this->invoice->issue_date?->format('Y-m-d') ?? now()->toDateString();
        $this->due_date = $this->invoice->due_date?->format('Y-m-d') ?? '';

        $this->items = $this->invoice->items
            ->map(fn($item) => [
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'title' => $item->title,
                'description' => $item->description ?? '',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255', (new Phone)->country($this->customer_country)],
            'customer_country' => ['required', 'string', 'size:2'],
            'company_name' => ['nullable', 'string', 'max:255'],

            'subject' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],

            'discount_type' => ['required', 'in:none,fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],

            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,service_plan,service_option,pricing_plan,custom'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'Please add at least one service, plan, option, or custom item.',
            'items.min' => 'Please add at least one service, plan, option, or custom item.',
            'customer_phone.phone' => 'The phone number must be a valid ' . ($this->countries()[strtoupper($this->customer_country)] ?? '') . ' phone number.',
        ];
    }

    public function countries(): array
    {
        return [
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra',
            'AO' => 'Angola', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia',
            'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas',
            'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus',
            'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BT' => 'Bhutan',
            'BO' => 'Bolivia', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BR' => 'Brazil',
            'BN' => 'Brunei', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi',
            'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde',
            'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China',
            'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CR' => 'Costa Rica',
            'CI' => 'Côte d’Ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus',
            'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica',
            'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'SZ' => 'Eswatini',
            'ET' => 'Ethiopia', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France',
            'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany',
            'GH' => 'Ghana', 'GR' => 'Greece', 'GD' => 'Grenada', 'GT' => 'Guatemala',
            'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti',
            'HN' => 'Honduras', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India',
            'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland',
            'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan',
            'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati',
            'KP' => 'North Korea', 'KR' => 'South Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan',
            'LA' => 'Laos', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho',
            'LR' => 'Liberia', 'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania',
            'LU' => 'Luxembourg', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia',
            'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands',
            'MR' => 'Mauritania', 'MU' => 'Mauritius', 'MX' => 'Mexico', 'FM' => 'Micronesia',
            'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro',
            'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia',
            'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NZ' => 'New Zealand',
            'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'MK' => 'North Macedonia',
            'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau',
            'PS' => 'Palestine', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay',
            'PE' => 'Peru', 'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal',
            'QA' => 'Qatar', 'RO' => 'Romania', 'RU' => 'Russia', 'RW' => 'Rwanda',
            'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'São Tomé and Príncipe', 'SA' => 'Saudi Arabia',
            'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone',
            'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands',
            'SO' => 'Somalia', 'ZA' => 'South Africa', 'SS' => 'South Sudan', 'ES' => 'Spain',
            'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SE' => 'Sweden',
            'CH' => 'Switzerland', 'SY' => 'Syria', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo',
            'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey',
            'TM' => 'Turkmenistan', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States',
            'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VA' => 'Vatican City',
            'VE' => 'Venezuela', 'VN' => 'Vietnam', 'YE' => 'Yemen', 'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
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
        $this->customer_phone = $user->phone ?: ($company?->phone ?? '');
        $this->customer_country = $user->country ?: 'BD';
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
        $this->customer_country = 'BD';
        $this->company_name = '';

        $this->customer_dropdown_open = false;
    }

    public function services()
    {
        return Service::query()->where('is_active', true)->orderBy('card_title')->get();
    }

    public function servicePlans()
    {
        if (!$this->selected_service_id) {
            return collect();
        }

        return ServicePlan::query()
            ->where('service_id', $this->selected_service_id)
            ->where('is_active', true)
            ->when(
                $this->selected_service_option_id,
                fn ($q) => $q->where('service_option_id', $this->selected_service_option_id),
                fn ($q) => $q->whereNull('service_option_id'),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function serviceOptions()
    {
        if (!$this->selected_service_id) {
            return collect();
        }

        return \App\Models\ServiceOption::query()
            ->where('service_id', $this->selected_service_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('card_title')
            ->get();
    }

    public function selectedServiceHasOptions(): bool
    {
        if (!$this->selected_service_id) {
            return false;
        }

        return \App\Models\ServiceOption::query()
            ->where('service_id', $this->selected_service_id)
            ->where('is_active', true)
            ->exists();
    }

    public function selectedOptionHasPlans(): bool
    {
        if (!$this->selected_service_option_id) {
            return false;
        }

        return ServicePlan::query()
            ->where('service_option_id', $this->selected_service_option_id)
            ->where('is_active', true)
            ->exists();
    }

    public function updatedSelectedServiceId(): void
    {
        $this->selected_service_plan_id = null;
        $this->selected_service_option_id = null;
        $this->selected_billing_cycle = null;
    }

    public function updatedSelectedServiceOptionId(): void
    {
        $this->selected_service_plan_id = null;
        $this->selected_billing_cycle = null;
    }

    public function selectedServicePlan()
    {
        if (!$this->selected_service_plan_id) {
            return null;
        }

        return ServicePlan::query()->find($this->selected_service_plan_id);
    }

    public function updatedSelectedServicePlanId(): void
    {
        $plan = $this->selectedServicePlan();

        $this->selected_billing_cycle = $plan?->has_monthly_price && $plan->monthly_price
            ? 'monthly'
            : ($plan?->has_yearly_price && $plan->yearly_price
                ? 'yearly'
                : ($plan?->has_one_time_price && $plan->price ? 'one_time' : null));
    }

    public function planBillingCycles(): array
    {
        $plan = $this->selectedServicePlan();

        if (!$plan) {
            return [];
        }

        $cycles = [];

        if ($plan->has_monthly_price && $plan->monthly_price > 0) {
            $cycles[] = ['value' => 'monthly', 'label' => 'Monthly'];
        }

        if ($plan->has_yearly_price && $plan->yearly_price > 0) {
            $cycles[] = ['value' => 'yearly', 'label' => 'Yearly'];
        }

        if ($plan->has_one_time_price && $plan->price > 0) {
            $cycles[] = ['value' => 'one_time', 'label' => 'One-time'];
        }

        return $cycles;
    }

    public function selectedPlanPrice(): ?float
    {
        $plan = $this->selectedServicePlan();

        if (!$plan) {
            return null;
        }

        return match ($this->selected_billing_cycle) {
            'monthly' => $this->finalPlanPrice($plan->monthly_price, $plan->monthly_discount_price),
            'yearly' => $this->finalPlanPrice($plan->yearly_price, $plan->yearly_discount_price),
            'one_time' => $this->finalPlanPrice($plan->price, $plan->discount_price),
            default => null,
        };
    }

    private function finalPlanPrice($regular, $discount): ?float
    {
        if (empty($regular) || (float) $regular <= 0) {
            return null;
        }

        if (!empty($discount) && (float) $discount > 0 && (float) $discount < (float) $regular) {
            return (float) $discount;
        }

        return (float) $regular;
    }

    public function addService(): void
    {
        if (!$this->selected_service_id) {
            $this->dispatch('toast', message: 'Please select a service first.', type: 'warning');

            return;
        }

        $service = Service::query()->findOrFail($this->selected_service_id);

        $this->items[] = [
            'item_type' => 'service',
            'item_id' => $service->id,
            'title' => $service->card_title ?? ($service->title ?? 'Service'),
            'description' => $service->short_description ?? ($service->description ?? ''),
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

        $billingCycle = $this->selected_billing_cycle;

        if (!$billingCycle) {
            $this->dispatch('toast', message: 'This plan has no available pricing.', type: 'warning');

            return;
        }

        $plan = ServicePlan::query()->with('service')->findOrFail($this->selected_service_plan_id);

        $price = $this->finalPlanPrice(
            match ($billingCycle) {
                'monthly' => $plan->monthly_price,
                'yearly' => $plan->yearly_price,
                default => $plan->price,
            },
            match ($billingCycle) {
                'monthly' => $plan->monthly_discount_price,
                'yearly' => $plan->yearly_discount_price,
                default => $plan->discount_price,
            },
        );

        $this->items[] = [
            'item_type' => 'service_plan',
            'item_id' => $plan->id,
            'title' => ($plan->service?->card_title ? $plan->service->card_title . ' - ' : '') . $plan->name . ' (' . ucwords(str_replace('_', ' ', $billingCycle)) . ')',
            'description' => $plan->description ?? '',
            'quantity' => 1,
            'unit_price' => $price ?? 0,
        ];

        $this->selected_service_plan_id = null;
        $this->selected_service_option_id = null;
        $this->selected_billing_cycle = null;
        $this->resetValidation('items');
    }

    public function addServiceOption(): void
    {
        if (!$this->selected_service_option_id) {
            $this->dispatch('toast', message: 'Please select a service option first.', type: 'warning');

            return;
        }

        $option = \App\Models\ServiceOption::query()->findOrFail($this->selected_service_option_id);

        $this->items[] = [
            'item_type' => 'service_option',
            'item_id' => $option->id,
            'title' => $option->card_title ?? ($option->detail_title ?? 'Service Option'),
            'description' => $option->short_description ?? '',
            'quantity' => 1,
            'unit_price' => 0,
        ];

        $this->selected_service_option_id = null;
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
        return collect($this->items)->sum(function ($item) {
            return (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        });
    }

    public function discountAmount(): float
    {
        $subtotal = $this->subtotal();
        $discount = (float) ($this->discount_value ?: 0);

        $amount = match ($this->discount_type) {
            'percentage' => ($subtotal * min($discount, 100)) / 100,
            'fixed' => $discount,
            default => 0,
        };

        return min($amount, $subtotal);
    }

    public function grandTotal(): float
    {
        return max($this->subtotal() - $this->discountAmount(), 0);
    }

    public function update(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $this->invoice->update([
                'user_id' => $validated['user_id'] ?: null,
                'company_id' => $validated['company_id'] ?: null,

                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?: null,
                'customer_phone' => $validated['customer_phone'] ?: null,
                'company_name' => $validated['company_name'] ?: null,

                'subject' => $validated['subject'],
                'note' => $validated['note'] ?: null,
                'terms' => $validated['terms'] ?: null,

                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?: 0,

                'issue_date' => $validated['issue_date'] ?: null,
                'due_date' => $validated['due_date'] ?: null,
            ]);

            $this->invoice->items()->delete();

            foreach ($validated['items'] as $item) {
                $this->invoice->items()->create([
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'] ?? null,
                    'title' => $item['title'],
                    'description' => $item['description'] ?: null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }
        });

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Invoice updated successfully.',
        ]);

        $this->redirectRoute('admin.invoices.index', navigate: true);
    }

    public function discard(): void
    {
        $this->invoice->refresh();
        $this->invoice->load(['items', 'user.company', 'company']);

        $this->fillFromInvoice();

        $this->selected_service_id = null;
        $this->selected_service_plan_id = null;
        $this->selected_service_option_id = null;

        $this->custom_title = '';
        $this->custom_description = '';
        $this->custom_quantity = '1';
        $this->custom_unit_price = '';

        $this->customer_dropdown_open = false;

        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes discarded.', type: 'info');
    }
};
?>

<div>
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-h1 font-h1 text-on-surface">Edit Invoice</h1>
            <p class="mt-1 text-body-md font-body-md text-secondary">
                Edit a custom invoice with multiple services, plans, discounts and customer details.
            </p>
        </div>

        <a href="{{ route('admin.invoices.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Invoices
        </a>
    </div>

    <form wire:submit.prevent="update">
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

                                <div wire:ignore x-data="phoneInput('customer_phone', 'customer_country')">
                                    <input type="tel" x-ref="input" autocomplete="off"
                                        class="w-full rounded border border-outline-variant px-4 py-2.5 font-body-md outline-none transition-all focus:ring-2 focus:ring-primary/10"
                                        placeholder="Phone number" />

                                    <input type="hidden" x-ref="phone" value="{{ $customer_phone }}" />
                                    <input type="hidden" x-ref="country" value="{{ $customer_country }}" />
                                </div>

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
                        <span class="material-symbols-outlined text-primary">add_shopping_cart</span>
                        Add Services / Plans
                    </h3>

                    <div class="grid grid-cols-1 gap-6 {{ $this->selectedServiceHasOptions() ? 'md:grid-cols-3' : 'md:grid-cols-2' }}">
                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Select Service</label>
                            <select wire:model.live="selected_service_id"
                                class="w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="">Select service</option>
                                @foreach ($this->services() as $service)
                                    <option value="{{ $service->id }}">{{ $service->card_title }}</option>
                                @endforeach
                            </select>

                            @unless ($this->selectedServiceHasOptions())
                                <button type="button" wire:click="addService"
                                    class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                    Add Service
                                </button>
                            @endunless
                        </div>

                        @if ($this->selectedServiceHasOptions())
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Select Service Option</label>
                                <select wire:model.live="selected_service_option_id"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5">
                                    <option value="">Select service option</option>
                                    @foreach ($this->serviceOptions() as $option)
                                        <option value="{{ $option->id }}">{{ $option->card_title }}</option>
                                    @endforeach
                                </select>

                                @unless ($this->selectedOptionHasPlans())
                                    <button type="button" wire:click="addServiceOption"
                                        class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                        Add Service Option
                                    </button>
                                @endunless
                            </div>
                        @endif

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Select Service Plan</label>
                            <select wire:model.live="selected_service_plan_id"
                                class="w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="">
                                    {{ $selected_service_id ? 'Select service plan' : 'Select a service first' }}
                                </option>
                                @forelse ($this->servicePlans() as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @empty
                                    @if ($selected_service_id)
                                        <option value="" disabled>
                                            {{ $selected_service_option_id ? 'No plans available for this option' : 'No plans available for this service' }}
                                        </option>
                                    @endif
                                @endforelse
                            </select>

                            @if ($this->selected_service_plan_id && $this->planBillingCycles())
                                <div class="mt-3 space-y-1.5">
                                    <label class="block text-xs font-semibold text-secondary">Billing Cycle</label>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($this->planBillingCycles() as $cycle)
                                            <button type="button" wire:click="$set('selected_billing_cycle', '{{ $cycle['value'] }}')"
                                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer {{ $selected_billing_cycle === $cycle['value'] ? 'border-primary bg-primary/10 text-primary' : 'border-outline-variant text-on-surface hover:bg-slate-50' }}">
                                                {{ $cycle['label'] }}
                                            </button>
                                        @endforeach
                                    </div>

                                    @if ($this->selectedPlanPrice() !== null)
                                        <p class="text-xs text-emerald-600">
                                            Price: ৳{{ number_format($this->selectedPlanPrice(), 2) }}
                                            per {{ $selected_billing_cycle === 'one_time' ? 'service' : $selected_billing_cycle }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <button type="button" wire:click="addServicePlan"
                                class="mt-2 w-full rounded-lg border border-dashed border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                                Add Plan
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
                        Invoice Items
                    </h3>

                    @error('items')
                        <p class="mb-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="space-y-4">
                        @forelse ($items as $index => $item)
                            <div wire:key="invoice-item-{{ $index }}"
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
                                        <input type="number" step="1" min="1" wire:model.live="items.{{ $index }}.quantity"
                                            class="w-full rounded border border-outline-variant bg-white px-3 py-2 text-sm" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="text-xs text-secondary">Unit Price</label>
                                        <input type="number" step="0.1" min="0" wire:model.live="items.{{ $index }}.unit_price"
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

                        <button type="submit" x-data x-on:click="$store.phoneInputs.syncAll()" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2 text-label-md font-label-md text-white shadow-sm transition-opacity hover:opacity-90 cursor-pointer">
                            <span wire:loading.remove wire:target="update">Update Invoice</span>

                            <span wire:loading wire:target="update" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Updating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 space-y-6 lg:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Invoice Details</h3>

                    <div class="space-y-5">
                        <div class="rounded-lg bg-slate-50 p-3 font-mono text-sm text-slate-600">
                            {{ $invoice->invoice_no }}
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Subject</label>
                            <input type="text" wire:model.live="subject"
                                class="w-full rounded border border-outline-variant px-4 py-2.5" />

                            @error('subject')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Issue Date</label>
                                <input type="date" wire:model.live="issue_date"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />

                                @error('issue_date')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block font-label-md text-on-surface">Due Date</label>
                                <input type="date" wire:model.live="due_date"
                                    class="w-full rounded border border-outline-variant px-4 py-2.5" />

                                @error('due_date')
                                    <p class="text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Note</label>
                            <textarea wire:model.live="note" rows="4" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>

                            @error('note')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block font-label-md text-on-surface">Terms & Conditions</label>
                            <textarea wire:model.live="terms" rows="4" class="w-full rounded border border-outline-variant px-4 py-2.5"></textarea>

                            @error('terms')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-h3 font-h2">Discount & Calculation</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-on-surface">Discount Type</label>
                            <select wire:model.live="discount_type"
                                class="mt-2 w-full rounded border border-outline-variant px-4 py-2.5">
                                <option value="none">No Discount</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>

                        @if ($discount_type !== 'none')
                            <div>
                                <label class="block text-sm font-semibold text-on-surface">
                                    {{ $discount_type === 'percentage' ? 'Discount Percentage' : 'Discount Amount' }}
                                </label>
                                <input type="number" min="0" step="0.01" wire:model.live="discount_value"
                                    class="mt-2 w-full rounded border border-outline-variant px-4 py-2.5" />
                            </div>
                        @endif

                        <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-secondary">Subtotal</span>
                                <span class="font-mono text-on-surface">৳{{ number_format($this->subtotal(), 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-secondary">Discount</span>
                                <span class="font-mono text-red-600">-৳{{ number_format($this->discountAmount(), 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                                <span class="font-semibold text-on-surface">Total</span>
                                <span class="font-mono text-base font-bold text-primary">৳{{ number_format($this->grandTotal(), 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
