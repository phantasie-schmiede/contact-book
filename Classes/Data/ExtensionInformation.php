<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Data;

use PSB\PsbContacts\Controller\ContactController;
use PSB\PsbContacts\Controller\EventWidgetController;
use PSB\PsbFoundation\Data\AbstractExtensionInformation;
use PSB\PsbFoundation\Data\PluginConfiguration;
use PSB\PsbFoundation\Enum\ContentType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionInformation
 *
 * @package PSB\PsbContacts\Data
 */
class ExtensionInformation extends AbstractExtensionInformation
{
    public const TYPE_NUMS = [
        'ConfirmDeleteContact' => 1715345584,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                addToElementWizard: false,
                controllers       : [ContactController::class => ['confirmDelete']],
                name              : 'ConfirmDeleteContact',
                typeNum           : self::TYPE_NUMS['ConfirmDeleteContact'],
                typeNumContentType: ContentType::JSON
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactController::class],
                name       : 'ContactList'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [EventWidgetController::class],
                name       : 'EventWidget'
            )
        );
    }
}
