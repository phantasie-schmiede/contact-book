<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service;

use DateTime;
use PSB\PsbContacts\Domain\Demand\AddressDemand;
use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Demand\EMailDemand;
use PSB\PsbContacts\Domain\Demand\PhoneNumberDemand;
use PSB\PsbContacts\Domain\Demand\WebsiteDemand;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbContacts\Domain\Model\Address;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\EMail;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Model\Website;
use PSB\PsbContacts\Domain\Repository\AddressRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Domain\Repository\EMailRepository;
use PSB\PsbContacts\Domain\Repository\PhoneNumberRepository;
use PSB\PsbContacts\Domain\Repository\WebsiteRepository;
use PSB\PsbContacts\Utility\AddressUtility;
use PSB\PsbContacts\Utility\PhoneNumberUtility;
use PSB\PsbFoundation\Utility\StringUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use function count;
use function in_array;

/**
 * Class ImportService
 *
 * @package PSB\PsbContacts\Service
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
        $contact = null;
        $fileContent = file_get_contents($file);
        $lines = StringUtility::explodeByLineBreaks($fileContent);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (str_starts_with($line, 'BEGIN:VCARD')) {
                $contact = new Contact();
            } elseif (str_starts_with($line, 'END:VCARD')) {
                if ($contact instanceof Contact) {
                    $contactDemand = new ContactDemand();
                    $contactDemand->setFirstName($contact->getFirstName())
                        ->setLastName($contact->getLastName());
                    $existingContact = $this->contactRepository->findByDemand($contactDemand)
                        ->getFirst();

                    if ($existingContact instanceof Contact) {
                        $this->addProperties($contact, $existingContact);
                        $this->contactRepository->update($existingContact);
                    } else {
                        $this->contactRepository->add($contact);
                    }

                    $this->persistenceManager->persistAll();
                }

                $contact = null;
            } elseif ($contact instanceof Contact) {
                $this->parseVCardLine($line, $contact);
            }
        }
    }

    private function addProperties(Contact $contact, Contact $existingContact): void
    {
        $existingContact->addAddresses(...$contact->getAddresses());
        $existingContact->addCompanies(...$contact->getCompanies());
        $existingContact->addEMails(...$contact->getEMails());
        $existingContact->addEvents(...$contact->getEvents());

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

    private function parseVCardLine(string $line, Contact $contact): void
    {
        $parts = explode(':', $line, 2);

        if (2 !== count($parts)) {
            return;
        }

        [
            $key,
            $value,
        ] = $parts;
        $types = [];
        $value = trim($value);

        if (str_contains($key, ';')) {
            $keyParts = explode(';', $key);
            $key = array_shift($keyParts);

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
                ] = AddressUtility::splitStreetAndHouseNumber($addressParts[2] ?? '');
                $city = $addressParts[3] ?? '';
                $county = $addressParts[4] ?? '';
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
                $event = new Event();
                $eventDate = DateTime::createFromFormat('Ymd', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
                $event->setDate($eventDate)
                    ->setShowAnniversaries(true)
                    ->setShowInCalendar(true)
                    ->setType(EventType::Birthday);
                $contact->addEvents($event);
                break;
            case 'EMAIL':
                $eMailDemand = (new EMailDemand())->setEMailAddress($value);
                $eMail = $this->eMailRepository->findByDemand($eMailDemand)
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
            case 'ORG':
                $value = str_replace(' - ', ' ', trim($value, ';'));
                $contactDemand = new ContactDemand();
                $contactDemand->addType(ContactType::Company)
                    ->setCompanyName($value);
                $company = $this->contactRepository->findByDemand($contactDemand)
                    ->getFirst() ?? (new Contact())->setCompanyName($value)
                    ->setType(ContactType::Company);
                $contact->addCompanies($company);
                break;
            case 'TEL':
                if (!str_starts_with($value, '+49')) {
                    // Case not handled yet.
                    return;
                }

                $phoneNumber = PhoneNumberUtility::getPhoneNumberFromString($value);
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
}
