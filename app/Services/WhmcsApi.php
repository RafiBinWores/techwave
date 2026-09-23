<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @phpstan-type WhmcsUser array{id: int|string, email?: ?string, firstname?: ?string, lastname?: ?string}
 * @phpstan-type WhmcsClient array<string, mixed>
 */
class WhmcsApi
{
    /**
     * Perform a WHMCS API request and return the decoded JSON payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws WhmcsApiException
     */
    public function request(string $action, array $payload = []): array
    {
        try {
            $response = Http::asForm()
                ->timeout(20)
                ->retry(2, 300)
                ->post($this->endpoint(), array_merge([
                    'identifier' => (string) config('services.whmcs.identifier'),
                    'secret' => (string) config('services.whmcs.secret'),
                    'action' => $action,
                    'responsetype' => 'json',
                ], $payload));
        } catch (ConnectionException $exception) {
            Log::warning('WHMCS API connection failed.', ['action' => $action, 'error' => $exception->getMessage()]);

            throw new WhmcsApiException('Could not reach the billing system. Please try again later.');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        if (($data['result'] ?? null) !== 'success') {
            $message = (string) ($data['message'] ?? 'Unexpected response from the billing system.');

            Log::warning('WHMCS API error response.', ['action' => $action, 'message' => $message]);

            throw new WhmcsApiException($message);
        }

        return $data;
    }

    /**
     * Perform a WHMCS API request using XML and return the decoded payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws WhmcsApiException
     */
    public function requestXml(string $action, array $payload = []): array
    {
        try {
            $response = Http::asForm()
                ->timeout(20)
                ->retry(2, 300)
                ->post($this->endpoint(), array_merge([
                    'identifier' => (string) config('services.whmcs.identifier'),
                    'secret' => (string) config('services.whmcs.secret'),
                    'action' => $action,
                    'responsetype' => 'xml',
                ], $payload));
        } catch (ConnectionException $exception) {
            Log::warning('WHMCS API connection failed.', ['action' => $action, 'error' => $exception->getMessage()]);

            throw new WhmcsApiException('Could not reach the billing system. Please try again later.');
        }

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $response->body());
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', (string) $clean) ?? $clean;

        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($clean);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            Log::warning('WHMCS API XML parse failed.', [
                'action' => $action,
                'errors' => array_map(fn ($e) => trim($e->message), $errors),
            ]);

            throw new WhmcsApiException('Could not parse the billing system response.');
        }

        if ((string) $xml->result !== 'success') {
            $message = (string) ($xml->message ?? 'Unexpected response from the billing system.');

            Log::warning('WHMCS API XML error response.', ['action' => $action, 'message' => $message]);

            throw new WhmcsApiException($message);
        }

