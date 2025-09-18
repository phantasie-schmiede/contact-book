<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Service;

use Exception;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;

/**
 * Class EventService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class EventService
{
    public function __construct(
        protected ContactBookRepository $contactBookRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getAllEventUidsForCurrentFrontendUser(): array
    {
        $contactBooks = $this->contactBookRepository->findByOwner();
        $eventUids = [];

        foreach ($contactBooks as $contactBook) {
            foreach ($contactBook->getContacts() as $contact) {
                foreach ($contact->getEvents() as $event) {
                    $eventUids[] = $event->getUid();
                }
            }
        }

        return array_unique($eventUids);
    }
}
