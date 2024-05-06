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
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
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
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_phonenumbers', mmOppositeField: 'phoneNumbers', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Select(items      : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected GeneralType $generalType = GeneralType::Private;

    #[Input]
    protected string $internationalDiallingCode = '';

    #[Text]
    protected string $notes = '';

    #[Select(items      : [
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ],       prependItem: Select::EMPTY_DEFAULT_ITEM)]
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

    public function getGeneralType(): GeneralType
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
}
