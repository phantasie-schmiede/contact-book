<?php
declare(strict_types=1);

use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbFoundation\Service\Configuration\ModuleService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return GeneralUtility::makeInstance(ModuleService::class)
    ->buildModuleConfiguration(GeneralUtility::makeInstance(ExtensionInformation::class));
