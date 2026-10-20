<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Service;

use PSBits\ContactBook\Domain\Model\AbstractContactRelatedEntity;
use PSBits\ContactBook\Domain\Model\Job;
use PSBits\ContactBook\Domain\Repository\AddressRepository;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Domain\Repository\DocumentRepository;
use PSBits\ContactBook\Domain\Repository\EMailRepository;
use PSBits\ContactBook\Domain\Repository\EventRepository;
use PSBits\ContactBook\Domain\Repository\JobRepository;
use PSBits\ContactBook\Domain\Repository\PhoneNumberRepository;
use PSBits\ContactBook\Domain\Repository\WebsiteRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

use function count;
use function is_string;

/**
 * Class CleanupService
 *
 * @package PSBits\ContactBook\Service
 */
class CleanupService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly AddressRepository      $addressRepository,
        protected readonly ContactBookService     $contactBookService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactRepository      $contactRepository,
        protected readonly ContactService         $contactService,
        protected readonly DocumentRepository     $documentRepository,
        protected readonly EMailRepository        $eMailRepository,
        protected readonly EventRepository        $eventRepository,
        protected readonly JobRepository          $jobRepository,
        protected readonly PersistenceManager     $persistenceManager,
        protected readonly PhoneNumberRepository  $phoneNumberRepository,
        protected readonly WebsiteRepository      $websiteRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function cleanAll(): void
    {
        $this->deleteOrphanedAddresses();
        $this->deleteOrphanedDocuments();
        $this->deleteOrphanedEmails();
        $this->deleteOrphanedEvents();
        $this->deleteOrphanedJobs();
        $this->deleteOrphanedPhoneNumbers();
        $this->deleteOrphanedWebsites();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedAddresses(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->addressRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'addresses');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedDocuments(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->documentRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'documents');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedEmails(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->eMailRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'e-mails');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedEvents(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->eventRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'events');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedJobs(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues = [];

        /** @var Job $job */
        foreach ($this->jobRepository->findAll() as $job) {
            if (null === $job->getContact()) {
                $this->jobRepository->remove($job);
                $deletedValues[] = $job->getCompactValue();
            }
        }

        $this->persistenceManager->persistAll();

        $this->logDeletion($deletedValues, count($deletedValues), 'jobs');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedPhoneNumbers(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->phoneNumberRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'phone numbers');
    }

    /**
     * @template T of AbstractContactRelatedEntity
     * @param Repository<T>      $repository
     * @param array<int, string> $data
     * @param-out array<int, string> $data
     *
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedRecords(Repository $repository, array &$data): int
    {
        $records        = $repository->findAll();
        $deletedRecords = 0;

        /** @var AbstractContactRelatedEntity $record */
        foreach ($records as $record) {
            if (0 === $record->getContacts()
                    ->count()) {
                $repository->remove($record);
                $deletedRecords++;

                if (method_exists($record, 'getCompactValue')) {
                    $compactValue = $record->getCompactValue();

                    if (is_string($compactValue)) {
                        $data[] = $compactValue;
                    }
                }
            }
        }

        $this->persistenceManager->persistAll();

        return $deletedRecords;
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function deleteOrphanedWebsites(): void
    {
        /** @var array<int, string> $deletedValues */
        $deletedValues          = [];
        $numberOfDeletedRecords = $this->deleteOrphanedRecords($this->websiteRepository, $deletedValues);
        $this->logDeletion($deletedValues, $numberOfDeletedRecords, 'websites');
    }

    /** @param array<int, string> $data */
    private function logDeletion(array $data, int $deletedRecords, string $entityName): void
    {
        if (0 < $deletedRecords) {
            $this->logger->info('Deleted ' . $deletedRecords . ' orphaned ' . $entityName . '.', $data);
        }
    }
}
