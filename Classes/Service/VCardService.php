<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service;

use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbContacts\Domain\Model\Address;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\EMail;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Model\PhoneNumber;
use PSB\PsbContacts\Domain\Model\Website;
use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbTemplates\Service\CountryNamesService;

/**
 * Class VCardService
 *
 * @package PSB\PsbContacts\Service
 */
readonly class VCardService
{
    public function __construct(
        protected CountryNamesService $countryNamesService,
    ) {
    }

    public function getVCard(Contact $contact): string
    {
        $vCard = '';
        $this->addLine('BEGIN:VCARD', $vCard);
        $this->addLine('VERSION:4.0', $vCard);

        if (ContactType::Company === $contact->getType()) {
            $this->addCompanyInformation($contact, $vCard);
        } else {
            $this->addIndividualInformation($contact, $vCard);
        }

        $this->addAddresses($contact, $vCard);
        $this->addEMails($contact, $vCard);
        $this->addPhoneNumbers($contact, $vCard);
        $this->addWebsites($contact, $vCard);
        $this->addLine('END:VCARD', $vCard);

        return $vCard;
    }

    private function addAddresses(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getAddresses()
                ->count()) {
            return;
        }

        /** @var Address $address */
        foreach ($contact->getAddresses() as $address) {
            $this->addLine(
                'ADR;' . $this->getGeneralType($address->getType()) . ':;;' . implode(';', [
                    $address->getStreetAndHouseNumber(),
                    $address->getCity(),
                    $address->getCounty(),
                    $address->getZipCode(),
                    $this->countryNamesService->getFromCode($address->getCountry()),
                ]),
                $vCard
            );
        }
    }

    private function addBirthday(Contact $contact, string &$vCard): void
    {
        $birthEvent = ContactUtility::getBirthEvent($contact);

        if ($birthEvent instanceof Event) {
            if ($birthEvent->isYearUnknown()) {
                $this->addLine(
                    'BDAY:' . $birthEvent->getDate()
                        ->format('--md'),
                    $vCard
                );
            } else {
                $this->addLine(
                    'BDAY:' . $birthEvent->getDate()
                        ->format('Ymd'),
                    $vCard
                );
            }
        }
    }

    private function addCompanyInformation(Contact $contact, string &$vCard): void
    {
        $this->addLine('FN:' . $contact->getCompanyName(), $vCard);
        $this->addLine('KIND:org', $vCard);
    }

    private function addEMails(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getEMails()
                ->count()) {
            return;
        }

        /** @var EMail $eMail */
        foreach ($contact->getEMails() as $eMail) {
            $this->addLine(
                'EMAIL;' . $this->getGeneralType($eMail->getType()) . ':' . $eMail->getEMailAddress(),
                $vCard
            );
        }
    }

    private function addIndividualInformation(Contact $contact, string &$vCard): void
    {
        $this->addBirthday($contact, $vCard);
        $this->addLine('FN:' . $contact->getFullName(), $vCard);
        $this->addLine(
            'N:' . $contact->getLastName() . ';' . $contact->getFirstName() . ';' . $contact->getMiddleNames(
            ) . ';' . $contact->getTitle() . ';',
            $vCard
        );

        if ('' !== $contact->getNickname()) {
            $this->addLine('NICKNAME:' . $contact->getNickname(), $vCard);
        }
    }

    private function addLine(string $line, string &$vCard): void
    {
        $vCard .= $line . PHP_EOL;
    }

    private function addPhoneNumbers(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getPhoneNumbers()
                ->count()) {
            return;
        }

        /** @var PhoneNumber $phoneNumber */
        foreach ($contact->getPhoneNumbers() as $phoneNumber) {
            $this->addLine(
                'TEL;VALUE=uri;' . $this->getGeneralType(
                    $phoneNumber->getGeneralType()
                ) . ',' . $this->getPhoneNumberType(
                    $phoneNumber->getPhoneNumberType()
                ) . ':tel:' . $phoneNumber->getCompleteNumber(),
                $vCard
            );
        }
    }

    private function addWebsites(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getWebsites()
                ->count()) {
            return;
        }

        /** @var Website $website */
        foreach ($contact->getWebsites() as $website) {
            $this->addLine('URL;' . $this->getGeneralType($website->getType()) . ':' . $website->getUrl(), $vCard);
        }
    }

    private function getGeneralType(GeneralType $type): string
    {
        return 'TYPE=' . match ($type) {
                GeneralType::Private  => 'home',
                GeneralType::Business => 'work',
                GeneralType::Other    => 'other',
            };
    }

    private function getPhoneNumberType(PhoneNumberType $getPhoneNumberType): string
    {
        return match ($getPhoneNumberType) {
            PhoneNumberType::Fax      => 'fax',
            PhoneNumberType::Landline => 'voice',
            PhoneNumberType::Mobile   => 'cell',
            PhoneNumberType::Other    => 'other',
        };
    }
}
