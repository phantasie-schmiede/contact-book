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
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\Event;
use PSB\PsbTemplates\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

/**
 * Class ContactUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class ContactUtility
{
    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function createEnumeration(array $contacts): string
    {
        $contactsByFamilyName = [];

        foreach ($contacts as $contact) {
            $contactsByFamilyName[$contact->getLastName()][] = $contact;
        }

        $names = [];

        while (!empty($contactsByFamilyName)) {
            $family = array_shift($contactsByFamilyName);

            $familyMembers = [];

            while (!empty($family)) {
                /** @var Contact $contact */
                $contact = array_shift($family);

                if (empty($family)) {
                    $familyMembers[] = $contact->getFullName();
                } else {
                    $familyMembers[] = $contact->getFirstNames();
                }
            }

            $names[] = StringUtility::createEnumeration($familyMembers);
        }

        return StringUtility::createEnumeration($names);
    }

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
