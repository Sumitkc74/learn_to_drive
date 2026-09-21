<?php
return [
    'chat_model'=>env('GEMINI_CHAT_MODEL',env('GEMINI_TRANSLATION_MODEL','gemini-3.5-flash')),
    'daily_chat_limit'=>(int)env('PREMIUM_CHAT_DAILY_LIMIT',30),
];
