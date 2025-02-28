<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Demand;

use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Model\Contact;

/**
 * Class ContactDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
 */
class ContactDemand
{
    protected string $companyName      = '';
    protected array  $excludedContacts = [];
    protected string $firstName        = '';
    protected ?bool  $hasPhoneNumber   = null;
    protected string $lastName         = '';
    protected array  $types            = [];

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

    public function getHasPhoneNumber(): ?bool
    {
        return $this->hasPhoneNumber;
    }

    public function getLastName(): string
    {
        return $this->lastName;
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

    public function setHasPhoneNumber(?bool $hasPhoneNumber): self
    {
        $this->hasPhoneNumber = $hasPhoneNumber;

        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }
}
