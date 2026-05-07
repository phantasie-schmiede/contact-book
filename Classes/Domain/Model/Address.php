<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use function sprintf;

/**
 * Class Address
 *
 * @package PSBits\ContactBook\Domain\Model
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

    #[Column(position: 'after:zipCode')]
    #[Input]
    protected string $city = '';

    /** @var ObjectStorage<Contact> */
    #[Column(position: 'after:country')]
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_addresses', mmOppositeField: 'addresses', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Column(position: 'after:county')]
    #[Input]
    protected string $country = '';

    #[Column(position: 'after:city')]
    #[Input]
    protected string $county = '';

    #[Column(position: 'after:street')]
    #[Input]
    protected string $houseNumber = '';

    #[Input]
    protected string $street = '';

    #[Column(required: true)]
    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_STRING)]
    protected GeneralType $type = GeneralType::Private;

    #[Column(position: 'after:houseNumber')]
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
        return 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($this->getCompactValue());
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getStreetAndHouseNumber(): string
    {
        return $this->street . ($this->houseNumber ? ' ' . $this->houseNumber : '');
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
