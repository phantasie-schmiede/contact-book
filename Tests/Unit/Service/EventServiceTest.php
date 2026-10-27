<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Service\EventService;
use PSBits\ContactBook\Tests\Unit\Fixtures\ArrayQueryResult;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EventServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class EventServiceTest extends UnitTestCase
{
    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theEventUidsOfAllContactBooksAreReturned(): void
    {
        $contactBookOne = new ContactBook();
        $contactBookTwo = new ContactBook();

        $contact = new Contact();
        $eventOne = $this->createEvent(1);
        $eventTwo = $this->createEvent(2);
        $eventThree = $this->createEvent(3);
        $contact->addEvents($eventOne, $eventTwo);
        $contactBookOne->getContacts()
            ->attach($contact);

        $otherContact = new Contact();
        $otherContact->addEvents($eventTwo, $eventThree);
        $contactBookTwo->getContacts()
            ->attach($otherContact);

        $contactBookRepository = $this->createMock(ContactBookRepository::class);
        $contactBookRepository->method('findByOwner')
            ->willReturn($this->createQueryResultMock($contactBookOne, $contactBookTwo));

        $eventService = new EventService($contactBookRepository);

        self::assertSame(
            [1, 2, 3],
            array_values($eventService->getAllEventUidsForCurrentFrontendUser())
        );
    }

    #[Test]
    public function noEventUidsAreReturnedWithoutContactBooks(): void
    {
        $contactBookRepository = $this->createMock(ContactBookRepository::class);
        $contactBookRepository->method('findByOwner')
            ->willReturn($this->createQueryResultMock());

        $eventService = new EventService($contactBookRepository);

        self::assertSame([], $eventService->getAllEventUidsForCurrentFrontendUser());
    }

    private function createQueryResultMock(ContactBook ...$contactBooks): QueryResultInterface
    {
        return new ArrayQueryResult($contactBooks);
    }

    /**
     * @throws ReflectionException
     */
    private function createEvent(int $uid): Event
    {
        $event = new Event();
        $event->setMonth(5)
            ->setDay(10 + $uid)
            ->setYear(1990);

        $property = new ReflectionProperty(AbstractDomainObject::class, 'uid');
        $property->setValue($event, $uid);

        return $event;
    }
}
