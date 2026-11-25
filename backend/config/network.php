<?php

$proxies = array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '')))));

// A '*' in the list means "trust all proxies", and it MUST reach Laravel as
// the string '*', not the array ['*']: the framework only treats the string
// as a wildcard (it trusts the calling IP); an array is treated as a list
// of literal IPs, and '*' matches no real IP, so nothing is trusted and
// forwarded headers are ignored. If the dashboard holds TRUSTED_PROXIES='*'
// (as suggested on Nov 23), the config used to return ['*'] here, and both
// earlier fixes were dead on arrival for exactly that reason. Normalise
// here so any '*' (alone or among explicit IPs) becomes the string wildcard.
if ($proxies === [] || in_array('*', $proxies, true) || in_array('**', $proxies, true)) {
    return [
        'trusted_proxies' => '*',
    ];
}

return [
    // List only your reverse proxy IP addresses/CIDRs, never arbitrary clients.
    // When the list is empty, trust all proxies: hosts like Render terminate
    // TLS in front of the app and forward X-Forwarded-Proto, and their proxy
    // IPs are dynamic, so an explicit list cannot be written down. Without
    // this the app sees Render's forwarded requests as plain http and
    // request-derived URLs (notably paginator links) render as http://.
    // An explicitly set TRUSTED_PROXIES value always wins. Deliberately NOT
    // gated on APP_ENV: the deployed image bakes APP_ENV=local from
    // .env.example (the Dockerfile copies it) and the Render dashboard does
    // not carry render.yaml's values, so a production-only default never
    // triggers live. The app only runs behind Render's proxy or directly in
    // local dev, where no forwarded headers arrive.
    'trusted_proxies' => $proxies,
];
