<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

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
    protected int $areaCode = 0;

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
    protected string $generalType = 'private';

    #[Input]
    protected int $internationalDiallingCode = 0;

    #[Select(items   : [
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $phoneNumberType = 'mobile';

    public function getAreaCode(): int
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

    public function getInternationalDiallingCode(): int
    {
        return $this->internationalDiallingCode;
    }

    public function getPhoneNumberType(): string
    {
        return $this->phoneNumberType;
    }

    public function setAreaCode(int $areaCode): void
    {
        $this->areaCode = $areaCode;
    }

    public function setConnectionIdentifier(string $connectionIdentifier): void
    {
        $this->connectionIdentifier = $connectionIdentifier;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function setGeneralType(string $generalType): void
    {
        $this->generalType = $generalType;
    }

    public function setInternationalDiallingCode(int $internationalDiallingCode): void
    {
        $this->internationalDiallingCode = $internationalDiallingCode;
    }

    public function setPhoneNumberType(string $phoneNumberType): void
    {
        $this->phoneNumberType = $phoneNumberType;
    }
}
