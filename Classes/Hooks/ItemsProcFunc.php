<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Hooks;

use PSB\PsbContacts\Domain\Enum\EventType;

/**
 * Class ItemsProcFunc
 *
 * @package PSB\PsbContacts\Hooks
 */
class ItemsProcFunc
{
    public static function getEventTypes(array &$params): void
    {
        $params['items'] = [];

        foreach (EventType::toArray(EventType::Birthday) as $value => $name) {
            $params['items'][] = [
                'label' => 'LLL:EXT:psb_contacts/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.' . $value,
                'value' => $value,
            ];
        }
    }
}
