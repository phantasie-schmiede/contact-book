<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

use JsonException;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Utility\ContactBookUtility;

/**
 * Class ContactDemand
 *
 * @package PSBits\ContactBook\Domain\Demand
 */
class ContactDemand
{
    /** @var int[] */
    protected array  $categoryUids    = [];
    protected string $companyName     = '';
    protected int    $contactBookUid  = 0;
    protected int    $contactGroupUid = 0;

    /** @var int[] */
    protected array $contactUids = [];

    /** @var int[] */
    protected array  $excludedContacts              = [];
    protected string $firstName                     = '';
    protected string $lastName                      = '';
    protected bool   $onlyExportRelevantInformation = false;
    protected bool   $respectStoragePage            = false;

    /** @var ContactType[] */
    protected array $types = [];

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        $this->contactBookUid = ContactBookUtility::getActiveContactBookUid() ?? 0;
    }

    public function addCategoryUids(int ...$categoryUids): static
    {
        foreach ($categoryUids as $categoryUid) {
            $this->categoryUids[] = $categoryUid;
        }

        return $this;
    }

    public function addContactUids(int ...$contactUids): static
    {
        foreach ($contactUids as $contactUid) {
            $this->contactUids[] = $contactUid;
        }

        return $this;
    }

    public function addExcludedContact(Contact|int $contact): static
    {
        if ($contact instanceof Contact) {
            $this->excludedContacts[] = $contact->getUid();
        } else {
            $this->excludedContacts[] = $contact;
        }

        return $this;
    }

    public function addType(ContactType $type): static
    {
        $this->types[] = $type;

        return $this;
    }

    /** @return int[] */
    public function getCategoryUids(): array
    {
        return $this->categoryUids;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getContactBookUid(): int
    {
        return $this->contactBookUid;
    }

    public function getContactGroupUid(): int
    {
        return $this->contactGroupUid;
    }

    /** @return int[] */
    public function getContactUids(): array
    {
        return $this->contactUids;
    }

    /** @return int[] */
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

    public function getRespectStoragePage(): bool
    {
        return $this->respectStoragePage;
    }

    /** @return ContactType[] */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function setCategoryUids(int ...$categoryUids): static
    {
        $this->categoryUids = $categoryUids;

        return $this;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function setContactGroupUid(int $contactGroupUid): static
    {
        $this->contactGroupUid = $contactGroupUid;

        return $this;
    }

    public function setContactUids(int ...$contactUids): static
    {
        $this->contactUids = $contactUids;

        return $this;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setOnlyExportRelevantInformation(bool $onlyExportRelevantInformation): static
    {
        $this->onlyExportRelevantInformation = $onlyExportRelevantInformation;

        return $this;
    }

    public function setRespectStoragePage(bool $respectStoragePage): static
    {
        $this->respectStoragePage = $respectStoragePage;

        return $this;
    }

    public function unsetContactBookUid(): static
    {
        $this->contactBookUid = 0;

        return $this;
    }
}
