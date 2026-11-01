<?php

namespace App\Support\Seo;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PublicUrl
{
    public function resolve(string $url, ?float $deadline = null): array
    {
        $deadline ??= microtime(true) + (float) config('seo.audit_seconds', 20);
        if (microtime(true) >= $deadline) {
            throw new SeoException('This check exceeded the time limit.');
        }
        $url = trim($url);
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new SeoException('Enter a valid public HTTP or HTTPS URL.');
        }
        $parts = parse_url($url);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || empty($parts['host'])) {
            throw new SeoException('Enter a public HTTP or HTTPS URL without credentials.');
        }
        $host = strtolower(trim($parts['host'], '[]'));
        $port = $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80);
        if (! in_array($port, [80, 443], true)) {
            throw new SeoException('Only standard web ports 80 and 443 are supported.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            if (! str_contains($host, '.') || ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
                || preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/i', $host)
                || preg_match('/^(0x[0-9a-f]+|[0-9.]+)$/i', $host)) {
                throw new SeoException('Use a publicly reachable website address.');
            }
            $ips = $this->addresses($host, $deadline);
        }
        if ($ips === []) {
            throw new SeoException('This website could not be resolved. Check the address and try again.');
        }
        foreach ($ips as $ip) {
            if (! $this->isPublic($ip)) {
                throw new SeoException('This address is not a supported public website.');
            }
        }
        $parts['scheme'] = strtolower($parts['scheme']);
        $parts['host'] = str_contains($host, ':') ? '['.$host.']' : $host;
        unset($parts['fragment']);
        $normalized = (string) Uri::fromParts($parts);

        return ['url' => $normalized, 'host' => $host, 'port' => $port, 'ips' => array_values(array_unique($ips))];
    }

    protected function addresses(string $host, float $deadline): array
    {
        $process = $this->dnsProcess($host);
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new SeoException('This check exceeded the time limit.');
        }
        $process->setTimeout($remaining);
        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new SeoException('The website DNS lookup exceeded the check time limit.');
        } catch (\Throwable) {
            throw new SeoException('The website DNS lookup is unavailable. Please try again later.');
        }
        if (! $process->isSuccessful() || microtime(true) >= $deadline) {
            throw new SeoException('The website DNS lookup could not complete within the check budget.');
        }
        $addresses = json_decode($process->getOutput(), true);

        return is_array($addresses) ? array_values(array_filter($addresses, 'is_string')) : [];
    }

    protected function dnsProcess(string $host): Process
    {
        $php = (new PhpExecutableFinder)->find(false);
        if (! $php) {
            throw new SeoException('The DNS lookup runtime is unavailable.');
        }
        $code = '$records = @dns_get_record($argv[1], DNS_A | DNS_AAAA); echo json_encode(array_values(array_filter(array_map(fn ($r) => $r["ip"] ?? $r["ipv6"] ?? null, $records ?: []))));';

        return new Process([$php, '-r', $code, '--', $host]);
    }

    private function isPublic(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // IPv6 is restricted to global unicast; exclude transition mechanisms and special registries.
        if (str_contains($ip, ':')) {
            return IpUtils::checkIp($ip, '2000::/3')
                && ! IpUtils::checkIp($ip, ['2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20']);
        }

        return ! IpUtils::checkIp($ip, ['0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4']);
    }
}
