<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\ContactBook\Service\VCardService;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\FrontendTemplates\Service\CountryNamesService;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VCardServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class VCardServiceTest extends UnitTestCase
{
    #[Test]
    public function aPersonVCardIsGeneratedForVersion21(): void
    {
        $service = $this->createService();

        $lines = $this->getLines(
            $service->getVCard($this->createContact(), VCardVersion::V2_1)
        );

        self::assertSame(
            [
                'BEGIN:VCARD',
                'VERSION:v2.1',
                'BDAY:1990-05-15',
                'FN:Max Mustermann',
                'N:Mustermann;Max;Philipp;Dr.;',
                'ADR;home:;;Hauptstraße 12;Berlin;Berlin;10115;Germany',
                'EMAIL;home:max@example.com',
                'TEL;home;cell:+49301234567',
                'UID:abc-123',
                'URL;home:https://example.com',
                'END:VCARD',
            ],
            $lines
        );
    }

    #[Test]
    public function aPersonVCardIsGeneratedForVersion30(): void
    {
        $service = $this->createService();

        $lines = $this->getLines(
            $service->getVCard($this->createContact(), VCardVersion::V3_0)
        );

        self::assertSame(
            [
                'BEGIN:VCARD',
                'VERSION:v3.0',
                'BDAY:1990-05-15',
                'FN:Max Mustermann',
                'N:Mustermann;Max;Philipp;Dr.;',
                'NICKNAME:Maxi',
                'ADR;TYPE=home:;;Hauptstraße 12;Berlin;Berlin;10115;Germany',
                'EMAIL;TYPE=home:max@example.com',
                'TEL;TYPE=home,cell:+49301234567',
                'UID:abc-123',
                'URL;TYPE=home:https://example.com',
                'END:VCARD',
            ],
            $lines
        );
    }

    #[Test]
    public function aPersonVCardIsGeneratedForVersion40(): void
    {
        $service = $this->createService();

        $lines = $this->getLines(
            $service->getVCard($this->createContact(), VCardVersion::V4_0)
        );

        self::assertSame(
            [
                'BEGIN:VCARD',
                'VERSION:v4.0',
                'BDAY:1990-05-15',
                'FN:Max Mustermann',
                'N:Mustermann;Max;Philipp;Dr.;',
                'NICKNAME:Maxi',
                'KIND:individual',
                'ADR;TYPE=home:;;Hauptstraße 12;Berlin;Berlin;10115;Germany',
                'EMAIL;TYPE=home:max@example.com',
                'TEL;VALUE=uri;TYPE=home,cell:tel:+49301234567',
                'UID:urn:uuid:abc-123',
                'URL;TYPE=home:https://example.com',
                'END:VCARD',
            ],
            $lines
        );
    }

    #[Test]
    public function anAppleVCardOmitsMiddleNamesAndUsesTheAbuid(): void
    {
        $service = $this->createService();

        $lines = $this->getLines(
            $service->getVCard($this->createContact(), VCardVersion::Apple)
        );

        self::assertSame(
            [
                'BEGIN:VCARD',
                'VERSION:v3.0',
                'BDAY:1990-05-15',
                'FN:Max Mustermann',
                'N:Mustermann;Max;;Dr.;',
                'NICKNAME:Maxi',
                'ADR;TYPE=home:;;Hauptstraße 12;Berlin;Berlin;10115;Germany',
                'EMAIL;TYPE=home:max@example.com',
                'TEL;TYPE=cell:+49301234567',
                'X-ABUID:abc-123:ABPerson',
                'URL;TYPE=home:https://example.com',
                'END:VCARD',
            ],
            $lines
        );
    }

    #[Test]
    public function aCompanyVCardIsGeneratedForVersion40(): void
    {
        $service = $this->createService();

        $contact = $this->createContact();
        $contact->setType(ContactType::Company)
            ->setCompanyName('Acme GmbH');

        $lines = $this->getLines(
            $service->getVCard($contact, VCardVersion::V4_0)
        );

        self::assertSame(
            [
                'BEGIN:VCARD',
                'VERSION:v4.0',
                'FN:Acme GmbH',
                'KIND:organization',
                'ADR;TYPE=home:;;Hauptstraße 12;Berlin;Berlin;10115;Germany',
                'EMAIL;TYPE=home:max@example.com',
                'TEL;VALUE=uri;TYPE=home,cell:tel:+49301234567',
                'UID:urn:uuid:abc-123',
                'URL;TYPE=home:https://example.com',
                'END:VCARD',
            ],
            $lines
        );
    }

    #[Test]
    public function anIncompleteBirthdayIsEmittedWithTheYear1604HackForVersion30(): void
    {
        $service = $this->createService();

        $contact = $this->createContact();
        $contact->getEvents()
            ->toArray()[0]->setYear(null);

        $lines = $this->getLines(
            $service->getVCard($contact, VCardVersion::V3_0)
        );

        self::assertContains('BDAY;X-APPLE-OMIT-YEAR=1604:1604-05-15', $lines);
    }

    #[Test]
    public function anIncompleteBirthdayIsEmittedWithoutAYearForVersion40(): void
    {
        $service = $this->createService();

        $contact = $this->createContact();
        $contact->getEvents()
            ->toArray()[0]->setYear(null);

        $lines = $this->getLines(
            $service->getVCard($contact, VCardVersion::V4_0)
        );

        self::assertContains('BDAY:--0515', $lines);
    }

    #[Test]
    public function aRuntimeExceptionIsThrownWithoutAUuid(): void
    {
        $service = $this->createService();

        $contact = $this->createContact();
        $this->setUuid($contact, '');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Contact [uid:] has no UUID!');

        $service->getVCard($contact, VCardVersion::V2_1);
    }

    #[Test]
    public function longLinesAreFolded(): void
    {
        $service = $this->createService();

        $contact = $this->createContact();
        $contact->getAddresses()
            ->toArray()[0]->setStreet(str_repeat('a', 80))
            ->setHouseNumber('');

        $lines = $this->getLines(
            $service->getVCard($contact, VCardVersion::V2_1)
        );

        self::assertContains('ADR;home:;;' . str_repeat('a', 64), $lines);
        self::assertContains(
            ' ' . str_repeat('a', 16) . ';Berlin;Berlin;10115;Germany',
            $lines
        );
    }

    /**
     * @param string[] $lines
     *
     * @return string[]
     */
    private function getLines(string $vCard): array
    {
        return explode("\r\n", rtrim($vCard, "\r\n"));
    }

    private function createService(): VCardService
    {
        $countryNamesService = $this->createMock(CountryNamesService::class);
        $countryNamesService->method('getFromCode')
            ->with('DE')
            ->willReturn('Germany');

        return new VCardService($countryNamesService);
    }

    private function createContact(): Contact
    {
        $contact = new Contact();
        $contact->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setMiddleNames('Philipp')
            ->setTitle('Dr.')
            ->setNickname('Maxi');

        $address = new Address();
        $address->setStreet('Hauptstraße')
            ->setHouseNumber('12')
            ->setCity('Berlin')
            ->setCounty('Berlin')
            ->setZipCode('10115')
            ->setCountry('DE');
        $contact->addAddresses($address);

        $eMail = new EMail();
        $eMail->setEMailAddress('max@example.com');
        $contact->addEMails($eMail);

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setInternationalDiallingCode('49')
            ->setAreaCode('30')
            ->setConnectionIdentifier('1234567')
            ->setPhoneNumberType(PhoneNumberType::Mobile);
        $contact->addPhoneNumbers($phoneNumber);

        $website = new Website();
        $website->setUrl('https://example.com');
        $contact->addWebsites($website);

        $event = new Event();
        $event->setDay(15)
            ->setMonth(5)
            ->setYear(1990)
            ->setType(EventType::Birthday);
        $contact->addEvents($event);

        $this->setUuid($contact, 'abc-123');

        return $contact;
    }

    /**
     * @throws ReflectionException
     */
    private function setUuid(Contact $contact, string $uuid): void
    {
        $property = new ReflectionProperty(Contact::class, 'uuid');
        $property->setValue($contact, $uuid);
    }
}
