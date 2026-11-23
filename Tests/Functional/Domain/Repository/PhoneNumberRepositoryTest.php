<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\PhoneNumberDemand;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Domain\Repository\PhoneNumberRepository;
use PSBits\Qa\Testing\Support\PersistenceSupportTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class PhoneNumberRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class PhoneNumberRepositoryTest extends FunctionalTestCase
{
    use PersistenceSupportTrait;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/friendsoftypo3/content-blocks',
        'typo3conf/ext/psbits/contact-book',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/frontend-templates',
        'typo3conf/ext/psbits/frontend-users',
        'typo3conf/ext/psbits/view-helpers',
        'typo3conf/ext/wapplersystems/ws-scss',
    ];

    #[Test]
    public function phoneNumbersMatchingTheDemandAreReturned(): void
    {
        $phoneNumberOne = $this->createPhoneNumber('49', '30', '1234567');
        $phoneNumberTwo = $this->createPhoneNumber('49', '89', '7654321');
        $this->persistAll($phoneNumberOne, $phoneNumberTwo);

        $repository = GeneralUtility::makeInstance(PhoneNumberRepository::class);

        $demand = new PhoneNumberDemand();
        $demand->setAreaCode('30');

        self::assertSame(
            [$phoneNumberOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );

        $demand = new PhoneNumberDemand();
        $demand->setAreaCode('30')
            ->setConnectionIdentifier('1234567');

        self::assertSame(
            [$phoneNumberOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );

        $demand = new PhoneNumberDemand();
        $demand->setAreaCode('30')
            ->setConnectionIdentifier('9999999');

        self::assertCount(0, $repository->findByDemand($demand));
    }

    private function createPhoneNumber(
        string $internationalDiallingCode,
        string $areaCode,
        string $connectionIdentifier,
    ): PhoneNumber {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setInternationalDiallingCode($internationalDiallingCode)
            ->setAreaCode($areaCode)
            ->setConnectionIdentifier($connectionIdentifier);

        return $phoneNumber;
    }

    /**
     * @return list<int>
     */
    private function getUids(iterable $result): array
    {
        $uids = [];
        foreach ($result as $phoneNumber) {
            $uids[] = $phoneNumber->getUid();
        }

        sort($uids);

        return $uids;
    }
}
