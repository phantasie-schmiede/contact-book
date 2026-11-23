<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use JsonException;
use PHPUnit\Framework\TestCase;
use PSBits\ContactBook\Utility\PhoneNumberUtility;

final class PhoneNumberUtilityTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testFallsBackToTheNextFourDigitsForUnknownAreaCodes(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+499999123456');

        self::assertSame('49', $phoneNumber->getInternationalDiallingCode());
        self::assertSame('9999', $phoneNumber->getAreaCode());
        self::assertSame('123456', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testIgnoresWhitespaces(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+49 30 12345678');

        self::assertSame('30', $phoneNumber->getAreaCode());
        self::assertSame('12345678', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesALandlineNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+493012345678');

        self::assertSame('49', $phoneNumber->getInternationalDiallingCode());
        self::assertSame('30', $phoneNumber->getAreaCode());
        self::assertSame('12345678', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesALandlineNumberWithAThreeDigitAreaCode(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+492011234567');

        self::assertSame('201', $phoneNumber->getAreaCode());
        self::assertSame('1234567', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesALandlineNumberWithAFourDigitAreaCode(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+49216112345');

        self::assertSame('2161', $phoneNumber->getAreaCode());
        self::assertSame('12345', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesALandlineNumberWithAFiveDigitAreaCode(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+493477912345');

        self::assertSame('34779', $phoneNumber->getAreaCode());
        self::assertSame('12345', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesAMobileNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+491712345678');

        self::assertSame('171', $phoneNumber->getAreaCode());
        self::assertSame('2345678', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesAMobileNumberWithALongerNumberBlock(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+491501912345');

        self::assertSame('15019', $phoneNumber->getAreaCode());
        self::assertSame('12345', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testParsesAServiceNumber(): void
    {
        $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString('+497001234567');

        self::assertSame('700', $phoneNumber->getAreaCode());
        self::assertSame('1234567', $phoneNumber->getConnectionIdentifier());
    }

    /**
     * @throws JsonException
     */
    public function testPrefersTheLongestMatchingAreaCode(): void
    {
        $shorterAreaCode = PhoneNumberUtility::getPhoneNumberFromString('+49212123456');
        $longerAreaCode  = PhoneNumberUtility::getPhoneNumberFromString('+49212912345');

        self::assertSame('212', $shorterAreaCode->getAreaCode());
        self::assertSame('123456', $shorterAreaCode->getConnectionIdentifier());
        self::assertSame('2129', $longerAreaCode->getAreaCode());
        self::assertSame('12345', $longerAreaCode->getConnectionIdentifier());
    }
}
