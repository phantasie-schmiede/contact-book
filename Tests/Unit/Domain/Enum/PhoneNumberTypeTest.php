<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Enum;

use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PhoneNumberTypeTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Enum
 */
class PhoneNumberTypeTest extends UnitTestCase
{
    #[Test]
    public function faxHasThePrinterIcon(): void
    {
        self::assertSame('printer', PhoneNumberType::Fax->getIconIdentifier());
    }

    #[Test]
    public function landlineHasTheHouseIcon(): void
    {
        self::assertSame('house', PhoneNumberType::Landline->getIconIdentifier());
    }

    #[Test]
    public function mobileHasThePhoneIcon(): void
    {
        self::assertSame('phone', PhoneNumberType::Mobile->getIconIdentifier());
    }

    #[Test]
    public function otherHasTheQuestionCircleIcon(): void
    {
        self::assertSame('question-circle', PhoneNumberType::Other->getIconIdentifier());
    }

    #[Test]
    public function theTypesAreListedAsArray(): void
    {
        self::assertSame([
            'fax'      => 'Fax',
            'landline' => 'Landline',
            'mobile'   => 'Mobile',
            'other'    => 'Other',
        ], PhoneNumberType::toArray());
    }
}
