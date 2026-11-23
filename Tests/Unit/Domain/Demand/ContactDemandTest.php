<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Demand;

use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactDemandTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Demand
 */
class ContactDemandTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GlobalVariableService::clearCache();
    }

    #[Test]
    public function theActiveContactBookOfTheCurrentUserIsUsedByDefault(): void
    {
        $this->seedFrontendUserGlobal($this->createFrontendUser(42));

        self::assertSame(42, (new ContactDemand())->getContactBookUid());
    }

    #[Test]
    public function theContactBookDefaultsToZeroWithoutACurrentUser(): void
    {
        $this->seedFrontendUserGlobal(null);

        self::assertSame(0, (new ContactDemand())->getContactBookUid());
    }

    #[Test]
    public function categoriesAndContactsCanBeAdded(): void
    {
        $this->seedFrontendUserGlobal(null);
        $demand = new ContactDemand();

        $demand->addCategoryUids(1, 2)
            ->addContactUids(3, 4);

        self::assertSame([1, 2], $demand->getCategoryUids());
        self::assertSame([3, 4], $demand->getContactUids());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function contactsCanBeExcludedByModelOrUid(): void
    {
        $this->seedFrontendUserGlobal(null);
        $demand = new ContactDemand();

        $contact = new Contact();
        $this->setUid($contact, 7);
        $demand->addExcludedContact($contact)
            ->addExcludedContact(17);

        self::assertSame([7, 17], $demand->getExcludedContacts());
    }

    #[Test]
    public function typesCanBeAdded(): void
    {
        $this->seedFrontendUserGlobal(null);
        $demand = new ContactDemand();

        $demand->addType(ContactType::Company)
            ->addType(ContactType::Person);

        self::assertSame([ContactType::Company, ContactType::Person], $demand->getTypes());
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
    private function createFrontendUser(int $activeContactBookUid): FrontendUser
    {
        $user = new FrontendUser();
        $user->setSetting('activeContactBook', $activeContactBookUid);

        $this->setUid($user, $activeContactBookUid);

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
