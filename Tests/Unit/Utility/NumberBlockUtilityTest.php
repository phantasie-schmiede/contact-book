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
use PSBits\ContactBook\Utility\NumberBlockUtility;

final class NumberBlockUtilityTest extends TestCase
{
    public function testStripsTheLeadingZero(): void
    {
        foreach (NumberBlockUtility::getGermanMobileServicesWithoutLeadingZero() as $numberBlock) {
            self::assertStringStartsNotWith('0', $numberBlock);
        }
    }

    public function testContainsTheMobileNumberBlocks(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanMobileServicesWithoutLeadingZero();

        foreach ([
            '15019',
            '1511',
            '160',
            '170',
            '171',
            '179',
        ] as $numberBlock) {
            self::assertContains($numberBlock, $numberBlocks);
        }
    }

    public function testContainsTheServiceNumberBlocks(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanMobileServicesWithoutLeadingZero();

        foreach ([
            '700',
            '800',
            '900',
        ] as $numberBlock) {
            self::assertContains($numberBlock, $numberBlocks);
        }
    }
}
