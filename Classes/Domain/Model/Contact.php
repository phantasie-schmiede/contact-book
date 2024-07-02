<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\Check;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Inline;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Contact
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label        : 'lastName', labelAlt: [
    'firstName',
    'companyName',
],     labelAltForce: true, searchFields: [
    'companyName',
    'firstName',
    'lastName',
])]
class Contact extends AbstractEntity
{
    /** @var ObjectStorage<Address> */
    #[Inline(linkedModel: Address::class)]
    protected ObjectStorage $addresses;

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_companies')]
    protected ObjectStorage $companies;

    #[Input]
    protected string $companyName = '';

    /** @var ObjectStorage<EMail> */
    #[Inline(linkedModel: EMail::class)]
    protected ObjectStorage $eMails;

    /** @var ObjectStorage<Event> */
    #[Inline(linkedModel: Event::class)]
    protected ObjectStorage $events;

    #[Input]
    protected string $firstName = '';

    #[Input]
    protected string $lastName = '';

    #[Input]
    protected string $middleNames = '';

    #[Text]
    protected string $notes = '';

    /** @var ObjectStorage<PhoneNumber> */
    #[Inline(linkedModel: PhoneNumber::class)]
    protected ObjectStorage $phoneNumbers;

    #[Check]
    protected bool $showInAddressBook = false;

    #[Select(items   : [
        'Company' => 'company',
        'Person'  => 'person',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $type = 'person';

    /** @var ObjectStorage<Website> */
    #[Inline(linkedModel: Website::class)]
    protected ObjectStorage $websites;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function addEvent(Event $event): void
    {
        $this->events->attach($event);
    }

    public function getAddresses(): ObjectStorage
    {
        return $this->addresses;
    }

    public function getCompanies(): ObjectStorage
    {
        return $this->companies;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getDisplayName(): string
    {
        $displayName = '';

        if ('' !== $this->getLastName()) {
            $displayName = $this->getLastName();

            if ('' !== $this->getFirstName()) {
                $displayName .= ', ' . $this->getFirstName();
            }
        } elseif ('' !== $this->getFirstName()) {
            $displayName = $this->getFirstName();
        } elseif ('' !== $this->getCompanyName()) {
            $displayName = $this->getCompanyName();
        }

        return $displayName;
    }

    public function getEMails(): ObjectStorage
    {
        return $this->eMails;
    }

    public function getEvents(): ObjectStorage
    {
        return $this->events;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getMiddleNames(): string
    {
        return $this->middleNames;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getPhoneNumbers(): ObjectStorage
    {
        return $this->phoneNumbers;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getWebsites(): ObjectStorage
    {
        return $this->websites;
    }

    public function initializeObject(): void
    {
        $this->addresses = new ObjectStorage();
        $this->companies = new ObjectStorage();
        $this->eMails = new ObjectStorage();
        $this->events = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
        $this->websites = new ObjectStorage();
    }

    public function setAddresses(ObjectStorage $addresses): void
    {
        $this->addresses = $addresses;
    }

    public function setCompanies(ObjectStorage $companies): void
    {
        $this->companies = $companies;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function setEMails(ObjectStorage $eMails): void
    {
        $this->eMails = $eMails;
    }

    public function setEvents(ObjectStorage $events): void
    {
        $this->events = $events;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setMiddleNames(string $middleNames): void
    {
        $this->middleNames = $middleNames;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setPhoneNumbers(ObjectStorage $phoneNumbers): void
    {
        $this->phoneNumbers = $phoneNumbers;
    }

    public function setShowInAddressBook(bool $showInAddressBook): void
    {
        $this->showInAddressBook = $showInAddressBook;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setWebsites(ObjectStorage $websites): void
    {
        $this->websites = $websites;
    }
}
