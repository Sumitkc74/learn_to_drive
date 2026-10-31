<?php
return [
    'payments_enabled'=>(bool)env('SCHOOL_PAYMENTS_ENABLED',false),
    'payments_live'=>(bool)env('SCHOOL_PAYMENTS_LIVE',false),
];
