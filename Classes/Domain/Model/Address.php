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
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Address
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label        : 'city', labelAlt: [
    'street',
    'houseNumber',
],     labelAltForce: true, searchFields: [
    'city',
    'country',
    'county',
    'houseNumber',
    'street',
    'zipCode',
])]
class Address extends AbstractEntity
{
    #[Input]
    protected string $city = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_address_contacts', mmOppositeField: 'addresses')]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $country = '';

    #[Input]
    protected string $county = '';

    #[Input]
    protected string $houseNumber = '';

    #[Text]
    protected string $notes = '';

    #[Input]
    protected string $street = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $type = 'private';

    #[Input]
    protected string $zipCode = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getCounty(): string
    {
        return $this->county;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function setCounty(string $county): void
    {
        $this->county = $county;
    }

    public function setHouseNumber(string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }
}
