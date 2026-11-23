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
use PSBits\ContactBook\Utility\NumberBlockUtility;

final class NumberBlockUtilityTest extends TestCase
{
    /**
     * @throws JsonException
     */
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

    /**
     * @throws JsonException
     */
    public function testContainsTheLandlineAreaCodes(): void
    {
        $areaCodes = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

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

    /**
     * @throws JsonException
     */
    public function testContainsTheMobileNumberBlocks(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

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

    /**
     * @throws JsonException
     */
    public function testContainsTheServiceNumberBlocks(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

        foreach ([
            '700',
            '800',
            '900',
        ] as $numberBlock) {
            self::assertContains($numberBlock, $numberBlocks);
        }
    }

    /**
     * @throws JsonException
     */
    public function testNumberBlocksAreSortedByLengthDescending(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();
        $lengths      = array_map('strlen', $numberBlocks);
        $sorted       = $lengths;

        rsort($sorted);

        self::assertSame($sorted, $lengths);
    }

    /**
     * @throws JsonException
     */
    public function testNumberBlocksContainNoDuplicates(): void
    {
        $numberBlocks = NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero();

        self::assertCount(count($numberBlocks), array_unique($numberBlocks));
    }

    /**
     * @throws JsonException
     */
    public function testStripsTheLeadingZero(): void
    {
        foreach (NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero() as $numberBlock) {
            self::assertStringStartsNotWith('0', $numberBlock);
        }
    }
}
