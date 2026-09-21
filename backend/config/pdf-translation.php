<?php
return [
    'driver' => env('PDF_TRANSLATION_DRIVER', 'google'),
    'google_key' => env('GOOGLE_TRANSLATION_API_KEY'),
    'gemini_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_TRANSLATION_MODEL', 'gemini-3.5-flash'),
    'local_url' => env('PDF_TRANSLATION_LOCAL_URL', 'http://127.0.0.1:11434'),
    'local_model' => env('PDF_TRANSLATION_LOCAL_MODEL'),
    'browser' => env('PDF_TRANSLATION_BROWSER', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'),
];
