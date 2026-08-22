<?php
declare(strict_types=1);

use Psr\Log\LogLevel;
use PSBits\ContactBook\CardDav\CardDavMiddleware;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['LOG']['PSBits']['ContactBook']['Service']['writerConfiguration'] = [
    LogLevel::INFO => [
        DatabaseWriter::class => [
            'logTable' => 'tx_contactbook_log',
        ],
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['contacts_list'] ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['contacts_list']['backend'] ??= Typo3DatabaseBackend::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['middlewares']['contact-book/carddav'] = [
    'after'  => [
        'typo3/cms-core/normalized-params-attribute',
    ],
    'target' => CardDavMiddleware::class,
];
