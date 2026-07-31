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
use PSBits\Templates\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

use function sprintf;

/**
 * Class ContactUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class ContactUtility
{
    /**
     * @param Contact[] $contacts
     * @param UriBuilder|null $uriBuilder
     *
     * @return string
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function createEnumeration(array $contacts, ?UriBuilder $uriBuilder = null): string
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

                $contactString = $contact->getNameDecorations() . (empty($family) ? $contact->getShortName(
                ) : $contact->getFirstName());

                if ($uriBuilder instanceof UriBuilder) {
                    $url = $uriBuilder->reset()
                        ->uriFor('show', ['contact' => $contact], 'Contact', 'ContactBook', 'ContactList');
                    $contactString = sprintf(
                        '<a class="text-decoration-none" href="%s">%s</a>',
                        $url,
                        $contactString
                    );
                }

                $familyMembers[] = $contactString;
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

    /**
     * @param Contact[] $contacts
     *
     * @return Contact[]
     */
    public static function sortByListName(array $contacts): array
    {
        usort(
            $contacts,
            static fn(Contact $a, Contact $b) => strnatcasecmp(
                NameUtility::getCompareString($a->getListName()),
                NameUtility::getCompareString($b->getListName())
            )
        );

        return $contacts;
    }
}
