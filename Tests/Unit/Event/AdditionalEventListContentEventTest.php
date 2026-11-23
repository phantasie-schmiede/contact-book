<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Event;

use InvalidArgumentException;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Event\AdditionalEventListContentEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class AdditionalEventListContentEventTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Event
 */
class AdditionalEventListContentEventTest extends UnitTestCase
{
    #[Test]
    public function theEventIsSetFromTheParameters(): void
    {
        $event = new Event();
        $listEvent = new AdditionalEventListContentEvent();

        $listEvent->setParameters([
            'event' => $event,
        ]);

        self::assertSame($event, $listEvent->getEvent());
    }

    #[Test]
    public function aMissingEventThrowsAnException(): void
    {
        $listEvent = new AdditionalEventListContentEvent();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid event parameter!');

        $listEvent->setParameters([]);
    }

    #[Test]
    public function anInvalidEventThrowsAnException(): void
    {
        $listEvent = new AdditionalEventListContentEvent();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid event parameter!');

        $listEvent->setParameters([
            'event' => 'not-an-event',
        ]);
    }
}
