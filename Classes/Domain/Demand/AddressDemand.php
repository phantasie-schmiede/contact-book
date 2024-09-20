<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Demand;

/**
 * Class AddressDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
 */
class AddressDemand
{
    protected string $city        = '';
    protected string $country     = '';
    protected string $county      = '';
    protected string $houseNumber = '';
    protected string $street      = '';
    protected string $zipCode     = '';

    public function getCity(): string
    {
        return $this->city;
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

    public function getStreet(): string
    {
        return $this->street;
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

    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }
}
