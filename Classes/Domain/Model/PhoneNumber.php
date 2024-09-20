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
    #[Input]
    protected string $areaCode = '';

    #[Input]
    protected string $connectionIdentifier = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_phonenumbers', mmOppositeField: 'phoneNumbers', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $extension = '';

    /** @var string $generalType Needed for Extbase's property mapper! */
    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $generalType = 'private';

    #[Input]
    protected string $internationalDiallingCode = '';

    /** @var string $phoneNumberType Needed for Extbase's property mapper! */
    #[Select(items   : [
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $phoneNumberType = 'mobile';

    public function getAreaCode(): string
    {
        return $this->areaCode;
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

    public function getGeneralType(): string
    {
        return $this->generalType;
    }

    public function getInternationalDiallingCode(): string
    {
        return $this->internationalDiallingCode;
    }

    public function getPhoneNumberType(): string
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
        $this->generalType = $generalType->value;

        return $this;
    }

    public function setInternationalDiallingCode(string $internationalDiallingCode): self
    {
        $this->internationalDiallingCode = $internationalDiallingCode;

        return $this;
    }

    public function setPhoneNumberType(PhoneNumberType $phoneNumberType): self
    {
        $this->phoneNumberType = $phoneNumberType->value;

        return $this;
    }
}
