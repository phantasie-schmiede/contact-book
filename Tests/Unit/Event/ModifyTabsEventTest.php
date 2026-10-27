<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Event;

use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Event\ModifyTabsEvent;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ModifyTabsEventTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Event
 */
class ModifyTabsEventTest extends UnitTestCase
{
    #[Test]
    public function theTabsDefaultToAnEmptyArray(): void
    {
        $event = new ModifyTabsEvent(new Contact(), $this->createRequest());

        self::assertSame([], $event->getTabs());
    }

    #[Test]
    public function aTabIsAppendedWithoutAPosition(): void
    {
        $event = new ModifyTabsEvent(new Contact(), $this->createRequest());

        $event->addTab('<p>Content</p>', 'details', 'Details');

        self::assertSame([
            [
                'content' => '<p>Content</p>',
                'id'      => 'details',
                'label'   => 'Details',
            ],
        ], $event->getTabs());
    }

    #[Test]
    public function aTabIsInsertedAtThePosition(): void
    {
        $event = new ModifyTabsEvent(new Contact(), $this->createRequest());
        $event->addTab('<p>Content A</p>', 'a', 'A')
            ->addTab('<p>Content B</p>', 'b', 'B');

        $event->addTab('<p>Content X</p>', 'x', 'X', 1);

        self::assertSame([
            'a',
            'x',
            'b',
        ], array_column($event->getTabs(), 'id'));
    }

    #[Test]
    public function aTabIsAppendedWithAnOutOfRangePosition(): void
    {
        $event = new ModifyTabsEvent(new Contact(), $this->createRequest());
        $event->addTab('<p>Content A</p>', 'a', 'A');

        $event->addTab('<p>Content X</p>', 'x', 'X', 5);

        self::assertSame([
            'a',
            'x',
        ], array_column($event->getTabs(), 'id'));
    }

    #[Test]
    public function theContactAndTheRequestAreReturned(): void
    {
        $contact = new Contact();
        $request = $this->createRequest();
        $event = new ModifyTabsEvent($contact, $request);

        self::assertSame($contact, $event->getContact());
        self::assertSame($request, $event->getRequest());
    }

    #[Test]
    public function theTabsCanBeReplaced(): void
    {
        $event = new ModifyTabsEvent(new Contact(), $this->createRequest());

        $event->setTabs([
            [
                'content' => '<p>Content</p>',
                'id'      => 'details',
                'label'   => 'Details',
            ],
        ]);

        self::assertCount(1, $event->getTabs());
    }

    private function createRequest(): ServerRequestInterface
    {
        return $this->createMock(ServerRequestInterface::class);
    }
}
