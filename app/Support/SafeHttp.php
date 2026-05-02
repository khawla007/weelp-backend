<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SafeHttp
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const TIMEOUT = 10;

    private const MAX_REDIRECTS = 3;

    public static function fetch(string $url): Response
    {
        $current = $url;

        for ($hop = 0; $hop < self::MAX_REDIRECTS; $hop++) {
            self::assertSafe($current);

            $response = Http::withoutRedirecting()
                ->timeout(self::TIMEOUT)
                ->get($current);

            $status = $response->status();
            if ($status >= 300 && $status < 400) {
                $location = $response->header('Location');
                if (! $location) {
                    self::assertSize($response);

                    return $response;
                }
                $current = self::resolveRedirect($current, $location);

                continue;
            }

            self::assertSize($response);

            return $response;
        }

        throw new \DomainException('ssrf_blocked: too many redirects');
    }

    public static function isBlockedIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ranges = [
                '127.0.0.0/8',
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '169.254.0.0/16',
                '0.0.0.0/8',
                '100.64.0.0/10',
                '198.18.0.0/15',
                '224.0.0.0/4',
                '240.0.0.0/4',
            ];
            foreach ($ranges as $cidr) {
                if (self::ipv4InCidr($ip, $cidr)) {
                    return true;
                }
            }

            return false;
        }

        $normalized = strtolower((string) inet_ntop(inet_pton($ip)));
        if (in_array($normalized, ['::1', '::'], true)) {
            return true;
        }
        // IPv4-mapped (::ffff:a.b.c.d) — recurse on the embedded v4.
        if (self::ipv6InCidr($ip, '::ffff:0:0/96')) {
            $embedded = substr($ip, strrpos($ip, ':') + 1);
            if (filter_var($embedded, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return self::isBlockedIp($embedded);
            }

            return true;
        }
        $v6Ranges = ['fc00::/7', 'fe80::/10', '64:ff9b::/96', '2002::/16'];
        foreach ($v6Ranges as $cidr) {
            if (self::ipv6InCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new \DomainException('ssrf_blocked: invalid url');
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \DomainException('ssrf_blocked: scheme not allowed');
        }

        $host = trim($parts['host'], '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (self::isBlockedIp($host)) {
                throw new \DomainException('ssrf_blocked: private or loopback ip');
            }

            return;
        }

        $ips = @gethostbynamel($host);
        if (! $ips) {
            throw new \DomainException('ssrf_blocked: dns lookup failed');
        }
        foreach ($ips as $resolved) {
            if (self::isBlockedIp($resolved)) {
                throw new \DomainException('ssrf_blocked: resolves to private or loopback');
            }
        }
    }

    private static function assertSize(Response $response): void
    {
        $contentLength = $response->header('Content-Length');
        if ($contentLength !== '' && $contentLength !== null && (int) $contentLength > self::MAX_BYTES) {
            throw new \DomainException('ssrf_blocked: response too large');
        }
        if (strlen($response->body()) > self::MAX_BYTES) {
            throw new \DomainException('ssrf_blocked: response too large');
        }
    }

    private static function ipv4InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private static function ipv6InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $remBits = $bits % 8;
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }
        if ($remBits === 0) {
            return true;
        }
        $mask = 0xFF << (8 - $remBits) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }

    private static function resolveRedirect(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }
        $b = parse_url($base);
        if (! isset($b['scheme'], $b['host'])) {
            throw new \DomainException('ssrf_blocked: invalid base for redirect');
        }
        $port = isset($b['port']) ? ':'.$b['port'] : '';
        $path = str_starts_with($location, '/') ? $location : '/'.$location;

        return $b['scheme'].'://'.$b['host'].$port.$path;
    }
}
