<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Demand;

use DateTime;
use PSBits\ContactBook\Domain\Demand\EventDemand;
use PSBits\ContactBook\Domain\Enum\EventType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EventDemandTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Demand
 */
class EventDemandTest extends UnitTestCase
{
    #[Test]
    public function theDefaultsAreEmpty(): void
    {
        $demand = new EventDemand();

        self::assertNull($demand->getMinDate());
        self::assertNull($demand->getShowAnniversaries());
        self::assertNull($demand->getShowInCalendar());
        self::assertSame([], $demand->getTypes());
    }

    #[Test]
    public function theMinDateCanBeSet(): void
    {
        $demand = new EventDemand();
        $date   = new DateTime('2026-05-01');

        self::assertSame($date, $demand->setMinDate($date)
            ->getMinDate());
    }

    #[Test]
    public function theDisplayFlagsCanBeSet(): void
    {
        $demand = new EventDemand();

        self::assertTrue($demand->setShowAnniversaries(true)
            ->setShowInCalendar(false)
            ->getShowAnniversaries());
        self::assertFalse($demand->getShowInCalendar());
    }

    #[Test]
    public function typesCanBeAdded(): void
    {
        $demand = new EventDemand();

        $demand->addType(EventType::Birthday)
            ->addType(EventType::Wedding);

        self::assertSame([EventType::Birthday, EventType::Wedding], $demand->getTypes());
    }
}
