<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Utility;

use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\Event;

/**
 * Class ContactUtility
 *
 * @package PSB\PsbContacts\Utility
 */
class ContactUtility
{
    public static function getBirthEvent(Contact $contact): ?Event
    {
        foreach ($contact->getEvents() as $event) {
            if (EventType::Birthday === $event->getType()) {
                return $event;
            }
        }

        return null;
    }

    public static function sortByDisplayName(array $contacts): array
    {
        usort(
            $contacts,
            static fn(Contact $a, Contact $b) => strnatcasecmp(
            NameUtility::getCompareString($a->getDisplayName()),
            NameUtility::getCompareString($b->getDisplayName())
        )
        );

        return $contacts;
    }
}
