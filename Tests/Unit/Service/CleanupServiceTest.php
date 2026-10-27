<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
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
use PSBits\ContactBook\Service\CleanupService;
use PSBits\ContactBook\Service\ContactBookService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\ContactBook\Tests\Unit\Fixtures\ArrayQueryResult;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class CleanupServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class CleanupServiceTest extends UnitTestCase
{
    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function orphanedAddressesAreDeletedAndLogged(): void
    {
        $orphanedAddress = new Address();
        $orphanedAddress->setCity('Berlin')
            ->setHouseNumber('12')
            ->setStreet('Hauptstraße')
            ->setZipCode('10115');

        $usedAddress = new Address();
        $usedAddress->addContact(new Contact());

        $addressRepository = $this->createMock(AddressRepository::class);
        $addressRepository->expects(self::once())
            ->method('remove')
            ->with($orphanedAddress);
        $addressRepository->method('findAll')
            ->willReturn($this->createQueryResultMock($orphanedAddress, $usedAddress));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Deleted 1 orphaned addresses.', ['Hauptstraße 12, 10115 Berlin']);

        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::atLeastOnce())
            ->method('persistAll');

        $cleanupService = $this->createCleanupService($addressRepository, $persistenceManager);
        $cleanupService->setLogger($logger);

        $cleanupService->cleanAll();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function jobsWithoutAContactAreDeleted(): void
    {
        $orphanedJob = new Job();
        $orphanedJob->setPosition('Developer');

        $usedJob = new Job();
        $usedJob->setContact(new Contact());

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects(self::once())
            ->method('remove')
            ->with($orphanedJob);
        $jobRepository->method('findAll')
            ->willReturn($this->createQueryResultMock($orphanedJob, $usedJob));

        $addressRepository = $this->createMock(AddressRepository::class);
        $addressRepository->method('findAll')
            ->willReturn(new ArrayQueryResult());

        $cleanupService = $this->createCleanupService(
            $addressRepository,
            $this->createMock(PersistenceManager::class),
            $jobRepository,
        );
        $cleanupService->setLogger($this->createMock(LoggerInterface::class));

        $cleanupService->cleanAll();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function nothingIsDeletedWithoutOrphanedRecords(): void
    {
        $usedAddress = new Address();
        $usedAddress->addContact(new Contact());

        $addressRepository = $this->createMock(AddressRepository::class);
        $addressRepository->expects(self::never())
            ->method('remove');
        $addressRepository->method('findAll')
            ->willReturn($this->createQueryResultMock($usedAddress));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('info');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects(self::never())
            ->method('remove');
        $jobRepository->method('findAll')
            ->willReturn($this->createQueryResultMock());

        $cleanupService = $this->createCleanupService(
            $addressRepository,
            $this->createMock(PersistenceManager::class),
            $jobRepository,
        );
        $cleanupService->setLogger($logger);

        $cleanupService->cleanAll();
    }

    private function createQueryResultMock(Address|Job ...$records): QueryResultInterface
    {
        return new ArrayQueryResult($records);
    }

    private function createCleanupService(
        AddressRepository    $addressRepository,
        PersistenceManager   $persistenceManager,
        ?JobRepository       $jobRepository = null,
    ): CleanupService {
        $contactGroupRepository = $this->createMock(ContactGroupRepository::class);
        $contactRepository      = $this->createMock(ContactRepository::class);
        $documentRepository     = $this->createMock(DocumentRepository::class);
        $eMailRepository        = $this->createMock(EMailRepository::class);
        $eventRepository        = $this->createMock(EventRepository::class);
        $phoneNumberRepository  = $this->createMock(PhoneNumberRepository::class);
        $websiteRepository      = $this->createMock(WebsiteRepository::class);

        $emptyRepositories = [
            $contactGroupRepository,
            $contactRepository,
            $documentRepository,
            $eMailRepository,
            $eventRepository,
            $phoneNumberRepository,
            $websiteRepository,
        ];

        if (null === $jobRepository) {
            $jobRepository         = $this->createMock(JobRepository::class);
            $emptyRepositories[]   = $jobRepository;
        }

        foreach ($emptyRepositories as $repository) {
            $repository->method('findAll')
                ->willReturn(new ArrayQueryResult());
        }

        return new CleanupService(
            $addressRepository,
            $this->createMock(ContactBookService::class),
            $contactGroupRepository,
            $contactRepository,
            $this->createMock(ContactService::class),
            $documentRepository,
            $eMailRepository,
            $eventRepository,
            $jobRepository,
            $persistenceManager,
            $phoneNumberRepository,
            $websiteRepository,
        );
    }
}
