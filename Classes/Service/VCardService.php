<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Service;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\Templates\Service\CountryNamesService;
use RuntimeException;

/**
 * Class VCardService
 *
 * @package PSBits\ContactBook\Service
 */
class VCardService
{
    private const VCARD_EOL = "\r\n";

    protected VCardVersion $version = VCardVersion::V2_1;

    public function __construct(
        protected readonly CountryNamesService $countryNamesService,
    ) {
    }

    public function getVCard(Contact $contact, VCardVersion $version): string
    {
        $vCard = '';
        $this->version = $version;
        $this->addLine('BEGIN:VCARD', $vCard);
        $this->addLine('VERSION:' . $version->getVersionNumber(), $vCard);

        if (ContactType::Company === $contact->getType()) {
            $this->addCompanyInformation($contact, $vCard);
        } else {
            $this->addIndividualInformation($contact, $vCard);
        }

        $this->addAddresses($contact, $vCard);
        $this->addEMails($contact, $vCard);
        $this->addPhoneNumbers($contact, $vCard);
        // $this->addRevision($contact, $vCard);
        $this->addUid($contact, $vCard);
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
            $this->addStructuredTextLine(
                'ADR;' . $this->getGeneralType($address->getType()),
                [
                    '',
                    '',
                    $address->getStreetAndHouseNumber(),
                    $address->getCity(),
                    $address->getCounty(),
                    $address->getZipCode(),
                    $this->countryNamesService->getFromCode($address->getCountry()),
                ],
                $vCard
            );
        }
    }

    private function addBirthday(Contact $contact, string &$vCard): void
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
                if (VCardVersion::V4_0 !== $this->version) {
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
        $this->addTextLine('FN', $contact->getCompanyName(), $vCard);

        if (VCardVersion::V4_0 === $this->version) {
            $this->addLine('KIND:organization', $vCard);
        }
    }

    private function addEMails(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getEMails()
                ->count()) {
            return;
        }

        /** @var EMail $eMail */
        foreach ($contact->getEMails() as $eMail) {
            $this->addTextLine(
                'EMAIL;' . $this->getGeneralType($eMail->getType()),
                $eMail->getEMailAddress(),
                $vCard
            );
        }
    }

    private function addIndividualInformation(Contact $contact, string &$vCard): void
    {
        $this->addBirthday($contact, $vCard);
        $this->addTextLine('FN', $contact->getShortName(), $vCard);
        $this->addStructuredTextLine(
            'N',
            [
                $contact->getLastName(),
                $contact->getFirstName(),
                VCardVersion::Apple !== $this->version ? $contact->getMiddleNames() : '',
                $contact->getTitle(),
                '',
            ],
            $vCard
        );

        if (VCardVersion::V2_1 !== $this->version && '' !== $contact->getNickname()) {
            $this->addTextLine('NICKNAME', $contact->getNickname(), $vCard);
        }

        if (VCardVersion::V4_0 === $this->version) {
            $this->addLine('KIND:individual', $vCard);
        }
    }

    private function addLine(string $line, string &$vCard): void
    {
        $vCard .= $this->foldLine($line) . self::VCARD_EOL;
    }

    private function addPhoneNumbers(Contact $contact, string &$vCard): void
    {
        if (0 === $contact->getPhoneNumbers()
                ->count()) {
            return;
        }

        /** @var PhoneNumber $phoneNumber */
        foreach ($contact->getPhoneNumbers() as $phoneNumber) {
            $line = 'TEL;' . (VCardVersion::V4_0 === $this->version ? 'VALUE=uri;' : '');

            /*
             * Apple doesn't follow the official specifications and can't handle multiple comma-separated types.
             * Instead, it uses several TYPE attributes, which are separated by a semicolon. According to the RFC,
             * duplicate attributes are not allowed. Additionally, 'mobile' is not shown as phone number type in iOS,
             * if TYPE=cell does not stand alone.
             */
            if (VCardVersion::Apple === $this->version) {
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
                    ) . (VCardVersion::V2_1 === $this->version ? ';' : ',') . $this->getPhoneNumberType(
                        $phoneNumber->getPhoneNumberType()
                    );
            }

            $line .= ':' . (VCardVersion::V4_0 === $this->version ? 'tel:' : '') . $phoneNumber->getCompleteNumber();
            $this->addLine(
                $line,
                $vCard
            );
        }
    }

    private function addUid(Contact $contact, string &$vCard): void
    {
        if ('' === $contact->getUuid()) {
            throw new RuntimeException(
                __CLASS__ . ': Contact [uid:' . $contact->getUid() . '] has no UUID!', 1740157024
            );
        }

        if (VCardVersion::Apple === $this->version) {
            $this->addLine('X-ABUID:' . $contact->getUuid() . ':ABPerson', $vCard);
        } else {
            $this->addLine(
                'UID:' . (VCardVersion::V4_0 === $this->version ? 'urn:uuid:' : '') . $contact->getUuid(),
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
            $this->addTextLine(
                'URL;' . $this->getGeneralType($website->getType()),
                $website->getUrl(),
                $vCard,
                false,
                false
            );
        }
    }

    private function addStructuredTextLine(string $property, array $values, string &$vCard): void
    {
        $line = $property . ':' . implode(
            ';',
            array_map(
                fn(string $value) => $this->escapeTextValue($value),
                $values
            )
        );
        $this->addLine($line, $vCard);
    }

    private function addTextLine(
        string $property,
        string $value,
        string &$vCard,
        bool $escapeComma = true,
        bool $escapeSemicolon = true
    ): void {
        $this->addLine(
            $property . ':' . $this->escapeTextValue($value, $escapeComma, $escapeSemicolon),
            $vCard
        );
    }

    private function escapeTextValue(string $value, bool $escapeComma = true, bool $escapeSemicolon = true): string
    {
        $escapedValue = str_replace(
            ['\\', "\r\n", "\r", "\n"],
            ['\\\\', '\n', '\n', '\n'],
            $value
        );

        if ($escapeSemicolon) {
            $escapedValue = str_replace(';', '\;', $escapedValue);
        }

        if ($escapeComma) {
            $escapedValue = str_replace(',', '\,', $escapedValue);
        }

        return $escapedValue;
    }

    private function foldLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $foldedLine = '';
        $remainingLine = $line;
        $isFirstLine = true;

        while ('' !== $remainingLine) {
            $prefix = $isFirstLine ? '' : ' ';
            $chunkLength = 75 - strlen($prefix);
            $chunk = mb_strcut($remainingLine, 0, $chunkLength, 'UTF-8');
            $remainingLine = substr($remainingLine, strlen($chunk));

            $foldedLine .= $prefix . $chunk;

            if ('' !== $remainingLine) {
                $foldedLine .= self::VCARD_EOL;
            }

            $isFirstLine = false;
        }

        return $foldedLine;
    }

    private function getGeneralType(GeneralType $type): string
    {
        $typeString = '';

        if (VCardVersion::V2_1 !== $this->version) {
            $typeString .= 'TYPE=';
        }

        return $typeString . match ($type) {
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
