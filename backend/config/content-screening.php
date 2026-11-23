<?php
return [
    'enabled' => env('CONTENT_AI_SCREENING', true),
    'daily_limit' => (int) env('CONTENT_AI_DAILY_LIMIT', 100),
    // Screening verdicts are a classification-style task, so they run on
    // Flash-Lite: 500 free requests/day versus 20/day on Flash. Chat and
    // PDF translation keep their own model (pdf-translation.gemini_model).
    'gemini_model' => env('CONTENT_SCREENING_GEMINI_MODEL', 'gemini-3.5-flash-lite'),
];
