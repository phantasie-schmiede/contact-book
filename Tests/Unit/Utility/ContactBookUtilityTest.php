<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use PSBits\ContactBook\Utility\ContactBookUtility;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactBookUtilityTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Utility
 */
class ContactBookUtilityTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GlobalVariableService::clearCache();
    }

    #[Test]
    public function theActiveContactBookIsReturnedForTheCurrentUser(): void
    {
        $user = new FrontendUser();
        $user->setSetting('activeContactBook', 42);
        $this->seedFrontendUserGlobal($user);

        self::assertSame(42, ContactBookUtility::getActiveContactBookUid());
    }

    #[Test]
    public function theActiveContactBookIsNullWithoutASetting(): void
    {
        $this->seedFrontendUserGlobal(new FrontendUser());

        self::assertNull(ContactBookUtility::getActiveContactBookUid());
    }

    #[Test]
    public function theActiveContactBookIsNullWithoutACurrentUser(): void
    {
        $this->seedFrontendUserGlobal(null);

        self::assertNull(ContactBookUtility::getActiveContactBookUid());
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
}
