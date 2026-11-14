<?php
return [
    'payments_enabled'=>(bool)env('SCHOOL_PAYMENTS_ENABLED',false),
    'payments_live'=>(bool)env('SCHOOL_PAYMENTS_LIVE',false),
    'commission_percent'=>(int)env('SCHOOL_COMMISSION_PERCENT',10),
    'enrollment_fee_paisa'=>(int)env('SCHOOL_ENROLLMENT_FEE_PAISA',10000),
];
