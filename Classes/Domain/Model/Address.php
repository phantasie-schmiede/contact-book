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
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
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
class Address extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'sign-turn-right';

    #[Input]
    protected string $city = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_addresses', mmOppositeField: 'addresses', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $country = '';

    #[Input]
    protected string $county = '';

    #[Input]
    protected string $houseNumber = '';

    #[Input]
    protected string $street = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected GeneralType $type = GeneralType::Private;

    #[Input]
    protected string $zipCode = '';

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCompactValue(): string
    {
        return sprintf(
            '%s %s, %s %s',
            $this->street,
            $this->houseNumber,
            $this->zipCode,
            $this->city
        );
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

    public function getLink(): string
    {
        return 'https://www.google.com/maps/dir//' . $this->getCompactValue();
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getType(): GeneralType
    {
        return $this->type;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function setCounty(string $county): self
    {
        $this->county = $county;

        return $this;
    }

    public function setHouseNumber(string $houseNumber): self
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function setType(GeneralType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }
}
