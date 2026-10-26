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
    public function testContainsAllNumberBlocks(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

        foreach ([
            '171',
            '30',
            '800',
        ] as $numberBlock) {
            self::assertContains($numberBlock, $numberBlocks);
        }
    }

    public function testContainsTheLandlineAreaCodes(): void
    {
        $areaCodes = NumberBlockUtility::getGermanLandlineAreaCodesWithoutLeadingZero();

        foreach ([
            '30',
            '40',
            '69',
            '89',
            '201',
            '2161',
            '2129',
            '34779',
        ] as $areaCode) {
            self::assertContains($areaCode, $areaCodes);
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

    public function testNumberBlocksAreSortedByLengthDescending(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();
        $lengths      = array_map('strlen', $numberBlocks);
        $sorted       = $lengths;

        rsort($sorted);

        self::assertSame($sorted, $lengths);
    }

    public function testNumberBlocksContainNoDuplicates(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

        self::assertCount(count($numberBlocks), array_unique($numberBlocks));
    }

    public function testStripsTheLeadingZero(): void
    {
        foreach ([
            NumberBlockUtility::getGermanLandlineAreaCodesWithoutLeadingZero(),
            NumberBlockUtility::getGermanMobileServicesWithoutLeadingZero(),
            NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero(),
        ] as $numberBlocks) {
            foreach ($numberBlocks as $numberBlock) {
                self::assertStringStartsNotWith('0', $numberBlock);
            }
        }
    }
}
