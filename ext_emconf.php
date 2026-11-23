<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'plugin',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'foundation'         => '4.2.2',
            'frontend_templates' => '0.1.0',
            'php'                => '8.3.0-8.5.99',
            'typo3'              => '13.4.0-14.99.99',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Comprehensive contact management',
    'state'            => 'alpha',
    'title'            => 'PSBits | Contact Book',
    'version'          => '0.1.0',
];
