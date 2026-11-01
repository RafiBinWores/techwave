<?php

namespace App\Support\Seo;

class Transport
{
    public function get(array $target, int $maxBytes, float $deadline, array $headers = []): array
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0 || ! extension_loaded('curl')) {
            throw new SeoException('The website could not be checked within the time limit.');
        }
        $requestHeaders = ['Accept' => 'text/html, application/xhtml+xml, text/plain;q=0.8, application/xml;q=0.8'];
        foreach ($headers as $name => $value) {
            if (! in_array($name, ['Accept', 'Authorization'], true) || ! is_string($value) || preg_match('/[\\r\\n\\x00]/', $value)) {
                throw new SeoException('Invalid provider request configuration.');
            }
            $requestHeaders[$name] = $value;
        }
        $body = '';
        $headers = [];
        $headerBytes = 0;
        $oversized = false;
        $ip = $target['ips'][0];
        $pinnedIp = str_contains($ip, ':') ? '['.$ip.']' : $ip;
        $curl = curl_init($target['url']);
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => filter_var($target['host'], FILTER_VALIDATE_IP) ? [] : [$target['host'].':'.$target['port'].':'.$pinnedIp],
            CURLOPT_CONNECTTIMEOUT_MS => max(1, (int) (min(config('seo.connect_timeout', 5), $remaining) * 1000)),
            CURLOPT_TIMEOUT_MS => max(1, (int) ($remaining * 1000)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'TechWaveSEOChecker/1.0',
            CURLOPT_HTTPHEADER => array_map(fn ($name, $value) => $name.': '.$value, array_keys($requestHeaders), $requestHeaders),
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$headers, &$headerBytes) {
                $headerBytes += strlen($line);
                if ($headerBytes > 65536) {
                    return 0;
                }
                if (str_starts_with($line, 'HTTP/')) {
                    $headers = [];
                } elseif (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$body, &$oversized, $maxBytes) {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $oversized = true;

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($oversized) {
            throw new SeoException('This page is too large to analyze. Try a smaller page.');
        }
        if ($ok === false || $status === 0) {
            throw new SeoException('The website could not be reached securely within the time limit.');
        }

        return compact('status', 'headers', 'body');
    }
}
