<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'plugin',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'foundation' => '3.0.0',
            'php'        => '8.3',
            'typo3'      => '13.4.0-13.4.99',
            'templates'  => '0.1.0',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Comprehensive contact management',
    'state'            => 'alpha',
    'title'            => 'PSBits | Contact Book',
    'version'          => '0.1.0',
];
