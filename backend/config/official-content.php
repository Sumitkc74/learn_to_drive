<?php

return [
    'ca_bundle' => env('OFFICIAL_CONTENT_CA_BUNDLE'),
    'sources' => [
        'nei-vision' => [
            'name' => 'National Eye Institute: color vision plate',
            'url' => 'https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/color-blindness/testing-color-vision-deficiency',
            'kind' => 'vision-test-image',
            'image_xpath' => '//img[contains(@alt, "Colorplate")]',
            'hosts' => ['www.nei.nih.gov', 'nei.nih.gov'],
        ],
        'dotm-b' => [
            'name' => 'DoTM: Category B question bank 2082-83',
            'url' => 'https://www.dotm.gov.np/content/111/-b--written-examination-questions-for-class-2082-83/',
            'kind' => 'question-bank-document',
            'hosts' => ['www.dotm.gov.np', 'dotm.gov.np', 'giwmscdnone.gov.np'],
        ],
        'dotm-ak' => [
            'name' => 'DoTM: Category A/K question bank 2082-83',
            'url' => 'https://www.dotm.gov.np/content/112/written-test-questions-2082-83-for--ak--class/',
            'kind' => 'question-bank-document',
            'hosts' => ['www.dotm.gov.np', 'dotm.gov.np', 'giwmscdnone.gov.np'],
        ],
        'kalanki' => [
            'name' => 'Transport Management Office Kalanki: question collections',
            'url' => 'https://tmokalanki.bagamati.gov.np/pages/question-and-answer-collection-12/',
            'kind' => 'question-bank-document',
            'hosts' => ['tmokalanki.bagamati.gov.np', 'giwmscdnone.gov.np'],
        ],
        'traffic-police' => [
            'name' => 'Nepal Traffic Police: traffic sign sheets',
            'url' => 'https://traffic.nepalpolice.gov.np/about-us/traffic-signs/',
            'kind' => 'traffic-sign-sheet',
            'hosts' => ['traffic.nepalpolice.gov.np'],
        ],
    ],
];
