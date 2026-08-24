<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Service;

use DateTime;
use PSBits\ContactBook\Domain\Demand\AddressDemand;
use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Demand\EMailDemand;
use PSBits\ContactBook\Domain\Demand\PhoneNumberDemand;
use PSBits\ContactBook\Domain\Demand\WebsiteDemand;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Model\Job;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\ContactBook\Domain\Repository\AddressRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Domain\Repository\EMailRepository;
use PSBits\ContactBook\Domain\Repository\PhoneNumberRepository;
use PSBits\ContactBook\Domain\Repository\WebsiteRepository;
use PSBits\ContactBook\Utility\AddressUtility;
use PSBits\ContactBook\Utility\PhoneNumberUtility;
use PSBits\Foundation\Utility\StringUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

use function count;
use function in_array;

/**
 * Class ImportService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ImportService
{
    public function __construct(
        protected AddressRepository     $addressRepository,
        protected ContactRepository     $contactRepository,
        protected EMailRepository       $eMailRepository,
        protected PersistenceManager    $persistenceManager,
        protected PhoneNumberRepository $phoneNumberRepository,
        protected WebsiteRepository     $websiteRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function parseVCardFile(string $file): void
    {
        $fileContent = file_get_contents($file);
        $this->parseVCardContent((string)$fileContent);
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidQueryException
     * @throws UnknownObjectException
     */
    public function parseVCardContent(string $content, ?ContactBook $contactBook = null): void
    {
        foreach ($this->extractContacts($content) as $contact) {
            $this->upsertContact($contact, $contactBook);
        }
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidQueryException
     * @throws UnknownObjectException
     */
    public function upsertSingleVCardContent(string $content, ContactBook $contactBook): Contact
    {
        $contacts = $this->extractContacts($content);
        $contact  = array_shift($contacts);

        if (!$contact instanceof Contact) {
            throw new UnknownObjectException(__CLASS__ . ': Could not parse vCard content.', 1755852605);
        }

        return $this->upsertContact($contact, $contactBook, true);
    }

    /**
     * @return Contact[]
     */
    private function extractContacts(string $content): array
    {
        $contact       = null;
        $contacts      = [];
        $preparedLines = [];
        $lines         = StringUtility::explodeByLineBreaks($content);

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (str_starts_with($line, ' ') || str_starts_with($line, "\t")) {
                $lastLineIndex = count($preparedLines) - 1;

                if (0 <= $lastLineIndex) {
                    $preparedLines[$lastLineIndex] .= ltrim($line);
                }

                continue;
            }

            $preparedLines[] = $line;
        }

        foreach ($preparedLines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (str_starts_with($line, 'BEGIN:VCARD')) {
                $contact = new Contact();
            } elseif (str_starts_with($line, 'END:VCARD')) {
                if ($contact instanceof Contact) {
                    $contacts[] = $contact;
                }

                $contact = null;
            } elseif ($contact instanceof Contact) {
                $this->parseVCardLine($line, $contact);
            }
        }

        return $contacts;
    }

    private function addProperties(Contact $contact, Contact $existingContact): void
    {
        $existingContact->addAddresses(...$contact->getAddresses());
        $existingContact->addEMails(...$contact->getEMails());
        $existingContact->addEvents(...$contact->getEvents());
        $existingContact->addJobs(...$contact->getJobs()->toArray());

        if ('' === $existingContact->getMiddleNames()) {
            $existingContact->setMiddleNames($contact->getMiddleNames());
        }

        if ('' === $existingContact->getNickname()) {
            $existingContact->setNickname($contact->getNickname());
        }

        $existingContact->addNote($contact->getNotes());
        $existingContact->addPhoneNumbers(...$contact->getPhoneNumbers());
        $existingContact->addWebsites(...$contact->getWebsites());
    }

    private function replaceProperties(Contact $sourceContact, Contact $targetContact): void
    {
        $targetContact->setAddresses($this->toObjectStorage(...$sourceContact->getAddresses()));
        $targetContact->setBirthName($sourceContact->getBirthName());
        $targetContact->setCompanyName($sourceContact->getCompanyName());
        $targetContact->setEMails($this->toObjectStorage(...$sourceContact->getEMails()));
        $targetContact->setEvents($this->toObjectStorage(...$sourceContact->getEvents()));
        $targetContact->setFirstName($sourceContact->getFirstName());
        $targetContact->setLastName($sourceContact->getLastName());
        $targetContact->setMiddleNames($sourceContact->getMiddleNames());
        $targetContact->setNickname($sourceContact->getNickname());
        $targetContact->setNotes($sourceContact->getNotes());
        $targetContact->setPhoneNumbers($this->toObjectStorage(...$sourceContact->getPhoneNumbers()));
        $targetContact->setType($sourceContact->getType());
        $targetContact->setWebsites($this->toObjectStorage(...$sourceContact->getWebsites()));
    }

    private function parseVCardLine(string $line, Contact $contact): void
    {
        $parts = explode(':', $line, 2);

        if (2 !== count($parts)) {
            return;
        }

        [
            $key,
            $value,
        ]      = $parts;
        $types = [];
        $value = trim($value);

        if (str_contains($key, ';')) {
            $keyParts = explode(';', $key);
            $key      = array_shift($keyParts);

            foreach ($keyParts as $keyPart) {
                if (str_starts_with($keyPart, 'TYPE=')) {
                    $types[] = substr($keyPart, 5);
                }
            }
        }

        if (in_array('QUOTED-PRINTABLE', $types, true)) {
            $value = quoted_printable_decode($value);
        }

        $value = str_replace('\\', '', $value);

        if (empty($value)) {
            return;
        }

        switch ($key) {
            case 'ADR':
                // get address parts from vcard format
                $addressParts = explode(';', $value);
                [
                    'street'      => $street,
                    'houseNumber' => $houseNumber,
                ]        = AddressUtility::splitStreetAndHouseNumber($addressParts[2] ?? '');
                $city    = $addressParts[3] ?? '';
                $county  = $addressParts[4] ?? '';
                $zipCode = $addressParts[5] ?? '';
                $country = $addressParts[6] ?? '';

                $addressDemand = (new AddressDemand())->setCity($city)
                    ->setCountry($country)
                    ->setCounty($county)
                    ->setHouseNumber($houseNumber)
                    ->setStreet($street)
                    ->setZipCode($zipCode);

                $address = $this->addressRepository->findByDemand($addressDemand)
                    ->getFirst() ?? (new Address())->setCity($city)
                    ->setCountry($country)
                    ->setCounty($county)
                    ->setHouseNumber($houseNumber)
                    ->setStreet($street)
                    ->setZipCode($zipCode);

                if (in_array('WORK', $types, true)) {
                    $address->setType(GeneralType::Business);
                } else {
                    $address->setType(GeneralType::Private);
                }

                $contact->addAddresses($address);

                break;
            case 'BDAY':
                $event     = new Event();
                $eventDate = DateTime::createFromFormat('Ymd', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
                $event->setDay((int)$eventDate->format('d'))
                    ->setMonth((int)$eventDate->format('m'))
                    ->setYear((int)$eventDate->format('Y'))
                    ->setShowAnniversaries(true)
                    ->setShowInCalendar(true)
                    ->setType(EventType::Birthday);
                $contact->addEvents($event);

                break;
            case 'EMAIL':
                $eMailDemand = (new EMailDemand())->setEMailAddress($value);
                $eMail       = $this->eMailRepository->findByDemand($eMailDemand)
                    ->getFirst() ?? (new EMail())->setEMailAddress($value);

                if (in_array('WORK', $types, true)) {
                    $eMail->setType(GeneralType::Business);
                } else {
                    $eMail->setType(GeneralType::Private);
                }

                $contact->addEMails($eMail);

                break;
            case 'FN':
                $nameParts = explode(' ', $value);
                $contact->setFirstName(array_shift($nameParts));
                $contact->setLastName(array_pop($nameParts) ?? '');
                $contact->setMiddleNames(implode(' ', $nameParts));

                break;
            case 'item1.URL':
                $websiteDemand = new WebsiteDemand();
                $websiteDemand->setUrl($value);
                $website = $this->websiteRepository->findByDemand($websiteDemand)
                    ->getFirst() ?? (new Website())->setUrl($value);
                $contact->addWebsites($website);

                break;
            case 'NICKNAME':
                $contact->setNickname($value);

                break;
            case 'NOTE':
            case 'TITLE':
                $contact->addNote($value);

                break;
            case 'UID':
                $uuid = str_replace(['urn:uuid:', 'URN:UUID:'], '', $value);

                if ('' !== trim($uuid)) {
                    $contact->setUuidValue($uuid);
                }

                break;
            case 'ORG':
                $value         = str_replace(' - ', ' ', trim($value, ';'));
                $contactDemand = new ContactDemand();
                $contactDemand->addType(ContactType::Company)
                    ->setCompanyName($value);
                $company = $this->contactRepository->findByDemand($contactDemand)
                    ->getFirst() ?? (new Contact())->setCompanyName($value)
                    ->setType(ContactType::Company);
                $job = new Job();
                $job->setCompany($company);
                $contact->addJob($job);

                break;
            case 'TEL':
                if (!str_starts_with($value, '+49')) {
                    // Case not handled yet.
                    return;
                }

                $phoneNumber       = PhoneNumberUtility::getPhoneNumberFromString($value);
                $phoneNumberDemand = (new PhoneNumberDemand())->setAreaCode($phoneNumber->getAreaCode())
                    ->setConnectionIdentifier($phoneNumber->getConnectionIdentifier())
                    ->setInternationalDiallingCode($phoneNumber->getInternationalDiallingCode());

                $phoneNumber = $this->phoneNumberRepository->findByDemand($phoneNumberDemand)
                    ->getFirst() ?? $phoneNumber;

                if (in_array('WORK', $types, true)) {
                    $phoneNumber->setGeneralType(GeneralType::Business);
                } else {
                    $phoneNumber->setGeneralType(GeneralType::Private);
                }

                if (in_array('CELL', $types, true)) {
                    $phoneNumber->setPhoneNumberType(PhoneNumberType::Mobile);
                } elseif (in_array('FAX', $types, true)) {
                    $phoneNumber->setPhoneNumberType(PhoneNumberType::Fax);
                } else {
                    $phoneNumber->setPhoneNumberType(PhoneNumberType::Landline);
                }

                $contact->addPhoneNumbers($phoneNumber);

                break;
            case 'X-ANDROID-CUSTOM':
                if (str_starts_with($value, 'vnd.android.cursor.item/nickname;')) {
                    $valueParts = explode(';', $value);
                    $contact->setNickname($valueParts[1] ?? '');
                }

                break;
        }
    }

    /**
     * @template TEntity of object
     * @param TEntity ...$objects
     * @return ObjectStorage<TEntity>
     */
    private function toObjectStorage(object ...$objects): ObjectStorage
    {
        /** @var ObjectStorage<TEntity> $storage */
        $storage = new ObjectStorage();

        foreach ($objects as $object) {
            $storage->attach($object);
        }

        return $storage;
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidQueryException
     * @throws UnknownObjectException
     */
    private function upsertContact(Contact $contact, ?ContactBook $contactBook = null, bool $replace = false): Contact
    {
        $existingContact = null;

        if ($contactBook instanceof ContactBook) {
            $existingContact = $this->contactRepository->findByContactBookAndUuid($contactBook, $contact->getUuid());
        } elseif ('' !== $contact->getUuid()) {
            $existingContact = $this->contactRepository->findByUuid($contact->getUuid());
        }

        if (!$existingContact instanceof Contact) {
            $contactDemand = new ContactDemand();
            $contactDemand->setFirstName($contact->getFirstName())
                ->setLastName($contact->getLastName());
            $existingContact = $this->contactRepository->findByDemand($contactDemand)
                ->getFirst();
        }

        if ($existingContact instanceof Contact) {
            if ($replace) {
                $this->replaceProperties($contact, $existingContact);
            } else {
                $this->addProperties($contact, $existingContact);
            }

            if ($contactBook instanceof ContactBook) {
                $existingContact->setContactBook($contactBook);
            }

            $this->contactRepository->update($existingContact);
            $this->persistenceManager->persistAll();

            return $existingContact;
        }

        if ($contactBook instanceof ContactBook) {
            $contact->setContactBook($contactBook);
        }

        $this->contactRepository->add($contact);
        $this->persistenceManager->persistAll();

        return $contact;
    }
}
