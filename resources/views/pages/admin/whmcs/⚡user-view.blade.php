<?php

use App\Models\WhmcsAccount;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('WHMCS User')] class extends Component {
    public string $userId = '';

    public string $tab = 'overview';

    public string $apiError = '';

    public function mount(string $userId): void
    {
        $this->userId = (string) $userId;
    }

    /**
     * Tabs rendered on the page.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    #[Computed]
    public function tabs(): array
    {
        return [
            'overview' => ['label' => 'Overview', 'icon' => 'person'],
            'services' => ['label' => 'Services', 'icon' => 'dns'],
            'domains' => ['label' => 'Domains', 'icon' => 'language'],
            'invoices' => ['label' => 'Invoices', 'icon' => 'receipt_long'],
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, $this->tabs) ? $tab : 'overview';
    }

    public function refresh(): void
    {
        foreach (
            [
                'whmcs-admin-client-detail:' . $this->userId,
                'whmcs-admin-user-services:' . $this->userId,
                'whmcs-admin-user-domains:' . $this->userId,
                'whmcs-admin-user-invoices:' . $this->userId,
            ] as $cacheKey
        ) {
            Cache::forget($cacheKey);
        }

        $this->apiError = '';
        $this->dispatch('toast', message: 'Client data has been refreshed.', type: 'success');
    }

    /**
     * Full WHMCS client profile, cached for thirty minutes.
     *
     * Shares its cache key with the user list and invoice view pages.
     */
    #[Computed]
    public function client(): array
    {
        $cacheKey = 'whmcs-admin-client-detail:' . $this->userId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $details = app(WhmcsApi::class)->getClientDetails($this->userId);

        if (! is_array($details)) {
            $this->apiError = 'Could not load this client from the billing system.';

            return [];
        }

        if (isset($details['client']) && is_array($details['client'])) {
            $details = $details['client'];
        }

        Cache::put($cacheKey, $details, now()->addMinutes(30));

        return $details;
    }

    /**
     * Products/services owned by the client, cached for ten minutes.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function services(): array
    {
        $cacheKey = 'whmcs-admin-user-services:' . $this->userId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $services = app(WhmcsApi::class)->getClientProductsXml($this->userId);
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return [];
        }

        Cache::put($cacheKey, $services, now()->addMinutes(10));

        return $services;
    }

    /**
     * Domains registered by the client, cached for ten minutes.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function domains(): array
    {
        $cacheKey = 'whmcs-admin-user-domains:' . $this->userId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $domains = app(WhmcsApi::class)->getClientDomains($this->userId);
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return [];
        }

        Cache::put($cacheKey, $domains, now()->addMinutes(10));

        return $domains;
    }

    /**
     * Invoices belonging to the client, cached for five minutes.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function invoices(): array
    {
        $cacheKey = 'whmcs-admin-user-invoices:' . $this->userId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $invoices = app(WhmcsApi::class)->getInvoices($this->userId);
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return [];
        }

        Cache::put($cacheKey, $invoices, now()->addMinutes(5));

        return $invoices;
    }

    /**
     * Portal account linked to this WHMCS client, if any.
     */
    #[Computed]
    public function portalAccount(): ?WhmcsAccount
    {
        return WhmcsAccount::query()
            ->with('user')
            ->where(function ($query) {
                $query->where('whmcs_client_id', $this->userId)
                    ->orWhere('whmcs_user_id', $this->userId);
            })
            ->first();
    }

    /**
     * Contacts (owner + sub-accounts) attached to the WHMCS client.
     *
     * @return array<int, array<string, mixed>>
     */
    public function contacts(): array
    {
        $contacts = data_get($this->client, 'users.user', []);

        if (is_array($contacts) && ! array_is_list($contacts)) {
            $contacts = [$contacts];
        }

        if (! is_array($contacts)) {
            return [];
        }

        return array_values($contacts);
    }

    public function clientName(): string
    {
        $company = trim((string) data_get($this->client, 'companyname', ''));
        $name = $this->clientContactName();

        if ($company !== '') {
            return $company;
        }

        return $name !== '' ? $name : 'Unknown';
    }

    public function clientContactName(): string
    {
        return trim(
            (string) data_get($this->client, 'firstname', '') . ' ' . (string) data_get($this->client, 'lastname', '')
        );
    }

    /**
     * Account creation date.
     *
     * GetClientsDetails has no creation date, so it falls back to the
     * GetClients list payload used by the user list page.
     */
    public function signedUpDate(): string
    {
        $date = (string) (data_get($this->client, 'datecreated') ?: data_get($this->client, 'signupdate') ?: '');

        if ($date === '') {
            foreach ($this->clientListItems() as $row) {
                if ((string) data_get($row, 'id', '') !== $this->userId) {
                    continue;
                }

                $date = (string) (data_get($row, 'datecreated') ?: data_get($row, 'signupdate') ?: '');

                break;
            }
        }

        return $date !== '' ? $this->formatDate($date) : '-';
    }

    /**
     * Client rows from the GetClients list, cached for five minutes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function clientListItems(): array
    {
        $cached = Cache::get('whmcs-admin-all-clients');

        if (is_array($cached) && isset($cached['items'])) {
            return $cached['items'];
        }

        try {
            $payload = app(WhmcsApi::class)->getAllClients();
        } catch (WhmcsApiException) {
            return [];
        }

        Cache::put('whmcs-admin-all-clients', $payload, now()->addMinutes(5));

        return $payload['items'];
    }

    /**
     * Phone number rebuilt from the raw number and country code, because
     * WHMCS formats it with a dot ("+880.1776538827").
     */
    public function phone(): string
    {
        $phone = trim((string) data_get($this->client, 'phonenumber', ''));

        if ($phone === '') {
            $formatted = trim((string) data_get($this->client, 'phonenumberformatted', ''));

            return $formatted !== '' ? str_replace('.', ' ', $formatted) : '-';
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $countryCode = trim((string) data_get($this->client, 'phonecc', ''));

        return $countryCode !== '' ? '+' . $countryCode . ' ' . $phone : $phone;
    }

    /**
     * @return array<int, string>
     */
    public function addressLines(): array
    {
        $lines = [
            trim((string) data_get($this->client, 'address1', '')),
            trim((string) data_get($this->client, 'address2', '')),
            trim(implode(', ', array_filter([
                trim((string) data_get($this->client, 'city', '')),
                trim((string) (data_get($this->client, 'state') ?: data_get($this->client, 'fullstate'))),
                trim((string) data_get($this->client, 'postcode', '')),
            ]))),
            trim((string) (data_get($this->client, 'countryname') ?: data_get($this->client, 'country'))),
        ];

        return array_values(array_filter($lines, fn($line) => $line !== ''));
    }

    /**
     * Last login payload from WHMCS ("Date: ...<br>IP Address: ...").
     */
    public function lastLogin(): string
    {
        $raw = trim((string) data_get($this->client, 'lastlogin', ''));

        if ($raw === '') {
            return '-';
        }

        $text = trim((string) preg_replace('/<br\s*([^>]*)>/i', "\n", strip_tags($raw)));

        return $text !== '' ? $text : '-';
    }

    /**
     * WHMCS admin profile URL for this client.
     */
    #[Computed]
    public function profileUrl(): string
    {
        return rtrim(app(WhmcsApi::class)->adminUrl(), '/') . '/clientssummary.php?userid=' . $this->userId;
    }

    public function formatDate(?string $date): string
    {
        if (! $date || str_starts_with($date, '0000-00-00')) {
            return '-';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function formatMoney(float|string|int|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2) . ' ' . (string) config('app.currency_code', 'BDT');
    }

    /**
     * Invoice total using the invoice's own currency prefix/code.
     */
    public function invoiceMoney(array $invoice): string
    {
        $number = number_format((float) data_get($invoice, 'total', 0), 2);
        $prefix = (string) data_get($invoice, 'currencyprefix', '');

        if ($prefix !== '') {
            return $prefix . $number;
        }

        return $number . ' ' . (string) data_get($invoice, 'currencycode', config('app.currency_code', 'BDT'));
    }

    public function statusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'active', 'paid' => 'bg-emerald-50 text-emerald-700',
            'pending', 'unpaid', 'payment pending' => 'bg-amber-50 text-amber-700',
            'suspended', 'cancelled', 'terminated', 'expired', 'fraud' => 'bg-rose-50 text-rose-700',
            'completed', 'refunded' => 'bg-blue-50 text-blue-700',
            'failed', 'collections' => 'bg-orange-50 text-orange-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function label(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' && ! str_starts_with($value, '0000-00-00') ? $value : '-';
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.whmcs.users') }}" wire:navigate
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>

            <div class="min-w-0">
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    {{ count($this->client) ? $this->clientName() : 'WHMCS Client #' . $userId }}
                </h2>

                <p class="truncate text-xs font-body-md text-secondary md:text-body-md">
                    @if (count($this->client))
                    {{ $this->label(data_get($this->client, 'email')) }} &middot; WHMCS Client #{{ $userId }}
                    @else
                    WHMCS client profile
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">


            <button type="button" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-label-md font-label-md text-on-surface transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="refresh" class="material-symbols-outlined text-lg">refresh</span>
                <span wire:loading wire:target="refresh" class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
                Refresh
            </button>
        </div>
    </div>

    @if ($apiError)
    <div class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-body-sm font-body-sm text-rose-700">
        <span class="material-symbols-outlined text-lg">error</span>
        {{ $apiError }}
    </div>
    @endif

    @if (count($this->client))
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white" x-data="{ switching: false }">
        {{-- Tabs --}}
        <div class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50/50 px-2">
            @foreach ($this->tabs as $key => $tabData)
            <button type="button"
                @click="if (! switching) { switching = true; $wire.setTab('{{ $key }}').finally(() => switching = false) }"
                :disabled="switching"
                class="inline-flex shrink-0 cursor-pointer items-center gap-2 border-b-2 px-4 py-3.5 text-label-md font-label-md transition disabled:cursor-wait disabled:opacity-50 {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-on-surface' }}">
                <span class="material-symbols-outlined text-lg">{{ $tabData['icon'] }}</span>
                {{ $tabData['label'] }}
            </button>
            @endforeach
        </div>

        {{-- Tab panels --}}
        <div class="relative">
            <div x-show="switching" x-cloak x-transition.opacity.duration.150ms
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 text-center backdrop-blur-[1px]">
                <div class="mx-auto flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-label-md font-label-md text-secondary shadow-sm">
                    <span class="material-symbols-outlined animate-spin text-lg text-primary">progress_activity</span>
                    Loading tab data...
                </div>
            </div>

        {{-- Overview --}}
        @if ($tab === 'overview')
        <div class="space-y-px bg-slate-100">
            <div class="grid gap-px bg-slate-100 md:grid-cols-2">
                {{-- Profile --}}
                <div class="bg-white px-6 py-5">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Profile</p>

                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-lg font-bold uppercase text-primary">
                            {{ str($this->clientName())->substr(0, 1) }}
                        </span>

                        <div class="min-w-0">
                            <p class="truncate text-[15px] font-bold text-on-surface">{{ $this->clientName() }}</p>

                            @if (($contact = $this->clientContactName()) !== '' && $contact !== $this->clientName())
                            <p class="truncate text-body-sm font-body-sm text-secondary">{{ $contact }}</p>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 text-body-sm font-body-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Email</dt>
                            <dd class="truncate text-on-surface">{{ $this->label(data_get($this->client, 'email')) }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Phone</dt>
                            <dd class="text-on-surface">{{ $this->phone() }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Client ID</dt>
                            <dd class="font-mono text-on-surface">{{ $userId }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Signed Up</dt>
                            <dd class="text-on-surface">{{ $this->signedUpDate() }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Last Login</dt>
                            <dd class="whitespace-pre-line text-right text-on-surface">{{ $this->lastLogin() }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Address --}}
                <div class="bg-white px-6 py-5">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Address</p>

                    @if (count($addressLines = $this->addressLines()))
                    <div class="mt-3 space-y-1 text-body-sm font-body-sm text-on-surface">
                        @foreach ($addressLines as $line)
                        <p>{{ $line }}</p>
                        @endforeach
                    </div>
                    @else
                    <p class="mt-3 text-body-sm font-body-sm text-secondary">No address on file.</p>
                    @endif

                    <p class="mt-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Portal Link</p>

                    @if ($account = $this->portalAccount)
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-primary/20 bg-primary/5 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-on-surface">{{ $account->user?->name ?? 'Deleted User' }}</p>
                            <p class="truncate text-xs text-secondary">{{ $account->email }}</p>
                        </div>

                        @if ($account->user)
                        <a href="{{ route('admin.users.edit', $account->user) }}" wire:navigate
                            class="shrink-0 text-label-sm font-label-sm text-primary hover:underline">
                            View
                        </a>
                        @endif
                    </div>
                    @else
                    <p class="mt-3 text-body-sm font-body-sm text-secondary">
                        No portal account is linked to this billing client.
                    </p>
                    @endif
                </div>
            </div>

            <div class="grid gap-px bg-slate-100 md:grid-cols-2">
                {{-- Account --}}
                <div class="bg-white px-6 py-5">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Account</p>

                    <dl class="mt-3 space-y-2 text-body-sm font-body-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Status</dt>
                            <dd>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass((string) data_get($this->client, 'status', '')) }}">
                                    {{ $this->label(data_get($this->client, 'status')) }}
                                </span>
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Currency</dt>
                            <dd class="text-on-surface">{{ $this->label(data_get($this->client, 'currency_code')) }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Credit Balance</dt>
                            <dd class="text-on-surface">{{ $this->formatMoney(data_get($this->client, 'credit', 0)) }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Default Gateway</dt>
                            <dd class="text-on-surface">{{ $this->label(data_get($this->client, 'defaultgateway')) }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Tax Exempt</dt>
                            <dd class="text-on-surface">{{ filter_var(data_get($this->client, 'taxexempt'), FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No' }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Email Verified</dt>
                            <dd class="text-on-surface">{{ filter_var(data_get($this->client, 'email_verified'), FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No' }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-secondary">Marketing Emails</dt>
                            <dd class="text-on-surface">{{ filter_var(data_get($this->client, 'marketing_emails_opt_in'), FILTER_VALIDATE_BOOLEAN) ? 'Subscribed' : 'Not subscribed' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Contacts --}}
                <div class="bg-white px-6 py-5">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Contacts</p>

                    @if (count($contacts = $this->contacts()))
                    <div class="mt-3 space-y-2">
                        @foreach ($contacts as $contact)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-on-surface">
                                    {{ $this->label(data_get($contact, 'name')) }}
                                </p>

                                <p class="truncate text-xs text-secondary">
                                    {{ $this->label(data_get($contact, 'email')) }}
                                </p>
                            </div>

                            @if (filter_var(data_get($contact, 'is_owner'), FILTER_VALIDATE_BOOLEAN))
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                Owner
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="mt-3 text-body-sm font-body-sm text-secondary">No contacts found.</p>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white px-6 py-5">
                <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Notes</p>

                @if (trim((string) data_get($this->client, 'notes', '')) !== '')
                <p class="mt-3 whitespace-pre-line text-body-sm font-body-sm text-on-surface">
                    {{ data_get($this->client, 'notes') }}
                </p>
                @else
                <p class="mt-3 text-body-sm font-body-sm text-secondary">No notes on this account.</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Services --}}
        @if ($tab === 'services')
        <div class="flex items-center justify-between px-6 py-4">
            <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Products &amp; Services</p>
            <span class="text-body-sm font-body-sm text-secondary">{{ count($this->services) }} total</span>
        </div>

        <div class="overflow-x-auto border-t border-slate-100">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Service</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Domain</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Billing</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Amount</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Next Due</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->services as $service)
                    <tr wire:key="service-{{ data_get($service, 'id') }}" class="transition-colors hover:bg-slate-50/80">
                        <td class="px-6 py-4">
                            @if ($group = trim((string) (data_get($service, 'translated_groupname') ?: data_get($service, 'groupname', ''))))
                            <p class="text-xs text-secondary">{{ $group }}</p>
                            @endif

                            <p class="text-body-sm font-body-sm text-on-surface">
                                {{ (string) (data_get($service, 'translated_name') ?: data_get($service, 'name', 'Service')) }}
                            </p>

                            @if ($username = trim((string) (is_array(data_get($service, 'username')) ? '' : data_get($service, 'username', ''))))
                            <p class="mt-0.5 font-mono text-xs text-secondary">{{ $username }}</p>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                            {{ $this->label(data_get($service, 'domain')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                            {{ $this->label(data_get($service, 'billingcycle')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-label-sm text-on-surface">
                            {{ $this->formatMoney(data_get($service, 'recurringamount', 0)) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($service, 'nextduedate')) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass((string) data_get($service, 'status', '')) }}">
                                {{ $this->label(data_get($service, 'status')) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-body-sm text-secondary">
                            No services found for this client.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Domains --}}
        @if ($tab === 'domains')
        <div class="flex items-center justify-between px-6 py-4">
            <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Domains</p>
            <span class="text-body-sm font-body-sm text-secondary">{{ count($this->domains) }} total</span>
        </div>

        <div class="overflow-x-auto border-t border-slate-100">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Domain</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Type</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Registrar</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Registered</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Expires</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Amount</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->domains as $domain)
                    <tr wire:key="domain-{{ data_get($domain, 'id') }}" class="transition-colors hover:bg-slate-50/80">
                        <td class="px-6 py-4 text-body-sm font-body-sm font-semibold text-on-surface">
                            {{ $this->label(data_get($domain, 'domainname')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                            {{ $this->label(data_get($domain, 'regtype')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                            {{ $this->label(data_get($domain, 'registrar')) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($domain, 'regdate')) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($domain, 'expirydate')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-label-sm text-on-surface">
                            {{ $this->formatMoney(data_get($domain, 'recurringamount', 0)) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass((string) data_get($domain, 'status', '')) }}">
                                {{ $this->label(data_get($domain, 'status')) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-body-sm text-secondary">
                            No domains found for this client.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Invoices --}}
        @if ($tab === 'invoices')
        <div class="flex items-center justify-between px-6 py-4">
            <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Invoices</p>
            <span class="text-body-sm font-body-sm text-secondary">{{ count($this->invoices) }} total</span>
        </div>

        <div class="overflow-x-auto border-t border-slate-100">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Invoice</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Issued</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Due</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Paid</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Total</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Status</th>
                        <th class="px-6 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->invoices as $invoice)
                    @php
                    $invoiceId = (string) data_get($invoice, 'id');
                    $invoiceNumber = (string) data_get($invoice, 'invoicenum');
                    @endphp

                    <tr wire:key="invoice-{{ $invoiceId }}" class="transition-colors hover:bg-slate-50/80">
                        <td class="px-6 py-4 font-mono text-body-sm font-body-sm text-on-surface">
                            #{{ $invoiceNumber !== '' ? $invoiceNumber : $invoiceId }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($invoice, 'date')) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($invoice, 'duedate')) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->formatDate(data_get($invoice, 'datepaid')) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-label-sm text-on-surface">
                            {{ $this->invoiceMoney($invoice) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass((string) data_get($invoice, 'status', '')) }}">
                                {{ $this->label(data_get($invoice, 'status')) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.whmcs.invoices.view', $invoiceId) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 text-label-sm font-label-sm text-white bg-yellow-500 px-1.5 py-1.5 rounded hover:bg-yellow-600">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>

                                <a href="{{ route('admin.whmcs.invoices.pdf', $invoiceId) }}?download=1" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-label-sm font-label-sm text-white bg-blue-600 px-1.5 py-1 rounded hover:bg-blue-700">
                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-body-sm text-secondary">
                            No invoices found for this client.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
        </div>
    </div>
    @elseif (! $apiError)
    <div class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center">
        <div class="mx-auto flex max-w-sm flex-col items-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <span class="material-symbols-outlined">person_off</span>
            </div>

            <h3 class="text-base font-semibold text-on-surface">Client not found</h3>

            <p class="mt-1 text-sm text-secondary">
                This client could not be loaded from WHMCS.
            </p>
        </div>
    </div>
    @endif
</div>