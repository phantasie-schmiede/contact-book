<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class PhoneNumber
 *
 * Number format: +<international dialling code> <area code> <connection identifier>
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl]
class PhoneNumber extends AbstractEntity
{
    #[Input]
    protected string $areaCode = '';

    #[Input]
    protected string $connectionIdentifier = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_phonenumbers', mmOppositeField: 'phoneNumbers')]
    protected ObjectStorage $contacts;

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $generalType = 'private';

    #[Input]
    protected string $internationalDiallingCode = '';

    #[Text]
    protected string $notes = '';

    #[Select(items   : [
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected PhoneNumberType $phoneNumberType = PhoneNumberType::Mobile;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function getAreaCode(): string
    {
        return $this->areaCode;
    }

    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getGeneralType(): string
    {
        return $this->generalType;
    }

    public function getInternationalDiallingCode(): string
    {
        return $this->internationalDiallingCode;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getPhoneNumberType(): PhoneNumberType
    {
        return $this->phoneNumberType;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function setAreaCode(string $areaCode): void
    {
        $this->areaCode = $areaCode;
    }

    public function setConnectionIdentifier(string $connectionIdentifier): void
    {
        $this->connectionIdentifier = $connectionIdentifier;
    }

    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function setGeneralType(string $generalType): void
    {
        $this->generalType = $generalType;
    }

    public function setInternationalDiallingCode(string $internationalDiallingCode): void
    {
        $this->internationalDiallingCode = $internationalDiallingCode;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setPhoneNumberType(PhoneNumberType $phoneNumberType): void
    {
        $this->phoneNumberType = $phoneNumberType;
    }
}
