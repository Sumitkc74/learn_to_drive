<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'dotm' => [
        'notices_url' => env('DOTM_NOTICES_URL', 'https://dotm.gov.np/'),
        // Category listing pages checked alongside the homepage. The
        // homepage only shows the latest notices, so once a link rotates
        // off it the importer would never see it again; each listing
        // keeps its own older items. A page that fails to fetch is
        // recorded as a failed source run (see DotmNoticeImporter).
        // /category/printed-license is deliberately NOT listed: it 404s.
        'listing_urls' => array_values(array_filter(array_map('trim', explode(',', env('DOTM_LISTING_URLS', implode(',', [
            'https://dotm.gov.np/category/details-of-printed-licenses/',
            'https://dotm.gov.np/category/details-of-printed-essential-driver-s-licenses-/',
            'https://dotm.gov.np/category/highlights-content/',
            'https://dotm.gov.np/category/fare-rate/',
        ])))))),
        // How many pages (?page=1..N) of each listing to check per run.
        // The site itself paginates these listings to 3 pages.
        'listing_max_pages' => (int) env('DOTM_LISTING_MAX_PAGES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
