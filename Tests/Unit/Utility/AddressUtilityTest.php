<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use PSBits\ContactBook\Utility\AddressUtility;

final class AddressUtilityTest extends TestCase
{
    public function testSplitsStreetAndHouseNumber(): void
    {
        self::assertSame(
            [
                'street'      => 'Musterstraße',
                'houseNumber' => '12',
            ],
            AddressUtility::splitStreetAndHouseNumber('Musterstraße 12')
        );
    }

    public function testKeepsTheStreetIntactWhenItContainsSpaces(): void
    {
        self::assertSame(
            [
                'street'      => 'Berliner Straße',
                'houseNumber' => '12',
            ],
            AddressUtility::splitStreetAndHouseNumber('Berliner Straße 12')
        );
    }

    public function testKeepsNonNumericSuffixesInTheHouseNumber(): void
    {
        self::assertSame(
            [
                'street'      => 'Hauptstraße',
                'houseNumber' => '12a',
            ],
            AddressUtility::splitStreetAndHouseNumber('Hauptstraße 12a')
        );
    }

    public function testLeavesTheHouseNumberEmptyWithoutANumber(): void
    {
        self::assertSame(
            [
                'street'      => 'Musterstraße',
                'houseNumber' => '',
            ],
            AddressUtility::splitStreetAndHouseNumber('Musterstraße')
        );
    }

    public function testTreatsNonNumericTrailingWordsAsPartOfTheStreet(): void
    {
        self::assertSame(
            [
                'street'      => 'Gartenweg 3 b',
                'houseNumber' => '',
            ],
            AddressUtility::splitStreetAndHouseNumber('Gartenweg 3 b')
        );
    }
}
