<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Model\Address;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class AddressTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class AddressTest extends UnitTestCase
{
    #[Test]
    public function theCompactValueCombinesTheAddressParts(): void
    {
        $address = new Address();
        $address->setCity('Berlin')
            ->setCountry('Germany')
            ->setCounty('Brandenburg')
            ->setHouseNumber('12a')
            ->setStreet('Hauptstraße')
            ->setZipCode('10115');

        self::assertSame('Hauptstraße 12a, 10115 Berlin', $address->getCompactValue());
    }

    #[Test]
    public function theGoogleMapsLinkIsGenerated(): void
    {
        $address = new Address();
        $address->setCity('Berlin')
            ->setHouseNumber('12')
            ->setStreet('Hauptstraße')
            ->setZipCode('10115');

        self::assertSame(
            'https://www.google.com/maps/dir/?api=1&destination=' . urlencode('Hauptstraße 12, 10115 Berlin'),
            $address->getLink()
        );
    }

    #[Test]
    public function theStreetAndHouseNumberAreCombined(): void
    {
        $address = new Address();
        $address->setHouseNumber('12a')
            ->setStreet('Hauptstraße');

        self::assertSame('Hauptstraße 12a', $address->getStreetAndHouseNumber());
    }

    #[Test]
    public function theStreetIsReturnedWithoutAHouseNumber(): void
    {
        $address = new Address();
        $address->setStreet('Hauptstraße');

        self::assertSame('Hauptstraße', $address->getStreetAndHouseNumber());
    }

    #[Test]
    public function theTypeDefaultsToPrivate(): void
    {
        self::assertSame(GeneralType::Private, (new Address())->getType());
    }
}
