<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service\Enum;

use PSBits\ContactBook\Service\Enum\VCardVersion;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VCardVersionTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service\Enum
 */
class VCardVersionTest extends UnitTestCase
{
    #[Test]
    public function appleUsesTheVersion3Number(): void
    {
        self::assertSame('v3.0', VCardVersion::Apple->getVersionNumber());
    }

    #[Test]
    public function theRegularVersionsUseTheirOwnNumber(): void
    {
        self::assertSame('v2.1', VCardVersion::V2_1->getVersionNumber());
        self::assertSame('v3.0', VCardVersion::V3_0->getVersionNumber());
        self::assertSame('v4.0', VCardVersion::V4_0->getVersionNumber());
    }
}
