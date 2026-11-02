<?php
return [
    'enabled'=>(bool)env('PREMIUM_PAYMENTS_ENABLED',false),
    'live'=>(bool)env('PREMIUM_PAYMENTS_LIVE',false),
    'amount_paisa'=>(int)env('PREMIUM_PRICE_PAISA',0),
    'days'=>(int)env('PREMIUM_ACCESS_DAYS',0),
    // Lifetime Premium: a one-time payment that never expires. When true,
    // access_days on the payment record is stored as 0 (meaning lifetime)
    // and premium_until is set 100 years out — there is no separate
    // lifetime column, and hasPremium() already only checks isFuture().
    'lifetime'=>(bool)env('PREMIUM_LIFETIME',false),
    'khalti_key'=>env('KHALTI_SECRET_KEY'),
    'esewa_code'=>env('ESEWA_PRODUCT_CODE'),
    'esewa_secret'=>env('ESEWA_SECRET_KEY'),
];
