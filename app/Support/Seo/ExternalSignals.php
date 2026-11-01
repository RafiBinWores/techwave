<?php

namespace App\Support\Seo;

use GuzzleHttp\Psr7\Uri;

final class ExternalSignals
{
    public function __construct(private SafeFetcher $fetcher, private PublicUrl $urls) {}

    public function inspect(string $url, float $deadline): array
    {
        $result = ['backlinks' => ['status' => 'not_connected', 'metrics' => null], 'screenshot' => ['status' => 'not_connected', 'image_url' => null]];
        foreach (['backlinks', 'screenshot'] as $type) {
            $endpoint = config('seo.integrations.'.$type.'.endpoint');
            if (! is_string($endpoint) || trim($endpoint) === '') {
                continue;
            }
            $result[$type]['status'] = 'unavailable';
            try {
                if (strtolower(parse_url($endpoint, PHP_URL_SCHEME) ?? '') !== 'https') {
                    continue;
                }
                $probeDeadline = min($deadline, microtime(true) + (float) config('seo.integration_seconds', 4));
                $uri = new Uri($endpoint);
                $query = [];
                parse_str($uri->getQuery(), $query);
                $query['url'] = $url;
                $uri = $uri->withQuery(http_build_query($query, '', '&', PHP_QUERY_RFC3986));
                $headers = ['Accept' => 'application/json'];
                $token = config('seo.integrations.'.$type.'.token');
                if (is_string($token) && $token !== '') {
                    $headers['Authorization'] = 'Bearer '.$token;
                }
                $response = $this->fetcher->fetch((string) $uri, (int) config('seo.auxiliary_bytes', 262144), $probeDeadline, false, null, $headers);
                if ($response->status !== 200) {
                    continue;
                }
                $data = json_decode($response->body, true, 32, JSON_THROW_ON_ERROR);
                if (! is_array($data)) {
                    continue;
                }
                if ($type === 'backlinks') {
                    $metrics = [];
                    foreach (['backlinks', 'referring_domains', 'referring_ips'] as $key) {
                        if (! isset($data[$key]) || ! is_int($data[$key]) || $data[$key] < 0) {
                            continue 2;
                        }
                        $metrics[$key] = $data[$key];
                    }
                    $result[$type] = ['status' => 'connected', 'metrics' => $metrics];
                } else {
                    $image = $data['image_url'] ?? null;
                    if (! is_string($image) || strtolower(parse_url($image, PHP_URL_SCHEME) ?? '') !== 'https') {
                        continue;
                    }
                    $target = $this->urls->resolve($image, $probeDeadline);
                    $result[$type] = ['status' => 'connected', 'image_url' => $target['url']];
                }
            } catch (\Throwable) {
                // Provider bodies, endpoints, tokens and errors never enter public report data.
            }
        }

        return $result;
    }
}
