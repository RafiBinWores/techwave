<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
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

    private function endpoint(): string
    {
        return rtrim((string) config('services.whmcs.url'), '/').'/includes/api.php';
    }
}
