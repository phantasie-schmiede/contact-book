<?php
declare(strict_types=1);

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['LOG']['PSB']['PsbContacts']['Service']['writerConfiguration'] = [
    LogLevel::INFO => [
        DatabaseWriter::class => [
            'logTable' => 'tx_psbcontacts_log',
        ],
    ],
];
