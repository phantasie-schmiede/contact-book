<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Repository\AddressRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Domain\Repository\EMailRepository;
use PSBits\ContactBook\Domain\Repository\PhoneNumberRepository;
use PSBits\ContactBook\Domain\Repository\WebsiteRepository;
use PSBits\ContactBook\Service\ImportService;
use PSBits\ContactBook\Tests\Unit\Fixtures\ArrayQueryResult;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ImportServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class ImportServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GlobalVariableService::clearCache();
        $this->seedFrontendUserGlobal();

        if (!defined('CR')) {
            define('CR', "\r");
        }

        if (!defined('CRLF')) {
            define('CRLF', "\r\n");
        }

        if (!defined('LF')) {
            define('LF', "\n");
        }
    }

    /**
     * @throws ReflectionException
     */
    private function seedFrontendUserGlobal(): void
    {
        GlobalVariableService::registerGlobalVariableProvider(FrontendUserProvider::class);

        $provider = $this->createMock(FrontendUserProvider::class);
        $provider->method('getGlobalVariables')
            ->willReturn([
                'loginPage' => null,
                'sysFolder' => null,
                'user'      => null,
            ]);
        $provider->method('isCacheable')
            ->willReturn(false);

        $property  = new ReflectionProperty(GlobalVariableService::class, 'globalVariableProviders');
        $providers = $property->getValue();
        $providers[FrontendUserProvider::class] = $provider;
        $property->setValue(null, $providers);
    }

    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function aNewContactIsAddedWithAllParsedProperties(): void
    {
        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByDemand')
            ->willReturn(new ArrayQueryResult());
        $contactRepository->expects(self::never())
            ->method('update');
        $addedContact = null;
        $contactRepository->expects(self::once())
            ->method('add')
            ->willReturnCallback(
                function (Contact $contact) use (&$addedContact): void {
                    $addedContact = $contact;
                }
            );

        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::once())
            ->method('persistAll');

        $importService = $this->createImportService($contactRepository, $persistenceManager);

        $importService->parseVCardFile(__DIR__ . '/../Fixtures/Import/contact.vcf');

        self::assertInstanceOf(Contact::class, $addedContact);

        /** @var Contact $addedContact */
        self::assertSame('Max', $addedContact->getFirstName());
        self::assertSame('Philipp', $addedContact->getMiddleNames());
        self::assertSame('Mustermann', $addedContact->getLastName());
        self::assertSame('Maxi', $addedContact->getNickname());
        self::assertStringContainsString('Test note', $addedContact->getNotes());
        self::assertStringContainsString('Dr.', $addedContact->getNotes());

        self::assertCount(1, $addedContact->getAddresses());
        $address = $addedContact->getAddresses()
            ->toArray()[0];
        self::assertSame('Hauptstraße', $address->getStreet());
        self::assertSame('12', $address->getHouseNumber());
        self::assertSame('Berlin', $address->getCity());
        self::assertSame('Berlin', $address->getCounty());
        self::assertSame('10115', $address->getZipCode());
        self::assertSame('Germany', $address->getCountry());
        self::assertSame(GeneralType::Private, $address->getType());

        self::assertCount(1, $addedContact->getEMails());
        $eMail = $addedContact->getEMails()
            ->toArray()[0];
        self::assertSame('max@example.com', $eMail->getEMailAddress());
        self::assertSame(GeneralType::Private, $eMail->getType());

        self::assertCount(1, $addedContact->getEvents());
        $event = $addedContact->getEvents()
            ->toArray()[0];
        self::assertSame(EventType::Birthday, $event->getType());
        self::assertSame(15, $event->getDay());
        self::assertSame(5, $event->getMonth());
        self::assertSame(1990, $event->getYear());

        self::assertCount(1, $addedContact->getPhoneNumbers());
        $phoneNumber = $addedContact->getPhoneNumbers()
            ->toArray()[0];
        self::assertSame('49', $phoneNumber->getInternationalDiallingCode());
        self::assertSame('30', $phoneNumber->getAreaCode());
        self::assertSame('1234567', $phoneNumber->getConnectionIdentifier());
        self::assertSame(GeneralType::Private, $phoneNumber->getGeneralType());
        self::assertSame(PhoneNumberType::Mobile, $phoneNumber->getPhoneNumberType());

        self::assertCount(1, $addedContact->getJobs());
        $job = $addedContact->getJobs()
            ->toArray()[0];
        self::assertInstanceOf(Contact::class, $job->getCompany());
        self::assertSame('Acme GmbH', $job->getCompany()
            ->getCompanyName());
        self::assertSame(ContactType::Company, $job->getCompany()
            ->getType());

        self::assertCount(1, $addedContact->getWebsites());
        self::assertSame('https://example.com', $addedContact->getWebsites()
            ->toArray()[0]->getUrl());
    }

    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function anExistingContactIsMergedInsteadOfAdded(): void
    {
        $existingContact = new Contact();
        $existingContact->setFirstName('Max')
            ->setLastName('Mustermann');

        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByDemand')
            ->willReturn(new ArrayQueryResult([$existingContact]));
        $contactRepository->expects(self::never())
            ->method('add');
        $contactRepository->expects(self::once())
            ->method('update')
            ->with($existingContact);

        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::once())
            ->method('persistAll');

        $importService = $this->createImportService($contactRepository, $persistenceManager);

        $importService->parseVCardFile(__DIR__ . '/../Fixtures/Import/contact.vcf');

        self::assertSame('Maxi', $existingContact->getNickname());
        self::assertStringContainsString('Test note', $existingContact->getNotes());
        self::assertCount(1, $existingContact->getAddresses());
        self::assertCount(1, $existingContact->getEMails());
        self::assertCount(1, $existingContact->getEvents());
        self::assertCount(1, $existingContact->getPhoneNumbers());
        self::assertCount(1, $existingContact->getWebsites());
    }

    /**
     * @throws IllegalObjectTypeException
     */
    #[Test]
    public function nonGermanPhoneNumbersAreSkipped(): void
    {
        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByDemand')
            ->willReturn(new ArrayQueryResult());
        $addedContact = null;
        $contactRepository->expects(self::once())
            ->method('add')
            ->willReturnCallback(
                function (Contact $contact) use (&$addedContact): void {
                    $addedContact = $contact;
                }
            );

        $importService = $this->createImportService($contactRepository);

        $importService->parseVCardFile(__DIR__ . '/../Fixtures/Import/foreign-phone.vcf');

        /** @var Contact $addedContact */
        self::assertSame('John', $addedContact->getFirstName());
        self::assertSame('Smith', $addedContact->getLastName());
        self::assertCount(0, $addedContact->getPhoneNumbers());
    }

    private function createImportService(
        ContactRepository   $contactRepository,
        ?PersistenceManager $persistenceManager = null,
    ): ImportService {
        $persistenceManager ??= $this->createMock(PersistenceManager::class);

        return new ImportService(
            $this->createEmptyRepository(AddressRepository::class),
            $contactRepository,
            $this->createEmptyRepository(EMailRepository::class),
            $persistenceManager,
            $this->createEmptyRepository(PhoneNumberRepository::class),
            $this->createEmptyRepository(WebsiteRepository::class),
        );
    }

    private function createEmptyRepository(string $repositoryClass): object
    {
        $repository = $this->createMock($repositoryClass);
        $repository->method('findByDemand')
            ->willReturn(new ArrayQueryResult());

        return $repository;
    }
}
