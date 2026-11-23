<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Data;

use InvalidArgumentException;
use PSBits\ContactBook\Controller\Backend\ImportController;
use PSBits\ContactBook\Controller\ContactBookController;
use PSBits\ContactBook\Controller\ContactCardController;
use PSBits\ContactBook\Controller\ContactController;
use PSBits\ContactBook\Controller\ContactGroupController;
use PSBits\ContactBook\Controller\ContactGroupWidgetController;
use PSBits\ContactBook\Controller\EventWidgetController;
use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\MainModuleConfiguration;
use PSBits\Foundation\Data\ModuleConfiguration;
use PSBits\Foundation\Data\PluginConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionInformation extends AbstractExtensionInformation
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct();
        $mainModuleKey = $this->buildModuleKeyPrefix() . 'main';
        $this->addMainModule(
            GeneralUtility::makeInstance(
                MainModuleConfiguration::class,
                key: $mainModuleKey,
                position: [
                    'after'  => 'file',
                    'before' => 'site',
                ]
            )
        );
        $this->addModule(
            GeneralUtility::makeInstance(
                ModuleConfiguration::class,
                controllers : [ImportController::class],
                key         : $this->buildModuleKeyPrefix() . 'import',
                parentModule: $mainModuleKey
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                addToElementWizard: false,
                controllers       : [
                    ContactController::class => ['getAutoCompleteList'],
                ],
                name              : 'AutocompleteList',
                typeNum           : 1754735201
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactBookController::class],
                group      : 'contactBook',
                name       : 'ContactBooks'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactCardController::class],
                group      : 'contactBook',
                name       : 'ContactCards'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactGroupController::class],
                group      : 'contactBook',
                name       : 'ContactGroups'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactGroupWidgetController::class],
                group      : 'contactBook',
                name       : 'ContactGroupWidget'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [ContactController::class],
                group      : 'contactBook',
                name       : 'ContactList'
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                addToElementWizard: false,
                controllers       : [
                    ContactController::class      => ['refreshList'],
                    ContactGroupController::class => ['refreshList'],
                ],
                name              : 'ContactListAjaxRefresh',
                typeNum           : 1749246027
            )
        );
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [EventWidgetController::class],
                group      : 'contactBook',
                name       : 'EventWidget'
            )
        );
    }
}
