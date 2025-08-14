<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use JsonException;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class JsonUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class JsonUtility
{
    /**
     * @throws JsonException
     */
    public static function getUidsAsJsonArray(AbstractEntity ...$domainObjects): false|string
    {
        $uids = [];

        foreach ($domainObjects as $domainObject) {
            $uids[] = $domainObject->getUid();
        }

        return json_encode($uids, JSON_THROW_ON_ERROR);
    }
}
