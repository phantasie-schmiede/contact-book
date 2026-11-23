<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Hooks;

use PSBits\ContactBook\Domain\Enum\EventType;

use function strtolower;

class ItemsProcFunc
{
    /** @param array<string, mixed> $params */
    public static function getEventTypes(array &$params): void
    {
        $params['items'] = [];

        foreach (EventType::toArray(EventType::Birthday) as $value => $name) {
            $params['items'][] = [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.' . strtolower($value),
                'value' => $value,
            ];
        }
    }
}
