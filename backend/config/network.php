<?php

$proxies = array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '')))));

return [
    // List only your reverse proxy IP addresses/CIDRs, never arbitrary clients.
    // When the list is empty in production, trust all proxies: hosts like
    // Render terminate TLS in front of the app and forward
    // X-Forwarded-Proto, and their proxy IPs are dynamic, so an explicit
    // list cannot be written down. Without this the app sees Render's
    // forwarded requests as plain http and request-derived URLs (notably
    // paginator links) render as http://. An explicitly set TRUSTED_PROXIES
    // value always wins. Outside production an empty list means no proxies
    // are trusted, so forwarded headers from untrusted clients are ignored.
    // (env('APP_ENV') rather than app()->environment(): the config file is
    // evaluated during boot, before the 'env' binding is available.)
    'trusted_proxies' => $proxies !== [] ? $proxies : (env('APP_ENV') === 'production' ? '*' : []),
];
