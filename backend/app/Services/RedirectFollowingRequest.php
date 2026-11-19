<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * GET a URL while following redirects manually, so every hop is checked
 * against the caller's allowlist before it is fetched. Guzzle's built-in
 * redirect follower would follow a redirect to any host without
 * re-checking, which would defeat the official-source allowlists and the
 * SSRF guards. The custom-website path (PublicWebsiteRequest) keeps its
 * own pinned-address behaviour and does not use this helper.
 */
class RedirectFollowingRequest
{
    public const MAX_REDIRECTS = 5;

    /**
     * @param callable(string):bool $allowed allowlist check for each URL, including the first
     * @param callable(string):Response $fetch one non-following GET (allow_redirects => false)
     */
    public function get(string $url, callable $allowed, callable $fetch): Response
    {
        $current = $url;
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if (! $allowed($current)) {
                throw new RuntimeException('The redirect target is not on the official allowlist.');
            }
            $response = $fetch($current);
            $status = $response->status();
            if (! in_array($status, [301, 302, 303, 307, 308], true)) {
                return $response;
            }
            $location = trim((string) $response->header('Location'));
            try {
                $response->toPsrResponse()->getBody()->close();
            } catch (\Throwable) {
                // A redirect body that cannot be closed must not block the redirect itself.
            }
            if ($location === '') {
                return $response;
            }
            if ($hop === self::MAX_REDIRECTS) {
                throw new RuntimeException('Too many redirects (limit: '.self::MAX_REDIRECTS.').');
            }
            $current = (string) UriResolver::resolve(new Uri($current), new Uri($location))->withFragment('');
        }
        throw new RuntimeException('Too many redirects (limit: '.self::MAX_REDIRECTS.').');
    }
}
