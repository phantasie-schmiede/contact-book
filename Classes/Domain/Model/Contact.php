<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\Inline;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
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

    /** @var ObjectStorage<Website> */
    #[Inline(linkedModel: Website::class)]
    protected ObjectStorage $websites;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function getAddresses(): ObjectStorage
    {
        return $this->addresses;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
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

    public function getWebsites(): ObjectStorage
    {
        return $this->websites;
    }

    public function initializeObject(): void
    {
        $this->addresses = new ObjectStorage();
        $this->eMails = new ObjectStorage();
        $this->events = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
        $this->websites = new ObjectStorage();
    }
}
