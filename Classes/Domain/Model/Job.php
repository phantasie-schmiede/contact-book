<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use JsonException;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\ContactBook\Utility\JsonUtility;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;

use function implode;
use function in_array;

#[Ctrl(label: 'company', labelAlt: 'position, role', labelAltForce: true, searchFields: [
    'company.name',
    'notes',
    'position',
    'role',
])]
class Job extends AbstractContactRelatedEntity
{
    public const array EMPTY_DEFAULT_ITEM = [
        [
            'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/job.xlf:company.default',
            'value' => 0,
        ],
    ];

    #[Select(foreignTableWhere: 'AND {#tx_contactbook_domain_model_contact}.{#type} = "company"', linkedModel: Contact::class, prependItem: Job::EMPTY_DEFAULT_ITEM)]
    protected ?Contact $company = null;

    #[Select(foreignTableWhere: 'AND {#tx_contactbook_domain_model_contact}.{#type} = "person"', linkedModel: Contact::class, maxItems: 1)]
    protected ?Contact $contact = null;

    #[Input]
    protected string $position = '';

    #[Input]
    protected string $role = '';

    public function addContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    /** @return Contact[] */
    public function getColleagues(): array
    {
        $colleagues = [];

        if (null === $this->getCompany()) {
            return $colleagues;
        }

        foreach ($this->getCompany()
                     ->getEmployments() as $companyJob) {
            // Ensure that we do not add duplicates or the current contact itself!
            if (!in_array($companyJob->getContact(), $colleagues, true) && $companyJob->getContact()
                    ->getUid() !== $this->getContact()
                    ->getUid()) {
                $colleagues[] = $companyJob->getContact();
            }
        }

        return ContactUtility::sortByListName(...$colleagues);
    }

    public function getCompactValue(): string
    {
        $values = [];

        if ($this->company) {
            $values[] = $this->company->getCompanyName();
        }

        if ($this->position) {
            $values[] = $this->position;
        }

        if ($this->role) {
            $values[] = $this->role;
        }

        if (empty($values)) {
            return '';
        }

        return implode(', ', $values);
    }

    public function getCompany(): ?Contact
    {
        return $this->company;
    }

    /**
     * @throws JsonException
     */
    public function getCompanyUidAsJson(): false|string
    {
        if ($this->company instanceof Contact) {
            return JsonUtility::getUidsAsJsonArray($this->getCompany());
        }

        return '[]';
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setCompany(?Contact $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
