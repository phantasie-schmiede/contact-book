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
use PSBits\ContactBook\Utility\CountyUtility;

final class CountyUtilityTest extends TestCase
{
    public function testReturnsTheGermanStates(): void
    {
        $counties = CountyUtility::getGermanCounties();

        self::assertCount(16, $counties);

        foreach ([
            'Baden-Württemberg',
            'Bayern',
            'Mecklenburg-Vorpommern',
            'Nordrhein-Westfalen',
            'Thüringen',
        ] as $county) {
            self::assertContains($county, $counties);
        }
    }
}
