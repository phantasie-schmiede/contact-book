<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\AddressDemand;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Repository\AddressRepository;
use PSBits\Qa\Testing\Support\PersistenceSupportTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class AddressRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class AddressRepositoryTest extends FunctionalTestCase
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
    public function addressesMatchingTheDemandAreReturned(): void
    {
        $addressOne = $this->createAddress('Hauptstraße', '12', 'Berlin', '10115', 'Germany');
        $addressTwo = $this->createAddress('Schulstraße', '5', 'Berlin', '10117', 'Germany');
        $addressThree = $this->createAddress('Hauptstraße', '1', 'Hamburg', '20095', 'Germany');
        $this->persistAll($addressOne, $addressTwo, $addressThree);

        $repository = GeneralUtility::makeInstance(AddressRepository::class);

        $demand = new AddressDemand();
        $demand->setCity('Berlin');

        self::assertCount(2, $repository->findByDemand($demand));

        $demand = new AddressDemand();
        $demand->setCity('Berlin')
            ->setStreet('Hauptstraße');

        self::assertSame(
            [$addressOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );

        $demand = new AddressDemand();
        $demand->setStreet('Hauptstraße')
            ->setHouseNumber('12')
            ->setZipCode('10115');

        self::assertSame(
            [$addressOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );
    }

    private function createAddress(
        string $street,
        string $houseNumber,
        string $city,
        string $zipCode,
        string $country,
    ): Address {
        $address = new Address();
        $address->setStreet($street)
            ->setHouseNumber($houseNumber)
            ->setCity($city)
            ->setZipCode($zipCode)
            ->setCountry($country);

        return $address;
    }

    /**
     * @return list<int>
     */
    private function getUids(iterable $result): array
    {
        $uids = [];
        foreach ($result as $address) {
            $uids[] = $address->getUid();
        }

        sort($uids);

        return $uids;
    }
}
