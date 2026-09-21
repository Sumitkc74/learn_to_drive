<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PublicWebsiteRequest
{
    public function host(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (isset($parts['query'])) {
            parse_str($parts['query'], $parameters);
            $keys = [];
            $collect = function (array $values) use (&$collect, &$keys): void {
                foreach ($values as $key => $value) {
                    $keys[] = (string) $key;
                    if (is_array($value)) $collect($value);
                }
            };
            $collect($parameters);
            foreach ($keys as $key) {
                if (preg_match('/token|secret|password|credential|signature|api.?key|authorization|session|x-amz|x-goog/i', $key)) {
                    throw new RuntimeException('Use a public URL without access tokens, signatures or credentials.');
                }
            }
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/', $host)
            || preg_match('/\.(localhost|local|internal|test|invalid|example)$/', $host)) {
            throw new RuntimeException('Enter a public HTTPS website URL without credentials or a custom port.');
        }
        return $host;
    }

    public function addresses(string $host): array
    {
        return gethostbynamel($host) ?: [];
    }

    public function get(string $url)
    {
        $host = $this->host($url);
        $addresses = $this->addresses($host);
        if (!$addresses || !extension_loaded('curl')) throw new RuntimeException('Website could not be resolved securely.');
        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || str_starts_with($address, '169.254.') || $this->sharedRange($address)) {
                throw new RuntimeException('Private and reserved network addresses cannot be scanned.');
            }
        }
        // Pin the verified public address; disable proxies and redirects so DNS
        // rebinding or redirect targets cannot bypass validation. TLS stays on.
        return Http::connectTimeout(8)->timeout(25)->withHeaders(['User-Agent' => 'LearnToDrive-ReviewImporter/1.0'])
            ->withOptions(['allow_redirects' => false, 'stream' => true, 'proxy' => '',
                'verify' => config('official-content.ca_bundle') ?: true,
                'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$addresses[0]], CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_PROXY => '']])->get($url);
    }

    private function sharedRange(string $ip): bool
    {
        $parts = array_map('intval', explode('.', $ip));
        return $parts[0] === 0 || $parts[0] >= 224
            || ($parts[0] === 100 && $parts[1] >= 64 && $parts[1] <= 127)
            || ($parts[0] === 192 && $parts[1] === 0)
            || ($parts[0] === 198 && in_array($parts[1], [18, 19], true));
    }
}
