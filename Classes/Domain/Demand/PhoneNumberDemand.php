<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

/**
 * Class PhoneNumberDemand
 *
 * @package PSBits\ContactBook\Domain\Demand
 */
class PhoneNumberDemand
{
    protected string $areaCode                  = '';
    protected string $connectionIdentifier      = '';
    protected string $extension                 = '';
    protected string $internationalDiallingCode = '';

    public function getAreaCode(): string
    {
        return $this->areaCode;
    }

    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getInternationalDiallingCode(): string
    {
        return $this->internationalDiallingCode;
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

    public function setInternationalDiallingCode(string $internationalDiallingCode): self
    {
        $this->internationalDiallingCode = $internationalDiallingCode;

        return $this;
    }
}
