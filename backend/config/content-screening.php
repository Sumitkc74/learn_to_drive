<?php
return ['enabled' => env('CONTENT_AI_SCREENING', true), 'daily_limit' => (int) env('CONTENT_AI_DAILY_LIMIT', 100)];
