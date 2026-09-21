<?php
return [
    // List only your reverse proxy IP addresses/CIDRs, never arbitrary clients.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
];
