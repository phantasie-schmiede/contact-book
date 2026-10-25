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
use PSBits\ContactBook\Utility\NameUtility;

final class NameUtilityTest extends TestCase
{
    public function testReplacesUmlautsForSorting(): void
    {
        self::assertSame('aerger', NameUtility::getCompareString('Ärger'));
        self::assertSame('mueller', NameUtility::getCompareString('Müller'));
        self::assertSame('groesse', NameUtility::getCompareString('Größe'));
        self::assertSame('schoen', NameUtility::getCompareString('Schön'));
    }

    public function testLowercasesNamesWithoutUmlauts(): void
    {
        self::assertSame('smith', NameUtility::getCompareString('Smith'));
    }

    public function testAppendsSForRegularEndings(): void
    {
        self::assertSame('Müllers', NameUtility::getGenitiveForm('Müller'));
        self::assertSame('Beethovens', NameUtility::getGenitiveForm('Beethoven'));
    }

    public function testAppendsAnApostropheForSibilantEndings(): void
    {
        self::assertSame("Fox'", NameUtility::getGenitiveForm('Fox'));
        self::assertSame("Jones'", NameUtility::getGenitiveForm('Jones'));
        self::assertSame("Menz'", NameUtility::getGenitiveForm('Menz'));
    }
}
