<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'plugin',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'php'   => '8.1',
            'psb_foundation' => '2.2.1',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Comprehensive cntact management',
    'state'            => 'alpha',
    'title'            => 'PSbits | Contacts',
    'version'          => '0.0.0',
];
