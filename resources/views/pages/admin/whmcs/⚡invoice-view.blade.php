<?php

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('WHMCS Invoice')] class extends Component {
    public string $invoiceId = '';

    public string $apiError = '';

    public function mount(string $invoiceId): void
    {
        $this->invoiceId = (string) $invoiceId;
    }

    /**
     * Full WHMCS invoice (with line items), cached for five minutes.
     */
    #[Computed]
    public function invoice(): array
    {
        $cacheKey = 'whmcs-admin-invoice:' . $this->invoiceId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['invoiceid'])) {
            return $cached;
        }

        try {
            $response = app(WhmcsApi::class)->request('GetInvoice', [
                'invoiceid' => $this->invoiceId,
            ]);
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return [];
        }

        $invoice = (array) ($response['invoice'] ?? $response);

        Cache::put($cacheKey, $invoice, now()->addMinutes(5));

        return $invoice;
    }

    /**
     * Normalized invoice line items.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function items(): array
    {
        $items = data_get($this->invoice, 'items.item', []);

        if (is_array($items) && ! array_is_list($items)) {
            $items = [$items];
        }

        if (! is_array($items)) {
            return [];
        }

        return array_values($items);
    }

    /**
     * WHMCS client details for the invoice owner (name, email, address).
     */
    #[Computed]
    public function clientDetails(): ?array
    {
        $clientId = (string) data_get($this->invoice, 'userid', '');

        if ($clientId === '') {
            return null;
        }

        $cacheKey = 'whmcs-admin-client-detail:' . $clientId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $details = app(WhmcsApi::class)->getClientDetails($clientId);
        } catch (WhmcsApiException) {
            return null;
        }

        if (! is_array($details)) {
            return null;
        }

        if (isset($details['client']) && is_array($details['client'])) {
            $details = $details['client'];
        }

        Cache::put($cacheKey, $details, now()->addMinutes(30));

        return $details;
    }

    /**
     * Company name + pay-to address shown in the invoice header.
     *
     * @return array{name: string, address: string}
     */
    #[Computed]
    public function payTo(): array
    {
        $cacheKey = 'whmcs-admin-invoice-payto';
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['name'], $cached['address'])) {
            return $cached;
        }

        $name = (string) config('services.whmcs.company_name', config('app.name'));
        $address = (string) config('services.whmcs.company_address', '');

        $api = app(WhmcsApi::class);

        try {
            $company = trim((string) data_get($api->request('GetConfigurationValue', ['setting' => 'CompanyName']), 'value', ''));

            if ($company !== '') {
                $name = $company;
            }
        } catch (WhmcsApiException) {
        }

        try {
            $payTo = trim((string) data_get($api->request('GetConfigurationValue', ['setting' => 'InvoicePayTo']), 'value', ''));

            if ($payTo !== '') {
                $address = trim((string) preg_replace('/<br\s*([^>]*)>/i', "\n", $payTo));
            }
        } catch (WhmcsApiException) {
        }

        $payload = ['name' => $name, 'address' => $address];

        Cache::put($cacheKey, $payload, now()->addHour());

        return $payload;
    }

    public function invoiceNumber(): string
    {
        $invoiceNumber = trim((string) data_get($this->invoice, 'invoicenum', ''));

        if ($invoiceNumber !== '') {
            return $invoiceNumber;
        }

        return (string) data_get($this->invoice, 'invoiceid', $this->invoiceId);
    }

    public function clientName(): string
    {
        $details = $this->clientDetails ?? [];
        $company = trim((string) data_get($details, 'companyname', ''));
        $name = trim((string) data_get($details, 'firstname', '') . ' ' . (string) data_get($details, 'lastname', ''));

        if ($company !== '') {
            return $company;
        }

        return $name !== '' ? $name : 'Unknown';
    }

    public function clientContactName(): string
    {
        $details = $this->clientDetails ?? [];

        return trim((string) data_get($details, 'firstname', '') . ' ' . (string) data_get($details, 'lastname', ''));
    }

    /**
     * @return array<int, string>
     */
    public function clientAddressLines(): array
    {
        $details = $this->clientDetails ?? [];

        $lines = [
            trim((string) data_get($details, 'address1', '')),
            trim((string) data_get($details, 'address2', '')),
            trim(implode(', ', array_filter([
                trim((string) data_get($details, 'city', '')),
                trim((string) data_get($details, 'state', '') ?: (string) data_get($details, 'fullstate', '')),
                trim((string) data_get($details, 'postcode', '')),
            ]))),
            trim((string) data_get($details, 'countryname', '') ?: (string) data_get($details, 'country', '')),
        ];

        return array_values(array_filter($lines, fn ($line) => $line !== ''));
    }

    /**
     * Friendly payment method name ("bKash Payment") for this invoice.
     */
    public function paymentMethodLabel(): string
    {
        $module = trim((string) data_get($this->invoice, 'paymentmethod', ''));

        if ($module === '') {
            return '-';
        }

        $display = $this->paymentMethodMap()[$module] ?? '';

        if ($display !== '') {
            return $display;
        }

        return ucwords((string) preg_replace('/(?<!^)([A-Z])/', ' $1', str_replace(['_', '-'], ' ', $module)));
    }

    /**
     * Module => display name map from WHMCS, cached for one hour.
     *
     * Shares its cache key with the invoice list page.
     *
     * @return array<string, string>
     */
    private function paymentMethodMap(): array
    {
        $cached = Cache::get('whmcs-admin-payment-methods');

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $map = app(WhmcsApi::class)->getPaymentMethods();
        } catch (WhmcsApiException) {
            return [];
        }

        Cache::put('whmcs-admin-payment-methods', $map, now()->addHour());

        return $map;
    }

    public function formatMoney(float|string|int|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2) . ' ' . (string) config('app.currency_code', 'BDT');
    }

    public function formatDate(?string $date): string
    {
        if (! $date || str_starts_with($date, '0000-00-00')) {
            return '-';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    public function statusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'paid' => 'bg-emerald-50 text-emerald-700',
            'unpaid', 'payment pending' => 'bg-amber-50 text-amber-700',
            'draft' => 'bg-slate-100 text-slate-600',
            'cancelled', 'fraud' => 'bg-rose-50 text-rose-700',
            'refunded' => 'bg-blue-50 text-blue-700',
            'collections' => 'bg-orange-50 text-orange-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.whmcs.invoices') }}" wire:navigate
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>

            <div>
                <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                    Invoice #{{ $this->invoiceNumber() }}
                </h2>

                <p class="text-xs font-body-md text-secondary md:text-body-md">
                    WHMCS invoice details rendered on TechWave.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if (($status = (string) data_get($this->invoice, 'status', '')) !== '')
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass($status) }}">
                    {{ ucfirst($status) }}
                </span>
            @endif

            <a href="{{ route('admin.whmcs.invoices.pdf', $this->invoiceId) }}?download=1" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-label-md font-label-md text-on-primary transition-all hover:shadow-lg hover:shadow-primary/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-lg">download</span>
                Download PDF
            </a>
        </div>
    </div>

    @if ($apiError)
        <div class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-body-sm font-body-sm text-rose-700">
            <span class="material-symbols-outlined text-lg">error</span>
            {{ $apiError }}
        </div>
    @endif

    @if (! $apiError && count($this->invoice))
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            {{-- Brand + Invoice meta --}}
            <div class="flex flex-col justify-between gap-5 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start">
                <div>
                    <p class="text-base font-bold uppercase text-on-surface">
                        {{ $this->payTo['name'] }}
                    </p>

                    @if ($this->payTo['address'] !== '')
                        <p class="mt-1.5 whitespace-pre-line text-body-sm font-body-sm text-secondary">
                            {{ $this->payTo['address'] }}
                        </p>
                    @endif
                </div>

                <div class="sm:text-right">
                    <p class="text-2xl font-black text-primary">INVOICE</p>

                    <div class="mt-2 space-y-0.5 text-body-sm font-body-sm">
                        <p><span class="text-secondary">Invoice #:</span> <span class="font-semibold text-on-surface">{{ $this->invoiceNumber() }}</span></p>
                        <p><span class="text-secondary">Date:</span> <span class="font-semibold text-on-surface">{{ $this->formatDate(data_get($this->invoice, 'date')) }}</span></p>
                        <p><span class="text-secondary">Due Date:</span> <span class="font-semibold text-on-surface">{{ $this->formatDate(data_get($this->invoice, 'duedate')) }}</span></p>
                        <p><span class="text-secondary">Paid Date:</span> <span class="font-semibold text-on-surface">{{ $this->formatDate(data_get($this->invoice, 'datepaid')) }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Bill To / Payment info --}}
            <div class="grid gap-px bg-slate-100 sm:grid-cols-2">
                <div class="bg-white px-6 py-5">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Bill To</p>

                    <p class="mt-2 text-[15px] font-bold text-on-surface">
                        {{ $this->clientName() }}
                    </p>

                    @if (($contact = $this->clientContactName()) !== '' && $contact !== $this->clientName())
                        <p class="text-body-sm font-body-sm text-on-surface">{{ $contact }}</p>
                    @endif

                    @if (($email = (string) data_get($this->clientDetails, 'email', '')) !== '')
                        <p class="mt-1 text-body-sm font-body-sm text-secondary">{{ $email }}</p>
                    @endif

                    @if (count($addressLines = $this->clientAddressLines()))
                        <div class="mt-1.5 text-body-sm font-body-sm text-secondary">
                            @foreach ($addressLines as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (($phone = (string) data_get($this->clientDetails, 'phonenumberformatted', '') ?: (string) data_get($this->clientDetails, 'phonenumber', '')) !== '')
                        <p class="mt-1 text-body-sm font-body-sm text-secondary">{{ $phone }}</p>
                    @endif
                </div>

                <div class="bg-white px-6 py-5 sm:text-right">
                    <p class="text-label-sm font-label-sm uppercase tracking-wider text-secondary">Payment</p>

                    <p class="mt-2 text-[15px] font-bold text-on-surface">
                        {{ $this->paymentMethodLabel() }}
                    </p>

                    <p class="mt-1 text-body-sm font-body-sm text-secondary">
                        Status: <span class="font-semibold {{ strtolower((string) data_get($this->invoice, 'status', '')) === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst((string) data_get($this->invoice, 'status', 'N/A')) }}</span>
                    </p>

                    <p class="mt-1 text-body-sm font-body-sm text-secondary">
                        WHMCS Client ID: <span class="font-mono font-semibold text-on-surface">{{ data_get($this->invoice, 'userid', '-') }}</span>
                    </p>
                </div>
            </div>

            {{-- Line items --}}
            <div class="overflow-x-auto px-6 py-4">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="px-4 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary w-12">#</th>
                            <th class="px-4 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary">Description</th>
                            <th class="px-4 py-3 text-label-sm font-label-sm uppercase tracking-wider text-secondary text-right">Amount</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->items as $index => $item)
                            <tr>
                                <td class="px-4 py-3 text-body-sm font-body-sm text-secondary">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 py-3">
                                    <p class="text-body-sm font-body-sm text-on-surface">
                                        {{ data_get($item, 'description', '-') }}
                                    </p>

                                    @if (($type = (string) data_get($item, 'type', '')) !== '')
                                        <span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                            {{ $type }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-body-sm font-label-sm text-on-surface">
                                    {{ $this->formatMoney(data_get($item, 'amount', 0)) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-body-sm text-secondary">
                                    No line items found on this invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="flex justify-end border-t border-slate-100 bg-slate-50/30 px-6 py-5">
                <dl class="w-full max-w-xs space-y-2">
                    <div class="flex items-center justify-between text-body-sm font-body-sm">
                        <dt class="text-secondary">Subtotal</dt>
                        <dd class="text-on-surface">{{ $this->formatMoney(data_get($this->invoice, 'subtotal', 0)) }}</dd>
                    </div>

                    @if ((float) data_get($this->invoice, 'credit', 0) > 0)
                        <div class="flex items-center justify-between text-body-sm font-body-sm">
                            <dt class="text-secondary">Credit</dt>
                            <dd class="text-on-surface">-{{ $this->formatMoney(data_get($this->invoice, 'credit', 0)) }}</dd>
                        </div>
                    @endif

                    @if ((float) data_get($this->invoice, 'tax', 0) > 0)
                        <div class="flex items-center justify-between text-body-sm font-body-sm">
                            <dt class="text-secondary">Tax {{ rtrim(rtrim(number_format((float) data_get($this->invoice, 'taxrate', 0), 3), '0'), '.') }}%</dt>
                            <dd class="text-on-surface">{{ $this->formatMoney(data_get($this->invoice, 'tax', 0)) }}</dd>
                        </div>
                    @endif

                    @if ((float) data_get($this->invoice, 'tax2', 0) > 0)
                        <div class="flex items-center justify-between text-body-sm font-body-sm">
                            <dt class="text-secondary">Tax 2</dt>
                            <dd class="text-on-surface">{{ $this->formatMoney(data_get($this->invoice, 'tax2', 0)) }}</dd>
                        </div>
                    @endif

                    <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-label-md font-label-md">
                        <dt class="text-on-surface">Total</dt>
                        <dd class="font-bold text-on-surface">{{ $this->formatMoney(data_get($this->invoice, 'total', 0)) }}</dd>
                    </div>

                    <div class="flex items-center justify-between text-label-md font-label-md">
                        <dt class="text-secondary">Balance</dt>
                        <dd class="font-bold {{ (float) data_get($this->invoice, 'balance', 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                            {{ $this->formatMoney(data_get($this->invoice, 'balance', 0)) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    @elseif (! $apiError)
        <div class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center">
            <div class="mx-auto flex max-w-sm flex-col items-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>

                <h3 class="text-base font-semibold text-on-surface">Invoice not found</h3>

                <p class="mt-1 text-sm text-secondary">
                    This invoice could not be loaded from WHMCS.
                </p>
            </div>
        </div>
    @endif
</div>
