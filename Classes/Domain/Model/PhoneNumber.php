<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class PhoneNumber
 *
 * Number formats:
 *  - 0<area code> <connection identifier>
 *  - 0<area code> <connection identifier>-<extension>
 *  - +<international dialling code> <area code> <connection identifier>
 *  - +<international dialling code> <area code> <connection identifier>-<extension>
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label        : 'internationalDiallingCode', labelAlt: [
    'areaCode',
    'connectionIdentifier',
    'extension',
],     labelAltForce: true, searchFields: [
    'areaCode',
    'connectionIdentifier',
    'extension',
    'internationalDiallingCode',
    'notes',
])]
class PhoneNumber extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'telephone';

    #[Input]
    protected string $areaCode = '';

    #[Input]
    protected string $connectionIdentifier = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_phonenumbers', mmOppositeField: 'phoneNumbers', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $extension = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected GeneralType $generalType = GeneralType::Private;

    #[Input]
    protected string $internationalDiallingCode = '';

    #[Select(items   : [
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected PhoneNumberType $phoneNumberType = PhoneNumberType::Mobile;

    public function getAdditionalLinks(): array
    {
        if (PhoneNumberType::Mobile === $this->getPhoneNumberType()) {
            return [
                'chat-left-dots' => 'sms:' . $this->getCompleteNumber(),
            ];
        }

        return [];
    }

    public function getAreaCode(): string
    {
        return $this->areaCode;
    }

    public function getCompactValue(): string
    {
        return sprintf(
            '+%s %s %s %s',
            $this->internationalDiallingCode,
            $this->areaCode,
            $this->connectionIdentifier,
            $this->extension,
        );
    }

    public function getCompleteNumber(): string
    {
        return '+' . $this->internationalDiallingCode . $this->areaCode . $this->connectionIdentifier . $this->extension;
    }

    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getGeneralType(): GeneralType
    {
        return $this->generalType;
    }

    public function getInternationalDiallingCode(): string
    {
        return $this->internationalDiallingCode;
    }

    public function getLink(): string
    {
        return 'tel:' . $this->getCompleteNumber();
    }

    public function getPhoneNumberType(): PhoneNumberType
    {
        return $this->phoneNumberType;
    }

    public function setAreaCode(string $areaCode): self
    {
        $this->areaCode = $areaCode;

        return $this;
    }

    public function setConnectionIdentifier(string $connectionIdentifier): self
    {
        $this->connectionIdentifier = $connectionIdentifier;

        return $this;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function setGeneralType(GeneralType $generalType): self
    {
        $this->generalType = $generalType;

        return $this;
    }

    public function setInternationalDiallingCode(string $internationalDiallingCode): self
    {
        $this->internationalDiallingCode = $internationalDiallingCode;

        return $this;
    }

    public function setPhoneNumberType(PhoneNumberType $phoneNumberType): self
    {
        $this->phoneNumberType = $phoneNumberType;

        return $this;
    }
}
