<?php

declare(strict_types=1);

use PSBits\ContactBook\CardDav\CardDavMiddleware;

return [
    'frontend' => [
        'middleware-identifier' => [
            'after'  => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'target' => CardDavMiddleware::class,
        ],
    ],
];
