<?php

declare(strict_types=1);

use PSBits\ContactBook\CardDav\CardDavMiddleware;

return [
    'frontend' => [
        'middleware-identifier' => [
            'target' => CardDavMiddleware::class,
        ],
    ],
];
