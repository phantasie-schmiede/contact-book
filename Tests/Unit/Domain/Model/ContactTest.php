<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Domain\Model\Website;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class ContactTest extends UnitTestCase
{
    #[Test]
    public function theListNameIsTheCompanyNameForCompanies(): void
    {
        $contact = $this->createPerson();
        $contact->setCompanyName('Acme Corp')
            ->setType(ContactType::Company);

        self::assertSame('Acme Corp', $contact->getListName());
    }

    #[Test]
    public function theListNameCombinesTheFamilyNameAndTheFirstName(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann');

        self::assertSame('Mustermann, Max', $contact->getListName());
    }

    #[Test]
    public function theListNameIsOnlyTheFamilyNameWithoutAFirstName(): void
    {
        $contact = $this->createPerson();
        $contact->setLastName('Mustermann');

        self::assertSame('Mustermann', $contact->getListName());
    }

    #[Test]
    public function theListNameIsTheFirstNameWithoutAFamilyName(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setMiddleNames('The Brave');

        self::assertSame('Max The Brave', $contact->getListName());
    }

    #[Test]
    public function theShortNameCombinesTheFirstAndTheLastName(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann');

        self::assertSame('Max Mustermann', $contact->getShortName());
    }

    #[Test]
    public function theShortNameIsTheCompanyNameForCompanies(): void
    {
        $contact = $this->createPerson();
        $contact->setCompanyName('Acme Corp')
            ->setType(ContactType::Company);

        self::assertSame('Acme Corp', $contact->getShortName());
    }

    #[Test]
    public function theFullNameIncludesTheTitleAndTheMiddleNames(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setMiddleNames('The Brave')
            ->setTitle('Dr.');

        self::assertSame('Dr. Max The Brave Mustermann', $contact->getFullName());
    }

    #[Test]
    public function theFirstNameIncludesTheMiddleNames(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setMiddleNames('The Brave');

        self::assertSame('Max The Brave', $contact->getFirstNames());
    }

    #[Test]
    public function theGenitiveFormIsDerivedFromTheFirstName(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Friedrich');

        self::assertSame('Friedrichs', $contact->getGenitiveForm());
    }

    #[Test]
    public function theGenitiveFormIsDerivedFromTheCompanyNameForCompanies(): void
    {
        $contact = $this->createPerson();
        $contact->setCompanyName('Acme')
            ->setType(ContactType::Company);

        self::assertSame('Acmes', $contact->getGenitiveForm());
    }

    #[Test]
    public function thePersonIsAliveWithoutDeathEvents(): void
    {
        self::assertTrue($this->createPerson()->isAlive());
    }

    #[Test]
    public function thePersonIsDeadWithDeathEvents(): void
    {
        $contact = $this->createPerson();
        $contact->addEvents($this->createEvent(EventType::Death));

        self::assertFalse($contact->isAlive());
    }

    #[Test]
    public function theNameDecorationsMarkDeadPersons(): void
    {
        $contact = $this->createPerson();
        $contact->addEvents($this->createEvent(EventType::Death));

        self::assertSame('† ', $contact->getNameDecorations());
    }

    #[Test]
    public function theNameDecorationsAreEmptyForAlivePersons(): void
    {
        self::assertSame('', $this->createPerson()->getNameDecorations());
    }

    #[Test]
    public function theExportRelevantInformationIsFalseForDeadPersons(): void
    {
        $contact = $this->createPerson();
        $contact->addEvents($this->createEvent(EventType::Death));
        $this->addEMail($contact);

        self::assertFalse($contact->hasExportRelevantInformation());
    }

    #[Test]
    public function theExportRelevantInformationIsFalseForEmptyPersons(): void
    {
        self::assertFalse($this->createPerson()->hasExportRelevantInformation());
    }

    #[Test]
    public function theExportRelevantInformationIsTrueForPersonsWithContactData(): void
    {
        $contact = $this->createPerson();
        $this->addAddress($contact);

        self::assertTrue($contact->hasExportRelevantInformation());

        $contact = $this->createPerson();
        $this->addEMail($contact);

        self::assertTrue($contact->hasExportRelevantInformation());

        $contact = $this->createPerson();
        $this->addPhoneNumber($contact);

        self::assertTrue($contact->hasExportRelevantInformation());

        $contact = $this->createPerson();
        $this->addWebsite($contact);

        self::assertTrue($contact->hasExportRelevantInformation());
    }

    #[Test]
    public function theExportRelevantInformationIsTrueForPersonsWithBirthEvents(): void
    {
        $contact = $this->createPerson();
        $contact->addEvents($this->createEvent(EventType::Birthday, 1990));

        self::assertTrue($contact->hasExportRelevantInformation());
    }

    #[Test]
    public function theBirthEventIsReturned(): void
    {
        $contact = $this->createPerson();
        $birthEvent = $this->createEvent(EventType::Birthday, 1990);
        $contact->addEvents($this->createEvent(EventType::Wedding, 2020), $birthEvent);

        self::assertSame($birthEvent, $contact->getBirthEvent());
    }

    #[Test]
    public function theBirthEventIsNullWithoutBirthdayEvents(): void
    {
        $contact = $this->createPerson();
        $contact->addEvents($this->createEvent(EventType::Wedding, 2020));

        self::assertNull($contact->getBirthEvent());
    }

    #[Test]
    public function theSearchTermsContainTheNameAndTheContactData(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setNickname('Maxi');
        $contact->addEvents($this->createEvent(EventType::Birthday, 1990));
        $this->addAddress($contact);
        $this->addEMail($contact);
        $this->addPhoneNumber($contact);
        $this->addWebsite($contact);

        self::assertSame(
            'type:person Max Mustermann Maxi 1990 Hauptstraße 12 10115 Berlin max@example.com https://example.com phoneType:mobile +4930123456',
            $contact->getSearchTerms()
        );
    }

    #[Test]
    public function theSearchTermsContainTheCompanyNameForCompanies(): void
    {
        $contact = $this->createPerson();
        $contact->setCompanyName('Acme Corp')
            ->setType(ContactType::Company);
        $this->addEMail($contact);

        self::assertSame('type:company Acme Corp max@example.com', $contact->getSearchTerms());
    }

    #[Test]
    public function theSearchTermsContainTheNotesLineByLine(): void
    {
        $contact = $this->createPerson();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setNotes("Note one\nNote two");

        self::assertSame('type:person Max Mustermann Note one Note two', $contact->getSearchTerms());
    }

    #[Test]
    public function theSocialUrlsAreGenerated(): void
    {
        $contact = $this->createPerson();
        $contact->setFacebookId('max')
            ->setInstagramId('max')
            ->setLinkedInId('max-mustermann');

        self::assertSame('https://www.facebook.com/max', $contact->getFacebookUrl());
        self::assertSame('https://www.instagram.com/max', $contact->getInstagramUrl());
        self::assertSame('https://www.linkedin.com/in/max-mustermann', $contact->getLinkedInUrl());
    }

    #[Test]
    public function theSocialUrlsAreEmptyWithoutIds(): void
    {
        $contact = $this->createPerson();

        self::assertSame('', $contact->getFacebookUrl());
        self::assertSame('', $contact->getInstagramUrl());
        self::assertSame('', $contact->getLinkedInUrl());
    }

    #[Test]
    public function theEventsAreSortedInDescendingOrder(): void
    {
        $contact = $this->createPerson();
        $birthEvent = $this->createEvent(EventType::Birthday, 1990);
        $contact->addEvents($birthEvent);

        $noYearEvent = $this->createEvent(EventType::Other, null);
        $noYearEvent->setMonth(6)
            ->setDay(2);
        $weddingEvent = $this->createEvent(EventType::Wedding, 2020);
        $contact->addEvents($noYearEvent, $weddingEvent);

        self::assertSame([$weddingEvent, $noYearEvent, $birthEvent], $contact->getEventsInDescendingOrder());
        self::assertNull($noYearEvent->getYear());
    }

    /**
     * Events without a year are sorted after the events of the birth year.
     */
    #[Test]
    public function theEventsAreSortedAfterTheBirthYearWithoutYears(): void
    {
        $contact = $this->createPerson();
        $birthEvent = $this->createEvent(EventType::Birthday, 1990);
        $contact->addEvents($birthEvent);

        $noYearEvent = $this->createEvent(EventType::Other, null);
        $noYearEvent->setMonth(1)
            ->setDay(1);
        $contact->addEvents($noYearEvent);

        self::assertSame([$noYearEvent, $birthEvent], $contact->getEventsInDescendingOrder());
        self::assertNull($noYearEvent->getYear());
    }

    private function createPerson(): Contact
    {
        $contact = new Contact();
        $contact->setType(ContactType::Person);

        return $contact;
    }

    private function createEvent(EventType $type, ?int $year = null): Event
    {
        $event = new Event();
        $event->setType($type)
            ->setYear($year);

        return $event;
    }

    private function addAddress(Contact $contact): void
    {
        $address = new Address();
        $address->setCity('Berlin')
            ->setHouseNumber('12')
            ->setStreet('Hauptstraße')
            ->setZipCode('10115');
        $contact->addAddresses($address);
    }

    private function addEMail(Contact $contact): void
    {
        $eMail = new EMail();
        $eMail->setEMailAddress('max@example.com');
        $contact->addEMails($eMail);
    }

    private function addPhoneNumber(Contact $contact): void
    {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setAreaCode('30')
            ->setConnectionIdentifier('123456')
            ->setInternationalDiallingCode('49')
            ->setPhoneNumberType(PhoneNumberType::Mobile);
        $contact->addPhoneNumbers($phoneNumber);
    }

    private function addWebsite(Contact $contact): void
    {
        $website = new Website();
        $website->setUrl('https://example.com');
        $contact->addWebsites($website);
    }
}
