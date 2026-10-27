<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use DateTime;
use PSBits\ContactBook\Domain\Demand\EventDemand;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Repository\EventRepository;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PSBits\Qa\Testing\Support\PersistenceSupportTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class EventRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class EventRepositoryTest extends FunctionalTestCase
{
    use PersistenceSupportTrait;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/friendsoftypo3/content-blocks',
        'typo3conf/ext/psbits/contact-book',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/frontend-templates',
        'typo3conf/ext/psbits/frontend-users',
        'typo3conf/ext/psbits/view-helpers',
        'typo3conf/ext/wapplersystems/ws-scss',
    ];

    #[Test]
    public function eventsOfTheCurrentFrontendUsersContactBooksAreReturned(): void
    {
        $alice = $this->createFrontendUser('alice@example.org');
        $bob   = $this->createFrontendUser('bob@example.org');

        $bookOne        = $this->createContactBook('Book One', $alice);
        $bookTwo        = $this->createContactBook('Book Two', $bob);
        $contactOfAlice = $this->createContact('Max', 'Mustermann', $bookOne);
        $contactOfBob   = $this->createContact('Erika', 'Meier', $bookTwo);

        $eventOne = $this->createEvent(1990, 5, 15);
        $eventTwo = $this->createEvent(1991, 6, 16);
        $eventThree = $this->createEvent(1992, 7, 17);
        $contactOfAlice->addEvents($eventOne, $eventTwo);
        $contactOfBob->addEvents($eventThree);
        $this->persistAll(
            $alice,
            $bob,
            $bookOne,
            $bookTwo,
            $contactOfAlice,
            $contactOfBob,
            $eventOne,
            $eventTwo,
            $eventThree
        );

        $this->seedCurrentFrontendUser($alice);

        $repository = GeneralUtility::makeInstance(EventRepository::class);
        $uids       = $this->getUids($repository->findByDemand(new EventDemand()));

        self::assertContains($eventOne->getUid(), $uids);
        self::assertContains($eventTwo->getUid(), $uids);
        self::assertNotContains($eventThree->getUid(), $uids);
    }

    #[Test]
    public function eventsBeforeTheMinimumDateAreFilteredOut(): void
    {
        $alice = $this->createFrontendUser('alice@example.org');

        $book   = $this->createContactBook('Book One', $alice);
        $contact = $this->createContact('Max', 'Mustermann', $book);

        $oldEvent          = $this->createEvent(2020, 5, 15);
        $newEvent          = $this->createEvent(2026, 5, 15);
        $anniversaryEvent  = $this->createEvent(2021, 5, 15);
        $anniversaryEvent->setShowAnniversaries(true);
        $contact->addEvents($oldEvent, $newEvent, $anniversaryEvent);
        $this->persistAll($alice, $book, $contact, $oldEvent, $newEvent, $anniversaryEvent);

        $this->seedCurrentFrontendUser($alice);

        $repository = GeneralUtility::makeInstance(EventRepository::class);
        $demand     = new EventDemand();
        $demand->setMinDate(new DateTime('2025-01-01'));
        $uids       = $this->getUids($repository->findByDemand($demand));

        self::assertContains($newEvent->getUid(), $uids);
        self::assertContains($anniversaryEvent->getUid(), $uids);
        self::assertNotContains($oldEvent->getUid(), $uids);
    }

    private function createFrontendUser(string $email): FrontendUser
    {
        $user = new FrontendUser();
        $user->setUsername((string)str_replace('@example.org', '', $email))
            ->setEmail($email);

        return $user;
    }

    private function createContactBook(string $name, FrontendUser ...$owners): ContactBook
    {
        $contactBook = new ContactBook();
        $contactBook->setName($name);

        foreach ($owners as $owner) {
            $contactBook->addOwner($owner);
        }

        return $contactBook;
    }

    private function createContact(string $firstName, string $lastName, ContactBook $contactBook): Contact
    {
        $contact = new Contact();
        $contact->setFirstName($firstName)
            ->setLastName($lastName)
            ->setContactBook($contactBook);

        return $contact;
    }

    private function createEvent(int $year, int $month, int $day): Event
    {
        $event = new Event();
        $event->setYear($year)
            ->setMonth($month)
            ->setDay($day)
            ->setType(EventType::Birthday);

        return $event;
    }

    /**
     * @throws ReflectionException
     */
    private function seedCurrentFrontendUser(FrontendUser $user): void
    {
        $property = new ReflectionProperty(GlobalVariableService::class, 'cachedVariables');
        $property->setValue(null, [
            FrontendUserProvider::class => [
                'loginPage' => null,
                'sysFolder' => null,
                'user'      => $user,
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    private function getUids(iterable $result): array
    {
        $uids = [];
        foreach ($result as $event) {
            $uids[] = $event->getUid();
        }

        sort($uids);

        return $uids;
    }
}
