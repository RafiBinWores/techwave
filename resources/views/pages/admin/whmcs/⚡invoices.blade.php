<?php

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('WHMCS Invoices')] class extends Component {
    public string $search = '';

    public string $status = 'all';

    public int $perPage = 25;

    public int $page = 1;

    public string $apiError = '';

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function refresh(): void
    {
        Cache::forget('whmcs-admin-all-invoices');
        Cache::forget('whmcs-admin-payment-methods');
        Cache::forget('whmcs-admin-client-directory');
        $this->apiError = '';
        $this->page = 1;
        $this->dispatch('toast', message: 'Invoice list has been refreshed.', type: 'success');
    }

    public function previousPage(): void
    {
        $this->gotoPage($this->page - 1);
    }

    public function nextPage(): void
    {
        $this->gotoPage($this->page + 1);
    }

    public function gotoPage(int $page): void
    {
        $lastPage = max(1, (int) ceil(count($this->filteredInvoices()) / $this->perPage));

        $this->page = min(max(1, $page), $lastPage);
    }

    /**
     * Paginated, filtered invoice rows for the table.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, from: int, to: int}
     */
    public function paginatedInvoices(): array
    {
        $items = $this->filteredInvoices();
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $page = min(max(1, $this->page), $lastPage);
        $offset = ($page - 1) * $this->perPage;

        return [
            'items' => array_slice($items, $offset, $this->perPage),
            'total' => $total,
            'page' => $page,
            'lastPage' => $lastPage,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $this->perPage, $total),
        ];
    }

    /**
     * Invoices matching the current search and status filters.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filteredInvoices(): array
    {
        $status = $this->status;
        $search = trim($this->search);

        return array_values(array_filter($this->allInvoices()['items'], function (array $invoice) use ($status, $search) {
            if ($status !== 'all' && strcasecmp((string) data_get($invoice, 'status', ''), $status) !== 0) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', [
                (string) data_get($invoice, 'id', ''),
                (string) data_get($invoice, 'invoicenum', ''),
                (string) data_get($invoice, 'userid', ''),
                (string) data_get($invoice, 'firstname', ''),
                (string) data_get($invoice, 'lastname', ''),
                (string) data_get($invoice, 'companyname', ''),
                (string) data_get($invoice, 'paymentmethod', ''),
                $this->paymentMethodLabel($invoice),
                $this->clientEmail($invoice),
            ]);

            return stripos($haystack, $search) !== false;
        }));
    }

    /**
     * All WHMCS invoices, fetched once and cached for five minutes.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function allInvoices(): array
    {
        $cached = Cache::get('whmcs-admin-all-invoices');

        if (is_array($cached) && isset($cached['items'], $cached['total'])) {
            return $cached;
        }

        try {
            $payload = app(WhmcsApi::class)->getAllInvoices();
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return ['items' => [], 'total' => 0];
        }

        Cache::put('whmcs-admin-all-invoices', $payload, now()->addMinutes(5));

        return $payload;
    }

    /**
     * Friendly payment method name for an invoice ("bKash Payment"), falling
     * back to a prettified module key when WHMCS has no display name.
     */
    public function paymentMethodLabel(array $invoice): string
    {
        $module = trim((string) data_get($invoice, 'paymentmethod', ''));

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
     * Failures are not cached so the next request retries.
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

    public function clientName(array $invoice): string
    {
        $company = trim((string) data_get($invoice, 'companyname', ''));
        $name = trim((string) data_get($invoice, 'firstname', '') . ' ' . (string) data_get($invoice, 'lastname', ''));

        if ($company !== '') {
            return $company;
        }

        return $name !== '' ? $name : 'Unknown';
    }

    /**
     * Email (or the contact's full name) shown under the client name.
     */
    public function clientSubtitle(array $invoice): string
    {
        $email = $this->clientEmail($invoice);

        if ($email !== '') {
            return $email;
        }

        $name = trim((string) data_get($invoice, 'firstname', '') . ' ' . (string) data_get($invoice, 'lastname', ''));

        if ($name !== '' && strcasecmp($name, $this->clientName($invoice)) !== 0) {
            return $name;
        }

        return '';
    }

    /**
     * Client email from the cached WHMCS client directory.
     */
    public function clientEmail(array $invoice): string
    {
        $id = (string) data_get($invoice, 'userid', '');

        if ($id === '') {
            return '';
        }

        return $this->clientDirectory()[$id]['email'] ?? '';
    }

    /**
     * Client id => profile map, cached for five minutes.
     *
     * Failures are not cached so the next request retries.
     *
     * @return array<string, array{firstname: string, lastname: string, email: string, companyname: string}>
     */
    private function clientDirectory(): array
    {
        $cached = Cache::get('whmcs-admin-client-directory');

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $map = app(WhmcsApi::class)->getClientDirectory();
        } catch (WhmcsApiException) {
            return [];
        }

        Cache::put('whmcs-admin-client-directory', $map, now()->addMinutes(5));

        return $map;
    }

    public function formatMoney(array $invoice): string
    {
        $number = number_format((float) data_get($invoice, 'total', 0), 2);
        $prefix = (string) data_get($invoice, 'currencyprefix', '');

        if ($prefix !== '') {
            return $prefix . $number;
        }

        return $number . ' ' . (string) data_get($invoice, 'currencycode', config('app.currency_code', 'BDT'));
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
    <!-- Header Section -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                WHMCS Invoices
            </h2>

            <p class="text-xs font-body-md text-secondary md:text-body-md">
                Browse every invoice from your WHMCS billing system.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    search
                </span>

                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search invoice #, name or email..."
                    class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-64" />
            </div>

            <div class="relative">
                <select wire:model.live="status"
                    class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-44">
                    <option value="all">All Status</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="payment pending">Payment Pending</option>
                    <option value="draft">Draft</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                    <option value="fraud">Fraud</option>
                    <option value="collections">Collections</option>
                </select>

                <span
                    class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    expand_more
                </span>
            </div>

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

    @php
        $results = $this->paginatedInvoices();
    @endphp

    <!-- Table Container -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Invoice
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Client
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Issued
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Due
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Paid
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Total
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Status
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Payment
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary text-right">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($results['items'] as $invoice)
                        @php
                            $invoiceStatus = (string) data_get($invoice, 'status', '');
                            $invoiceId = (string) data_get($invoice, 'id');
                            $invoiceNumber = (string) data_get($invoice, 'invoicenum');
                        @endphp

                        <tr wire:key="whmcs-invoice-{{ $invoiceId }}" class="transition-colors hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined text-lg">receipt</span>
                                    </span>

                                    <span class="font-mono text-body-sm font-body-sm text-on-surface">
                                        #{{ $invoiceNumber !== '' ? $invoiceNumber : $invoiceId }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-label-md font-label-md text-on-surface">
                                        {{ $this->clientName($invoice) }}
                                    </span>

                                    @if (($subtitle = $this->clientSubtitle($invoice)) !== '')
                                        <span class="text-body-sm font-body-sm text-secondary">
                                            {{ $subtitle }}
                                        </span>
                                    @endif
                                </div>
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

                            <td class="px-6 py-4">
                                <span class="text-body-sm font-label-sm text-on-surface">
                                    {{ $this->formatMoney($invoice) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass($invoiceStatus) }}">
                                    {{ $invoiceStatus !== '' ? ucfirst($invoiceStatus) : 'N/A' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-body-sm font-body-sm text-secondary">
                                    {{ $this->paymentMethodLabel($invoice) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button type="button" @click="open = !open"
                                        class="text-slate-400 transition-colors hover:text-primary">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>

                                    <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                        class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                        <a href="{{ route('admin.whmcs.invoices.view', $invoiceId) }}" wire:navigate
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-on-surface transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            View Invoice
                                        </a>

                                        <a href="{{ route('admin.whmcs.invoices.pdf', $invoiceId) }}?download=1" target="_blank" rel="noopener"
                                            @click="open = false"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-on-surface transition hover:bg-slate-50">
                                            <span class="material-symbols-outlined text-[18px]">download</span>
                                            Download PDF
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div
                                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <span class="material-symbols-outlined">receipt_long</span>
                                    </div>

                                    <h3 class="text-base font-semibold text-on-surface">
                                        No invoices found
                                    </h3>

                                    <p class="mt-1 text-sm text-secondary">
                                        @if ($search !== '' || $status !== 'all')
                                            No invoices match your current search or filter.
                                        @else
                                            Your WHMCS account has no invoices yet.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="text-body-sm font-body-sm text-secondary">
                    Per page
                </span>

                <select wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 focus:border-primary focus:ring-primary/10">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <span class="text-body-sm font-body-sm text-secondary">
                    Showing {{ $results['from'] }}&ndash;{{ $results['to'] }} of {{ $results['total'] }}
                </span>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="previousPage" @disabled($results['page'] <= 1)
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>

                    <span class="text-body-sm font-body-sm text-on-surface">
                        Page {{ $results['page'] }} of {{ $results['lastPage'] }}
                    </span>

                    <button type="button" wire:click="nextPage" @disabled($results['page'] >= $results['lastPage'])
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
