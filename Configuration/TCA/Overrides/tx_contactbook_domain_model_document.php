<?php
declare(strict_types=1);

// Set cropvariants for property 'file' in 'tx_contactbook_domain_model_document'
$GLOBALS['TCA']['tx_contactbook_domain_model_document']['columns']['file']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants'] = [
    'cardLandscape' => [
        'title'               => 'Card (landscape)',
        'allowedAspectRatios' => [
            '16:9' => [
                'title' => '16:9',
                'value' => 16 / 9,
            ],
        ],
    ],
    'cardPortrait'  => [
        'title'               => 'Card (portrait)',
        'allowedAspectRatios' => [
            '3:4' => [
                'title' => '3:4',
                'value' => 3 / 4,
            ],
        ],
    ],
];
