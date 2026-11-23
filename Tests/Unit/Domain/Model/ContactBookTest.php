<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use JsonException;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactBookTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class ContactBookTest extends UnitTestCase
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
    public function theCurrentUserIsNotListedAsOtherOwner(): void
    {
        $this->seedFrontendUserGlobal($this->createFrontendUser(1));

        $contactBook = new ContactBook();
        $contactBook->addOwner($this->createFrontendUser(1))
            ->addOwner($this->createFrontendUser(2));

        self::assertSame([2], $this->getUids($contactBook->getOtherOwners()));
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function theBookIsActiveWhenTheUserSelectedIt(): void
    {
        $this->seedFrontendUserGlobal($this->createFrontendUser(1));
        $contactBook = new ContactBook();
        $this->setUid($contactBook, 7);

        self::assertFalse($contactBook->isActive());

        $activeUser = $this->createFrontendUser(1);
        $activeUser->setSetting('activeContactBook', 7);
        $this->seedFrontendUserGlobal($activeUser);

        self::assertTrue($contactBook->isActive());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theOwnersCanBeRemoved(): void
    {
        $contactBook = new ContactBook();
        $contactBook->addOwner($this->createFrontendUser(1));
        self::assertCount(1, $contactBook->getOwners());

        $contactBook->removeAllOwners();

        self::assertCount(0, $contactBook->getOwners());
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<FrontendUser> $owners
     *
     * @return list<int>
     */
    private function getUids(object $owners): array
    {
        $uids = [];
        foreach ($owners as $owner) {
            $uids[] = $owner->getUid();
        }

        return $uids;
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
    private function createFrontendUser(int $uid): FrontendUser
    {
        $user = new FrontendUser();
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
