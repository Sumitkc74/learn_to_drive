<?php

$proxies = array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '')))));

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
    'trusted_proxies' => $proxies !== [] ? $proxies : '*',
];
