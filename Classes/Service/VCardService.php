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
use PSB\PsbContacts\Service\Enum\VCardVersion;
use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbTemplates\Service\CountryNamesService;
use RuntimeException;

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

    public function getVCard(Contact $contact, VCardVersion $version): string
    {
        $vCard = '';
        $this->addLine('BEGIN:VCARD', $vCard);
        $this->addLine('VERSION:' . $version->getVersionNumber(), $vCard);

        if (ContactType::Company === $contact->getType()) {
            $this->addCompanyInformation($contact, $vCard);
        } else {
            $this->addIndividualInformation($contact, $vCard, $version);
        }

        $this->addAddresses($contact, $vCard);
        $this->addEMails($contact, $vCard);
        $this->addPhoneNumbers($contact, $vCard, $version);
        $this->addUid($contact, $vCard, $version);
        $this->addWebsites($contact, $vCard);
        $this->addLine('END:VCARD', $vCard);

        // Ensure the VCard is encoded in UTF-8.
        return mb_convert_encoding($vCard, 'UTF-8', 'auto');
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

    private function addBirthday(Contact $contact, string &$vCard, VCardVersion $version): void
    {
        $birthEvent = ContactUtility::getBirthEvent($contact);

        if ($birthEvent instanceof Event) {
            if ($birthEvent->isDateComplete()) {
                $this->addLine(
                    'BDAY:' . $birthEvent->getDate()
                        ->format('Y-m-d'),
                    $vCard
                );
            } elseif ($birthEvent->isMonthAndDayGiven()) {
                // Incomplete birth dates are only supported in VCard 4.0.
                if (VCardVersion::V4_0 !== $version) {
                    /*
                     * Apple does not support incomplete birth dates in VCard 3.0. As a workaround, we set the year
                     * to 1604, which is the year of the first known birth date in the Gregorian calendar.
                     * This is a hack, but it works for Apple.
                     */
                    $this->addLine(
                        'BDAY;X-APPLE-OMIT-YEAR=1604:' . $birthEvent->getDate(true)
                            ->format('1604-m-d'),
                        $vCard
                    );
                } else {
                    $this->addLine(
                        'BDAY:' . $birthEvent->getDate(true)
                            ->format('--md'),
                        $vCard
                    );
                }
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

    private function addIndividualInformation(Contact $contact, string &$vCard, VCardVersion $version): void
    {
        $this->addBirthday($contact, $vCard, $version);
        $this->addLine('FN:' . $contact->getShortName(), $vCard);
        $this->addLine(
            'N:' . $contact->getLastName() . ';' . $contact->getFirstName(
            ) . ';' . (VCardVersion::Apple !== $version ? $contact->getMiddleNames() : '') . ';' . $contact->getTitle(
            ) . ';',
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

    private function addPhoneNumbers(Contact $contact, string &$vCard, VCardVersion $version): void
    {
        if (0 === $contact->getPhoneNumbers()
                ->count()) {
            return;
        }

        /** @var PhoneNumber $phoneNumber */
        foreach ($contact->getPhoneNumbers() as $phoneNumber) {
            $line = 'TEL;' . (VCardVersion::V4_0 === $version ? 'VALUE=uri;' : '');

            /*
             * Apple doesn't follow the official specifications and can't handle multiple comma-separated types.
             * Instead, it uses several TYPE attributes, which are separated by a semicolon. According to the RFC,
             * duplicate attributes are not allowed. Additionally, 'mobile' is not shown as phone number type in iOS,
             * if TYPE=cell does not stand alone.
             */
            if (VCardVersion::Apple === $version) {
                if (GeneralType::Private === $phoneNumber->getGeneralType(
                    ) && PhoneNumberType::Mobile === $phoneNumber->getPhoneNumberType()) {
                    $line .= 'TYPE=cell';
                } else {
                    $line .= $this->getGeneralType($phoneNumber->getGeneralType());
                    $line .= ';TYPE=' . $this->getPhoneNumberType($phoneNumber->getPhoneNumberType());
                }
            } else {
                $line .= $this->getGeneralType(
                        $phoneNumber->getGeneralType()
                    ) . ',' . $this->getPhoneNumberType(
                        $phoneNumber->getPhoneNumberType()
                    );
            }

            $line .= ':' . (VCardVersion::V4_0 === $version ? 'tel:' : '') . $phoneNumber->getCompleteNumber();
            $this->addLine(
                $line,
                $vCard
            );
        }
    }

    private function addUid(Contact $contact, string &$vCard, VCardVersion $version): void
    {
        if ('' === $contact->getUuid()) {
            throw new RuntimeException(
                __CLASS__ . ': Contact [uid:' . $contact->getUid() . '] has no UUID!', 1740157024
            );
        }

        if (VCardVersion::Apple === $version) {
            $this->addLine('X-ABUID:' . $contact->getUuid() . ':ABPerson', $vCard);
        } else {
            $this->addLine('UID:' . (VCardVersion::V4_0 === $version ? 'urn:uuid:' : '') . $contact->getUuid(), $vCard);
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
