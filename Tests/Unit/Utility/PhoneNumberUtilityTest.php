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
use PSBits\ContactBook\Utility\PhoneNumberUtility;

final class PhoneNumberUtilityTest extends TestCase
{
    public function testParsesALandlineNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+493012345678');

        self::assertSame('49', $phoneNumber->getInternationalDiallingCode());
        self::assertSame('3012', $phoneNumber->getAreaCode());
        self::assertSame('345678', $phoneNumber->getConnectionIdentifier());
    }

    public function testIgnoresWhitespaces(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+49 30 12345678');

        self::assertSame('3012', $phoneNumber->getAreaCode());
        self::assertSame('345678', $phoneNumber->getConnectionIdentifier());
    }

    public function testParsesAMobileNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+491712345678');

        self::assertSame('171', $phoneNumber->getAreaCode());
        self::assertSame('2345678', $phoneNumber->getConnectionIdentifier());
    }

    public function testParsesAMobileNumberWithALongerNumberBlock(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+491501912345');

        self::assertSame('15019', $phoneNumber->getAreaCode());
        self::assertSame('12345', $phoneNumber->getConnectionIdentifier());
    }

    public function testParsesAServiceNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+497001234567');

        self::assertSame('700', $phoneNumber->getAreaCode());
        self::assertSame('1234567', $phoneNumber->getConnectionIdentifier());
    }
}
