<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Service\ContactBookService;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Domain\Repository\Typo3\FrontendUserRepository;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use ReflectionException;
use ReflectionProperty;
use UnexpectedValueException;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactBookServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class ContactBookServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GlobalVariableService::clearCache();
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theCacheEntryIdentifierContainsTheActiveContactBook(): void
    {
        $this->seedFrontendUserGlobal($this->createFrontendUser(1, 42));

        self::assertSame('tx_contactbook_contacts_list_42', $this->createService()->getCacheEntryIdentifier());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theCacheEntryIdentifierIsEmptyWithoutAnActiveContactBook(): void
    {
        $user = new FrontendUser();
        $this->setUid($user, 1);
        $this->seedFrontendUserGlobal($user);

        self::assertSame('tx_contactbook_contacts_list_', $this->createService()->getCacheEntryIdentifier());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theActiveContactBookIsReturned(): void
    {
        $contactBook = new ContactBook();
        $this->setUid($contactBook, 42);

        $contactBookRepository = $this->createMock(ContactBookRepository::class);
        $contactBookRepository->method('findByUid')
            ->with(42)
            ->willReturn($contactBook);

        $this->seedFrontendUserGlobal($this->createFrontendUser(1, 42));

        self::assertSame($contactBook, $this->createService($contactBookRepository)->getActiveContactBookForCurrentUser());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theFirstContactBookOfTheUserIsUsedAndActivatedAsAFallback(): void
    {
        $contactBook = new ContactBook();
        $this->setUid($contactBook, 7);

        $contactBookRepository = $this->createMock(ContactBookRepository::class);
        $contactBookRepository->method('findByUid')
            ->willReturn(null);
        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('getFirst')
            ->willReturn($contactBook);
        $contactBookRepository->method('findByOwner')
            ->willReturn($queryResult);

        $user = $this->createFrontendUser(1, 0);
        $this->seedFrontendUserGlobal($user);

        $frontendUserRepository = $this->createMock(FrontendUserRepository::class);
        $frontendUserRepository->expects(self::once())
            ->method('update')
            ->with($user);
        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::once())
            ->method('persistAll');

        $service = new ContactBookService(
            $contactBookRepository,
            $frontendUserRepository,
            $persistenceManager,
            $this->createMock(TcaService::class),
        );

        self::assertSame($contactBook, $service->getActiveContactBookForCurrentUser());
        self::assertSame(7, $user->getSetting('activeContactBook'));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function anExceptionIsThrownWithoutContactBooks(): void
    {
        $contactBookRepository = $this->createMock(ContactBookRepository::class);
        $contactBookRepository->method('findByUid')
            ->willReturn(null);
        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('getFirst')
            ->willReturn(null);
        $contactBookRepository->method('findByOwner')
            ->willReturn($queryResult);

        $this->seedFrontendUserGlobal($this->createFrontendUser(1, 0));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('No contact book found for the current user.');

        $this->createService($contactBookRepository)->getActiveContactBookForCurrentUser();
    }

    private function createService(?ContactBookRepository $contactBookRepository = null): ContactBookService
    {
        return new ContactBookService(
            $contactBookRepository ?? $this->createMock(ContactBookRepository::class),
            $this->createMock(FrontendUserRepository::class),
            $this->createMock(PersistenceManager::class),
            $this->createMock(TcaService::class),
        );
    }

    /**
     * @throws ReflectionException
     */
    private function seedFrontendUserGlobal(?FrontendUser $user): void
    {
        GlobalVariableService::registerGlobalVariableProvider(FrontendUserProvider::class);

        $provider = $this->createMock(FrontendUserProvider::class);
        $provider->method('getGlobalVariables')
            ->willReturn([
                'loginPage' => null,
                'sysFolder' => null,
                'user'      => $user,
            ]);
        $provider->method('isCacheable')
            ->willReturn(false);

        $property  = new ReflectionProperty(GlobalVariableService::class, 'globalVariableProviders');
        $providers = $property->getValue();
        $providers[FrontendUserProvider::class] = $provider;
        $property->setValue(null, $providers);
    }

    /**
     * @throws ReflectionException
     */
    private function createFrontendUser(int $uid, int $activeContactBookUid): FrontendUser
    {
        $user = new FrontendUser();
        $user->setSetting('activeContactBook', $activeContactBookUid);
        $this->setUid($user, $uid);

        return $user;
    }

    /**
     * @throws ReflectionException
     */
    private function setUid(AbstractDomainObject $object, int $uid): void
    {
        $property = new ReflectionProperty(AbstractDomainObject::class, 'uid');
        $property->setValue($object, $uid);
    }
}
