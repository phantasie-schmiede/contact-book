<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service;

use Doctrine\DBAL\Exception;
use PSB\PsbContacts\Domain\Model\AbstractContactRelatedEntity;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Repository\AddressRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Domain\Repository\DocumentRepository;
use PSB\PsbContacts\Domain\Repository\EMailRepository;
use PSB\PsbContacts\Domain\Repository\EventRepository;
use PSB\PsbContacts\Domain\Repository\PhoneNumberRepository;
use PSB\PsbContacts\Domain\Repository\WebsiteRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class CleanupService
 *
 * @package PSB\PsbContacts\Service
 */
class CleanupService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly AddressRepository     $addressRepository,
        protected readonly ContactRepository     $contactRepository,
        protected readonly ContactService        $contactService,
        protected readonly DocumentRepository    $documentRepository,
        protected readonly EMailRepository       $eMailRepository,
        protected readonly EventRepository       $eventRepository,
        protected readonly PersistenceManager    $persistenceManager,
        protected readonly PhoneNumberRepository $phoneNumberRepository,
        protected readonly WebsiteRepository     $websiteRepository,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    public function cleanAll(): void
    {
        $this->deleteOrphanedAddresses();
        $this->deleteOrphanedDocuments();
        $this->deleteOrphanedEmails();
        $this->deleteOrphanedEvents();
        $this->deleteOrphanedPhoneNumbers();
        $this->deleteOrphanedWebsites();
        $this->addMissingSlugs();
        $this->addMissingUuids();
        $this->trimNotes();
        $this->updateEventDates();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedAddresses(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->addressRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'addresses');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedDocuments(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->documentRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'documents');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedEmails(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->eMailRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'e-mails');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedEvents(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->eventRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'events');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedPhoneNumbers(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->phoneNumberRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'phone numbers');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteOrphanedWebsites(): void
    {
        $deletedValues = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->websiteRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'websites');
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function trimNotes(): void
    {
        $trimmedNotes = 0;
        $this->trimNotesForRepository($this->addressRepository, $trimmedNotes);
        $this->trimNotesForRepository($this->contactRepository, $trimmedNotes);
        $this->trimNotesForRepository($this->eMailRepository, $trimmedNotes);
        $this->trimNotesForRepository($this->eventRepository, $trimmedNotes);
        $this->trimNotesForRepository($this->phoneNumberRepository, $trimmedNotes);
        $this->trimNotesForRepository($this->websiteRepository, $trimmedNotes);
        $this->persistenceManager->persistAll();

        if (0 < $trimmedNotes) {
            $this->logger->info('Trimmed ' . $trimmedNotes . ' notes.');
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    private function addMissingSlugs(): void
    {
        $addedSlugs = 0;

        /** @var Contact $contact */
        foreach ($this->contactRepository->findAll() as $contact) {
            if ('' === $contact->getSlug()) {
                $this->contactService->updateSlug($contact);
                $this->persistenceManager->persistAll();
                $addedSlugs++;
            }
        }

        if (0 < $addedSlugs) {
            $this->logger->info('Added ' . $addedSlugs . ' missing slugs.');
        }
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    private function addMissingUuids(): void
    {
        $addedUuids = 0;

        /** @var Contact $contact */
        foreach ($this->contactRepository->findAll() as $contact) {
            if ('' === $contact->getUuid()) {
                $contact->setUuid();
                $this->contactRepository->update($contact);
                $this->persistenceManager->persistAll();
                $addedUuids++;
            }
        }

        if (0 < $addedUuids) {
            $this->logger->info('Added ' . $addedUuids . ' missing UUIDs.');
        }
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedRecords(Repository $repository, array &$data): int
    {
        $records = $repository->findAll();
        $deletedRecords = 0;

        /** @var AbstractContactRelatedEntity $record */
        foreach ($records as $record) {
            if (0 === $record->getContacts()
                    ->count()) {
                $repository->remove($record);
                $deletedRecords++;

                if (method_exists($record, 'getCompactValue')) {
                    $data[] = $record->getCompactValue();
                }
            }
        }

        $this->persistenceManager->persistAll();

        return $deletedRecords;
    }

    private function logDeletion(array $data, int $deletedRecords, string $entityName): void
    {
        if (0 < $deletedRecords) {
            $this->logger->info('Deleted ' . $deletedRecords . ' orphaned ' . $entityName . '.', $data);
        }
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    private function trimNotesForRepository(Repository $repository, int &$trimmedNotes): void
    {
        /** @var AbstractContactRelatedEntity|Contact $record */
        foreach ($repository->findAll() as $record) {
            $originalValue = $record->getNotes();
            $trimmedValue = trim($originalValue);
            $record->setNotes($trimmedValue);
            $repository->update($record);

            if ($originalValue !== $trimmedValue) {
                $trimmedNotes++;
            }
        }
    }

    /**
     * Converts old property $date to new nullable properties $day, $month, $year.
     * $year is set to null if the property $yearUnknown is true.
     *
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    private function updateEventDates(): void
    {
        $events = $this->eventRepository->findAll();
        $updatedEvents = 0;

        /** @var Event $event */
        foreach ($events as $event) {
            if (null === $event->getDay() && null === $event->getMonth() && null === $event->getYear(
                ) && null !== $event->getDateLegacy()) {
                $event->setDay(
                    (int)$event->getDateLegacy()
                        ->format('d')
                );
                $event->setMonth(
                    (int)$event->getDateLegacy()
                        ->format('m')
                );
                $event->setYear(
                    $event->isYearUnknown() ? null : (int)$event->getDateLegacy()
                        ->format('Y')
                );
                $this->eventRepository->update($event);
                $updatedEvents++;
            }
        }

        $this->persistenceManager->persistAll();

        if (0 < $updatedEvents) {
            $this->logger->info('Updated ' . $updatedEvents . ' event dates.');
        }
        die((string)$updatedEvents);
    }
}
