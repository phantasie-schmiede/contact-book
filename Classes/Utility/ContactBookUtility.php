<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use Exception;
use JsonException;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;

class ContactBookUtility
{
    /**
     * @throws Exception
     * @throws JsonException
     */
    public static function getActiveContactBookUid(): ?int
    {
        /** @var ?FrontendUser $user */
        $user = GlobalVariableService::get(FrontendUserProvider::class . '.user');

        return $user?->getSetting('activeContactBook');
    }
}
