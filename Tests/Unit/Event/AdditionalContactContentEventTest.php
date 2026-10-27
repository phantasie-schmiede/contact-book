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
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Event\AdditionalContactContentEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class AdditionalContactContentEventTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Event
 */
class AdditionalContactContentEventTest extends UnitTestCase
{
    #[Test]
    public function theContactIsSetFromTheParameters(): void
    {
        $contact = new Contact();
        $event = new AdditionalContactContentEvent();

        $event->setParameters([
            'contact' => $contact,
        ]);

        self::assertSame($contact, $event->getContact());
    }

    #[Test]
    public function aMissingContactThrowsAnException(): void
    {
        $event = new AdditionalContactContentEvent();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid contact parameter!');

        $event->setParameters([]);
    }

    #[Test]
    public function anInvalidContactThrowsAnException(): void
    {
        $event = new AdditionalContactContentEvent();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid contact parameter!');

        $event->setParameters([
            'contact' => 'not-a-contact',
        ]);
    }

    #[Test]
    public function theContentCanBeAppended(): void
    {
        $event = new AdditionalContactContentEvent();

        $event->addContent('Hello');
        $event->addContent(' world');

        self::assertSame('Hello world', $event->getContent());
    }
}
