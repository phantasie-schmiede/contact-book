<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;

/**
 * Class ContactDemand
 *
 * @package PSBits\ContactBook\Domain\Demand
 */
class ContactDemand
{
    protected string $companyName                   = '';
    protected array  $excludedContacts              = [];
    protected string $firstName                     = '';
    protected string $lastName                      = '';
    protected bool   $onlyExportRelevantInformation = false;
    protected array  $types                         = [];

    public function addExcludedContact(Contact|int $contact): self
    {
        if ($contact instanceof Contact) {
            $this->excludedContacts[] = $contact->getUid();
        } else {
            $this->excludedContacts[] = $contact;
        }

        return $this;
    }

    public function addType(ContactType $type): self
    {
        $this->types[] = $type;

        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getExcludedContacts(): array
    {
        return $this->excludedContacts;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getOnlyExportRelevantInformation(): bool
    {
        return $this->onlyExportRelevantInformation;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setOnlyExportRelevantInformation(bool $onlyExportRelevantInformation): self
    {
        $this->onlyExportRelevantInformation = $onlyExportRelevantInformation;

        return $this;
    }
}