        return json_decode(json_encode($xml), true) ?? [];
    }

    /**
     * Find a WHMCS user by email address using the GetUsers action.
     *
     * @return WhmcsUser|null
     *
     * @throws WhmcsApiException
     */
    public function findUserByEmail(string $email): ?array
    {
        $data = $this->request('GetUsers', ['search' => $email]);

        /** @var array<int, array<string, mixed>> $users */
        $users = data_get($data, 'users.users', data_get($data, 'users', []));

        foreach ($users as $user) {
            if (strcasecmp((string) ($user['email'] ?? ''), $email) === 0) {
                /** @var WhmcsUser */
                return $user;
            }
        }

        return null;
    }

    /**
     * Fetch full client details for a client id or email.
     *
     * @return WhmcsClient|null
     *
     * @throws WhmcsApiException
     */
    public function getClientDetails(int|string|null $clientId, ?string $email = null): ?array
    {
        if ($clientId === null && $email === null) {
            return null;
        }

        $payload = $clientId !== null ? ['clientid' => $clientId] : ['email' => $email];

        try {
            return $this->request('GetClientsDetails', $payload + ['stats' => true]);
        } catch (WhmcsApiException) {
            return null;
        }
    }

    /**
     * Fetch products/services owned by a client.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws WhmcsApiException
     */
    public function getClientProducts(int|string $clientId): array
    {
        $data = $this->request('GetClientsProducts', [
            'clientid' => $clientId,
        ]);

        $products = data_get($data, 'products.product', []);

        if (is_array($products) && ! array_is_list($products)) {
            $products = [$products];
        }

        /** @var array<int, array<string, mixed>> */
        return $products;
    }

    /**
     * Fetch products/services owned by a client using XML (handles malformed UTF-8).
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws WhmcsApiException
     */
    public function getClientProductsXml(int|string $clientId): array
    {
        $data = $this->requestXml('GetClientsProducts', [
            'clientid' => $clientId,
        ]);

        $products = data_get($data, 'products.product', []);

        if (is_array($products) && ! array_is_list($products)) {
            $products = [$products];
        }

        /** @var array<int, array<string, mixed>> */
        return $products;
    }

    /**
     * Fetch invoices for a client.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws WhmcsApiException
     */
    public function getInvoices(int|string $userId): array
    {
        $data = $this->request('GetInvoices', ['userid' => $userId]);

        /** @var array<int, array<string, mixed>> */
        return data_get($data, 'invoices.invoice', []);
    }

    /**
     * Fetch domains owned by a client.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws WhmcsApiException
     */
    public function getClientDomains(int|string $clientId): array
    {
        $data = $this->request('GetClientsDomains', [
            'clientid' => $clientId,
        ]);

        $domains = data_get($data, 'domains.domain', []);

        if (is_array($domains) && ! array_is_list($domains)) {
            $domains = [$domains];
        }

        /** @var array<int, array<string, mixed>> */
        return $domains;
    }

    /**
     * Fetch global business performance statistics.
     *
     * @return array<string, mixed>
     *
     * @throws WhmcsApiException
     */
    public function getStats(?int $timelineDays = null): array
    {
        $payload = $timelineDays !== null ? ['timeline_days' => $timelineDays] : [];

        return $this->request('GetStats', $payload);
    }

    /**
     * Fetch the most recently created clients and the total client count.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     *
     * @throws WhmcsApiException
     */
    public function getRecentClients(int $limit = 5): array
    {
        $data = $this->request('GetClients', ['limitnum' => $limit]);

        $clients = data_get($data, 'clients.client', []);

        if (is_array($clients) && ! array_is_list($clients)) {
            $clients = [$clients];
        }

        /** @var array<int, array<string, mixed>> $clients */
        return [
            'items' => $clients,
            'total' => (int) data_get($data, 'totalresults', 0),
        ];
    }

    /**
     * Fetch all clients as an id => profile map.
     *
     * Invoice rows only carry the client id and name; emails come from
     * this read-only GetClients directory so invoice lists can enrich
     * client cells without one API call per row.
     *
     * @return array<string, array{firstname: string, lastname: string, email: string, companyname: string}>
     *
     * @throws WhmcsApiException
     */
    public function getClientDirectory(): array
    {
        $map = [];
        $startNumber = 0;
        $limitNum = 100;
        $maxPages = 20;
        $page = 0;
        $totalResults = 0;
        $clients = [];

        do {
            $data = $this->request('GetClients', [
                'limitstart' => $startNumber,
                'limitnum' => $limitNum,
            ]);

            $clients = data_get($data, 'clients.client', []);

            if (is_array($clients) && ! array_is_list($clients)) {
                $clients = [$clients];
            }

            $totalResults = (int) data_get($data, 'totalresults', 0);

            foreach ($clients as $client) {
                $id = (string) data_get($client, 'id', '');

                if ($id === '') {
                    continue;
                }

                $map[$id] = [
                    'firstname' => (string) data_get($client, 'firstname', ''),
                    'lastname' => (string) data_get($client, 'lastname', ''),
                    'email' => (string) data_get($client, 'email', ''),
                    'companyname' => (string) data_get($client, 'companyname', ''),
                ];
            }

            $startNumber += $limitNum;
            $page++;
        } while ($startNumber < $totalResults && $page < $maxPages && $clients !== []);

        return $map;
    }

    /**
     * Fetch the most recently created invoices and the total invoice count.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     *
     * @throws WhmcsApiException
     */
    public function getRecentInvoices(int $limit = 5): array
    {
        $data = $this->request('GetInvoices', [
            'limitnum' => $limit,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        $invoices = data_get($data, 'invoices.invoice', []);

        if (is_array($invoices) && ! array_is_list($invoices)) {
            $invoices = [$invoices];
        }

        /** @var array<int, array<string, mixed>> $invoices */
        return [
            'items' => $invoices,
            'total' => (int) data_get($data, 'totalresults', 0),
        ];
    }

    /**
     * Fetch every invoice from WHMCS, paging through the API.
     *
     * Uses the read-only GetInvoices action ordered by date descending,
     * which never triggers WHMCS login or anti-brute-force protections.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     *
     * @throws WhmcsApiException
     */
    public function getAllInvoices(): array
    {
        $items = [];
        $startNumber = 0;
        $limitNum = 100;
        $maxPages = 50;
        $page = 0;
        $totalResults = 0;
        $invoices = [];

        do {
            $data = $this->request('GetInvoices', [
                'limitstart' => $startNumber,
                'limitnum' => $limitNum,
                'orderby' => 'date',
                'order' => 'desc',
            ]);

            /** @var array<int, array<string, mixed>> $invoices */
            $invoices = data_get($data, 'invoices.invoice', []);

            if (is_array($invoices) && ! array_is_list($invoices)) {
                $invoices = [$invoices];
            }

            $totalResults = (int) data_get($data, 'totalresults', 0);

            foreach ($invoices as $invoice) {
                $items[] = $invoice;
            }

            $startNumber += $limitNum;
            $page++;
        } while ($startNumber < $totalResults && $page < $maxPages && $invoices !== []);

        return [
            'items' => $items,
            'total' => $totalResults,
        ];
    }

    /**
     * Fetch activated payment methods as a module => display name map.
     *
     * Invoice rows only carry the gateway module key (e.g. "mailin"); the
     * friendly name configured in WHMCS (e.g. "bKash Payment") comes from
     * this read-only GetPaymentMethods action.
     *
     * @return array<string, string>
     *
     * @throws WhmcsApiException
     */
    public function getPaymentMethods(): array
    {
        $data = $this->request('GetPaymentMethods');

        $methods = data_get($data, 'paymentmethods.paymentmethod', []);

        if (is_array($methods) && ! array_is_list($methods)) {
            $methods = [$methods];
        }

        $map = [];

        foreach ($methods as $method) {
            if (! is_array($method)) {
                continue;
            }

            $module = trim((string) data_get($method, 'module', ''));

            if ($module === '') {
                continue;
            }

            $map[$module] = trim((string) data_get($method, 'displayname', ''));
        }

        return $map;
    }

    /**
     * Aggregate paid invoice revenue and invoice counts per day and per month.
     *
     * Uses the read-only GetInvoices action with the "Paid" status filter, which
     * never triggers WHMCS login or anti-brute-force protections. Invoices are
     * fetched in pages of 100 and bucketed by their issue date so the result can
     * power the dashboard billing trend chart.
     *
     * @return array{
     *     daily: array{labels: array<int, string>, revenue: array<int, float>, invoices: array<int, int>},
     *     monthly: array{labels: array<int, string>, revenue: array<int, float>, invoices: array<int, int>}
     * }
     *
     * @throws WhmcsApiException
     */
    public function getBillingTrend(int $months = 12, int $days = 30): array
    {
        $dailyBuckets = [];
        $monthlyBuckets = [];
        $startNumber = 0;
        $limitNum = 100;
        $maxPages = 30;
        $page = 0;
        $totalResults = 0;

        do {
            $data = $this->request('GetInvoices', [
                'limitstart' => $startNumber,
                'limitnum' => $limitNum,
                'orderby' => 'date',
                'order' => 'desc',
                'status' => 'Paid',
            ]);

            /** @var array<int, array<string, mixed>> $invoices */
            $invoices = data_get($data, 'invoices.invoice', []);

            if (is_array($invoices) && ! array_is_list($invoices)) {
                $invoices = [$invoices];
            }

            $totalResults = (int) data_get($data, 'totalresults', 0);

            foreach ($invoices as $invoice) {
                $date = (string) data_get($invoice, 'date', '');

                if ($date === '' || str_starts_with($date, '0000-00-00')) {
                    continue;
                }

                $dayKey = substr($date, 0, 10);
                $monthKey = substr($date, 0, 7);

                if ($dayKey === '' || $monthKey === '') {
                    continue;
                }

                $amount = $this->normalizeAmount((string) data_get($invoice, 'total', '0'));

                $daily = $dailyBuckets[$dayKey] ?? ['revenue' => 0.0, 'count' => 0];
                $daily['revenue'] += $amount;
                $daily['count']++;
                $dailyBuckets[$dayKey] = $daily;

                $monthly = $monthlyBuckets[$monthKey] ?? ['revenue' => 0.0, 'count' => 0];
                $monthly['revenue'] += $amount;
                $monthly['count']++;
                $monthlyBuckets[$monthKey] = $monthly;
            }

            $startNumber += $limitNum;
            $page++;
        } while ($startNumber < $totalResults && $page < $maxPages);

        $dailyLabels = [];
        $dailyRevenue = [];
        $dailyInvoiceCounts = [];

        $today = Carbon::today();

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $bucket = $dailyBuckets[$day->toDateString()] ?? ['revenue' => 0.0, 'count' => 0];

            $dailyLabels[] = $day->format('M d');
            $dailyRevenue[] = round($bucket['revenue'], 2);
            $dailyInvoiceCounts[] = (int) $bucket['count'];
        }

        $monthlyLabels = [];
        $monthlyRevenue = [];
        $monthlyInvoiceCounts = [];

        $cursor = Carbon::today()->startOfMonth();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $cursor->copy()->subMonths($i);
            $bucket = $monthlyBuckets[$month->format('Y-m')] ?? ['revenue' => 0.0, 'count' => 0];

            $monthlyLabels[] = $month->format('M y');
            $monthlyRevenue[] = round($bucket['revenue'], 2);
            $monthlyInvoiceCounts[] = (int) $bucket['count'];
        }

        return [
            'daily' => [
                'labels' => $dailyLabels,
                'revenue' => $dailyRevenue,
                'invoices' => $dailyInvoiceCounts,
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'revenue' => $monthlyRevenue,
                'invoices' => $monthlyInvoiceCounts,
            ],
        ];
    }

    /**
     * Normalize a WHMCS money string into a float.
     */
    private function normalizeAmount(string $amount): float
    {
        $amount = trim($amount);

        if (preg_match('/([0-9][0-9.,]*)/', $amount, $matches) === 1) {
            $amount = $matches[1];
        }

        return (float) str_replace(',', '', $amount);
    }

    /**
     * Get the full WHMCS admin area URL.
     */
    public function adminUrl(): string
    {
        $adminPath = (string) config('services.whmcs.admin_path', 'admin');

        return rtrim((string) config('services.whmcs.url'), '/').'/'.ltrim($adminPath, '/');
    }

    /**
     * Get a Single Sign-On token for a WHMCS client.
     *
     * @throws WhmcsApiException
     */
    public function getSsoToken(int|string $clientId): string
    {
        $data = $this->request('CreateSsoToken', [
            'client_id' => (int) $clientId,
        ]);

        return (string) data_get($data, 'token', '');
    }

    /**
     * Build the full SSO redirect URL for a WHMCS client.
     *
     * @param  string|null  $destination  A WHMCS SSO destination scope (e.g. "clientarea:domain_details").
     * @param  int|string|null  $domainId  The domain id when using the "clientarea:domain_details" destination.
     *
     * @throws WhmcsApiException
     */
    public function getSsoUrl(int|string $clientId, ?string $destination = null, int|string|null $domainId = null): string
    {
        $payload = ['client_id' => (int) $clientId];

        if ($destination !== null) {
            $payload['destination'] = $destination;
        }

        if ($domainId !== null) {
            $payload['domain_id'] = (int) $domainId;
        }

        $data = $this->request('CreateSsoToken', $payload);

        $redirectUrl = (string) data_get($data, 'redirect_url', '');

        if ($redirectUrl !== '') {
            return $redirectUrl;
        }

        $token = (string) data_get($data, 'token', '');

        if ($token === '') {
            throw new WhmcsApiException('Could not obtain a single sign-on token.');
        }

        $whmcsUrl = rtrim((string) config('services.whmcs.url'), '/');

        return $whmcsUrl.'/index.php?rp=/clientarea/sso&token='.$token;
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.whmcs.url'), '/').'/includes/api.php';
    }
}
