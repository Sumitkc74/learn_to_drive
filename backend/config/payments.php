<?php
return [
    'enabled'=>(bool)env('PREMIUM_PAYMENTS_ENABLED',false),
    'live'=>(bool)env('PREMIUM_PAYMENTS_LIVE',false),
    'amount_paisa'=>(int)env('PREMIUM_PRICE_PAISA',0),
    'days'=>(int)env('PREMIUM_ACCESS_DAYS',0),
    'khalti_key'=>env('KHALTI_SECRET_KEY'),
    'esewa_code'=>env('ESEWA_PRODUCT_CODE'),
    'esewa_secret'=>env('ESEWA_SECRET_KEY'),
];
