<?php

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin-app')] #[Title('WHMCS Users')] class extends Component {
    public string $search = '';

    public string $status = 'all';

    public int $perPage = 25;

    public int $page = 1;

    public string $apiError = '';

    /** @var array<string, array<string, mixed>> Per-request memo of client detail payloads. */
    private array $detailsMemo = [];

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
        Cache::forget('whmcs-admin-all-clients');
        $this->apiError = '';
        $this->page = 1;
        $this->dispatch('toast', message: 'User list has been refreshed.', type: 'success');
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
        $lastPage = max(1, (int) ceil(count($this->filteredUsers()) / $this->perPage));

        $this->page = min(max(1, $page), $lastPage);
    }

    /**
     * Paginated, filtered user rows for the table.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, from: int, to: int}
     */
    public function paginatedUsers(): array
    {
        $items = $this->filteredUsers();
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
     * Users matching the current search and status filters.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filteredUsers(): array
    {
        $status = $this->status;
        $search = trim($this->search);

        return array_values(array_filter($this->allUsers()['items'], function (array $user) use ($status, $search) {
            if ($status !== 'all' && strcasecmp((string) data_get($user, 'status', ''), $status) !== 0) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', [
                (string) data_get($user, 'id', ''),
                (string) data_get($user, 'firstname', ''),
                (string) data_get($user, 'lastname', ''),
                (string) data_get($user, 'companyname', ''),
                (string) data_get($user, 'email', ''),
                (string) data_get($user, 'phonenumber', ''),
                (string) data_get($user, 'country', ''),
            ]);

            return stripos($haystack, $search) !== false;
        }));
    }

    /**
     * All WHMCS clients, fetched once and cached for five minutes.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function allUsers(): array
    {
        $cached = Cache::get('whmcs-admin-all-clients');

        if (is_array($cached) && isset($cached['items'], $cached['total'])) {
            return $cached;
        }

        try {
            $payload = app(WhmcsApi::class)->getAllClients();
        } catch (WhmcsApiException $exception) {
            $this->apiError = $exception->getMessage();

            return ['items' => [], 'total' => 0];
        }

        Cache::put('whmcs-admin-all-clients', $payload, now()->addMinutes(5));

        return $payload;
    }

    public function clientName(array $user): string
    {
        $company = trim((string) data_get($user, 'companyname', ''));
        $name = trim((string) data_get($user, 'firstname', '') . ' ' . (string) data_get($user, 'lastname', ''));

        if ($company !== '') {
            return $company;
        }

        return $name !== '' ? $name : 'Unknown';
    }

    /**
     * Full name shown under the client name when a company is present.
     */
    public function clientSubtitle(array $user): string
    {
        $email = trim((string) data_get($user, 'email', ''));

        if ($email !== '') {
            return $email;
        }

        $name = trim((string) data_get($user, 'firstname', '') . ' ' . (string) data_get($user, 'lastname', ''));

        if ($name !== '' && strcasecmp($name, $this->clientName($user)) !== 0) {
            return $name;
        }

        return '';
    }

    public function formatDate(?string $date): string
    {
        if (! $date || str_starts_with($date, '0000-00-00')) {
            return '-';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    /**
     * Account creation date. GetClients returns "datecreated"; older
     * WHMCS versions label it "signupdate".
     */
    public function signedUpDate(array $user): string
    {
        $date = (string) (data_get($user, 'datecreated') ?: data_get($user, 'signupdate') ?: '');

        return $this->formatDate($date !== '' ? $date : null);
    }

    /**
     * WHMCS client details (phone, country, address) for a client id.
     *
     * GetClients only returns name, email and status, so contact fields
     * are enriched from GetClientsDetails and cached for thirty minutes.
     * Failures are not cached so the next request retries.
     *
     * @return array<string, mixed>
     */
    private function clientDetails(string $clientId): array
    {
        if ($clientId === '') {
            return [];
        }

        if (array_key_exists($clientId, $this->detailsMemo)) {
            return $this->detailsMemo[$clientId];
        }

        $cacheKey = 'whmcs-admin-client-detail:' . $clientId;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $this->detailsMemo[$clientId] = $cached;
        }

        $details = app(WhmcsApi::class)->getClientDetails($clientId);

        if (! is_array($details)) {
            return $this->detailsMemo[$clientId] = [];
        }

        if (isset($details['client']) && is_array($details['client'])) {
            $details = $details['client'];
        }

        Cache::put($cacheKey, $details, now()->addMinutes(30));

        return $this->detailsMemo[$clientId] = $details;
    }

    /**
     * Phone number for a client.
     *
     * WHMCS formats phone numbers with a dot ("+880.1776538827"), so the
     * display value is rebuilt from the raw number and country code.
     */
    public function clientPhone(array $user): string
    {
        $details = $this->clientDetails((string) data_get($user, 'id', ''));

        $phone = trim((string) ($details['phonenumber'] ?? ''));

        if ($phone === '') {
            $formatted = trim((string) ($details['phonenumberformatted'] ?? ''));

            return $formatted !== '' ? str_replace('.', ' ', $formatted) : '-';
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $countryCode = trim((string) ($details['phonecc'] ?? ''));

        return $countryCode !== '' ? '+' . $countryCode . ' ' . $phone : $phone;
    }

    /**
     * Country name for a client.
     */
    public function clientCountry(array $user): string
    {
        $details = $this->clientDetails((string) data_get($user, 'id', ''));

        $country = trim((string) ($details['countryname'] ?? ''));

        if ($country === '') {
            $country = trim((string) ($details['country'] ?? ''));
        }

        return $country !== '' ? $country : '-';
    }

    public function statusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'active' => 'bg-emerald-50 text-emerald-700',
            'inactive' => 'bg-amber-50 text-amber-700',
            'closed' => 'bg-slate-100 text-slate-600',
            'fraud' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * WHMCS admin profile URL for a client.
     */
    public function profileUrl(array $user): string
    {
        $id = (string) data_get($user, 'id', '');

        if ($id === '') {
            return app(WhmcsApi::class)->adminUrl();
        }

        return rtrim(app(WhmcsApi::class)->adminUrl(), '/') . '/clientssummary.php?userid=' . $id;
    }
};
?>

<div class="mx-auto w-full space-y-stack-lg">
    <!-- Header Section -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-xl font-semibold text-on-surface md:text-h1 md:font-h1">
                WHMCS Users
            </h2>

            <p class="text-xs font-body-md text-secondary md:text-body-md">
                Browse every client from your WHMCS billing system.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                    search
                </span>

                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search name, email or client id..."
                    class="w-full rounded-lg border border-outline-variant bg-white py-2.5 pl-10 pr-4 text-label-md font-label-md text-on-surface transition-colors placeholder:text-secondary focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-64" />
            </div>

            <div class="relative">
                <select wire:model.live="status"
                    class="w-full appearance-none rounded-lg border border-outline-variant bg-white px-4 py-2.5 pr-10 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low focus:border-primary focus:ring-2 focus:ring-primary/10 sm:w-44">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="closed">Closed</option>
                    <option value="fraud">Fraud</option>
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
    $results = $this->paginatedUsers();
    @endphp

    <!-- Table Container -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Client
                        </th>

                        <!-- <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            WHMCS ID
                        </th> -->

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Company
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Phone
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Country
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Signed Up
                        </th>

                        <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary">
                            Status
                        </th>

                        <!-- <th class="px-6 py-4 text-label-sm font-label-sm uppercase tracking-wider text-secondary text-right">
                            Action
                        </th> -->
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($results['items'] as $user)
                    @php
                    $userId = (string) data_get($user, 'id');
                    $userStatus = (string) data_get($user, 'status', '');
                    $company = trim((string) data_get($user, 'companyname', ''));
                    @endphp

                    <tr wire:key="whmcs-user-{{ $userId }}" class="transition-colors hover:bg-slate-50/80">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.whmcs.users.view', $userId) }}" wire:navigate class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold uppercase text-primary">
                                    #{{ $userId !== '' ? $userId : '-' }}
                                </span>

                                <div class="flex flex-col">
                                    <span class="text-label-md font-label-md text-on-surface">
                                        {{ $this->clientName($user) }}
                                    </span>

                                    @if (($subtitle = $this->clientSubtitle($user)) !== '')
                                    <span class="text-body-sm font-body-sm text-secondary">
                                        {{ $subtitle }}
                                    </span>
                                    @endif
                                </div>
                            </a>
                        </td>

                        <!-- <td class="px-6 py-4">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700">
                                {{ $userId !== '' ? $userId : '-' }}
                            </span>
                        </td> -->

                        <td class="px-6 py-4 text-body-sm font-body-sm text-on-surface">
                            {{ $company !== '' ? $company : '-' }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->clientPhone($user) }}
                        </td>

                        <td class="px-6 py-4 text-body-sm font-body-sm text-secondary">
                            {{ $this->clientCountry($user) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-body-sm text-secondary">
                            {{ $this->signedUpDate($user) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $this->statusBadgeClass($userStatus) }}">
                                {{ $userStatus !== '' ? ucfirst($userStatus) : 'N/A' }}
                            </span>
                        </td>

                        <!-- <td class="px-6 py-4 text-right">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open"
                                    class="text-slate-400 transition-colors hover:text-primary">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>

                                <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                    class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                    <a href="{{ route('admin.whmcs.users.view', $userId) }}" wire:navigate
                                        @click="open = false"
                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-on-surface transition hover:bg-slate-50">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </td> -->
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-14 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div
                                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <span class="material-symbols-outlined">group</span>
                                </div>

                                <h3 class="text-base font-semibold text-on-surface">
                                    No users found
                                </h3>

                                <p class="mt-1 text-sm text-secondary">
                                    @if ($search !== '' || $status !== 'all')
                                    No users match your current search or filter.
                                    @else
                                    Your WHMCS account has no clients yet.
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
                    <button type="button" wire:click="previousPage" @disabled($results['page'] <=1)
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>

                    <span class="text-body-sm font-body-sm text-on-surface">
                        Page {{ $results['page'] }} of {{ $results['lastPage'] }}
                    </span>

                    <button type="button" wire:click="nextPage" @disabled($results['page']>= $results['lastPage'])
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>