<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use InvalidArgumentException;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Utility\NameUtility;
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
    'middleNames',
    'notes',
])]
class Contact extends AbstractEntity
{
    /** @var ObjectStorage<Address> */
    #[Inline(linkedModel: Address::class, mm: 'tx_psbcontacts_contacts_addresses')]
    protected ObjectStorage $addresses;

    /** @var ObjectStorage<Contact> */
    #[Select(foreignTableWhere: 'AND {#tx_psbcontacts_domain_model_contact}.{#type} = "company"', linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_companies')]
    protected ObjectStorage $companies;

    #[Input]
    protected string $companyName = '';

    /** @var ObjectStorage<EMail> */
    #[Inline(linkedModel: EMail::class, mm: 'tx_psbcontacts_contacts_emails')]
    protected ObjectStorage $eMails;

    /** @var ObjectStorage<Event> */
    #[Inline(linkedModel: Event::class, mm: 'tx_psbcontacts_contacts_events')]
    protected ObjectStorage $events;

    #[Input]
    protected string $firstName = '';

    #[Input]
    protected string $lastName = '';

    #[Input]
    protected string $middleNames = '';

    #[Input]
    protected string $nickname = '';

    /**
     * "@var" is needed for the PropertyMapper!
     * Otherwise, array is assumed because of an existing add*-method...
     * Blame symfony's ReflectionExtractor for this!
     *
     * @var string $notes
     */
    #[Text]
    protected string $notes = '';

    /** @var ObjectStorage<PhoneNumber> */
    #[Inline(linkedModel: PhoneNumber::class, mm: 'tx_psbcontacts_contacts_phonenumbers')]
    protected ObjectStorage $phoneNumbers;

    #[Check]
    protected bool $showInAddressBook = false;

    #[Select(items   : [
        'Company' => 'company',
        'Person'  => 'person',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected ContactType $type = ContactType::Person;

    /** @var ObjectStorage<Website> */
    #[Inline(linkedModel: Website::class, mm: 'tx_psbcontacts_contacts_websites')]
    protected ObjectStorage $websites;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function addAddress(Address $address): self
    {
        $this->addresses->attach($address);

        return $this;
    }

    public function addCompany(Contact $company): self
    {
        $this->companies->attach($company);

        return $this;
    }

    public function addEMail(EMail $eMail): self
    {
        $this->eMails->attach($eMail);

        return $this;
    }

    public function addEvent(Event $event): self
    {
        $this->events->attach($event);

        return $this;
    }

    public function addNote(string $note): self
    {
        $this->notes .= LF . $note;

        return $this;
    }

    public function addPhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumbers->attach($phoneNumber);

        return $this;
    }

    public function addWebsite(Website $website): self
    {
        $this->websites->attach($website);

        return $this;
    }

    public function getAddressByUid(int $uid): Address
    {
        foreach ($this->addresses as $address) {
            if ($uid === $address->getUid()) {
                return $address;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Address with UID ' . $uid . ' not found!', 1724164780);
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
        if (ContactType::Company->value === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $displayName = '';

        if ('' !== $this->getLastName()) {
            $displayName = $this->getLastName();

            if ('' !== $this->getFirstName()) {
                $displayName .= ', ' . $this->getFirstName();
            }
        } elseif ('' !== $this->getFirstName()) {
            $displayName = $this->getFirstName();

            if ('' !== $this->getMiddleNames()) {
                $displayName .= ' ' . $this->getMiddleNames();
            }
        }

        return $displayName;
    }

    public function getEMailByUid(int $uid): EMail
    {
        foreach ($this->eMails as $eMail) {
            if ($uid === $eMail->getUid()) {
                return $eMail;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': E-Mail with UID ' . $uid . ' not found!', 1724164830);
    }

    public function getEMails(): ObjectStorage
    {
        return $this->eMails;
    }

    public function getEventByUid(int $uid): Event
    {
        foreach ($this->events as $event) {
            if ($uid === $event->getUid()) {
                return $event;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Event with UID ' . $uid . ' not found!', 1720095212);
    }

    public function getEvents(): ObjectStorage
    {
        return $this->events;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getFullName(): string
    {
        if (ContactType::Company->value === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $nameParts = [];

        if ('' !== $this->getFirstName()) {
            $nameParts[] = $this->getFirstName();
        }

        if ('' !== $this->getMiddleNames()) {
            $nameParts[] = $this->getMiddleNames();
        }

        if ('' !== $this->getLastName()) {
            $nameParts[] = $this->getLastName();
        }

        return implode(' ', $nameParts);
    }

    public function getGenitiveForm(): string
    {
        if (ContactType::Company->value === $this->type && '' !== $this->getCompanyName()) {
            $name = $this->getCompanyName();
        } else {
            $name = $this->getFirstName();

            if ('' !== $this->getMiddleNames()) {
                $name .= ' ' . $this->getMiddleNames();
            }
        }

        return NameUtility::getGenitiveForm($name);
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getMiddleNames(): string
    {
        return $this->middleNames;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getPhoneNumberByUid(int $uid): PhoneNumber
    {
        foreach ($this->phoneNumbers as $phoneNumber) {
            if ($uid === $phoneNumber->getUid()) {
                return $phoneNumber;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Phone number with UID ' . $uid . ' not found!', 1724164856);
    }

    public function getPhoneNumbers(): ObjectStorage
    {
        return $this->phoneNumbers;
    }

    public function getType(): ContactType
    {
        return $this->type;
    }

    public function getWebsiteByUid(int $uid): Website
    {
        foreach ($this->websites as $website) {
            if ($uid === $website->getUid()) {
                return $website;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Website with UID ' . $uid . ' not found!', 1724164873);
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

    public function removeAddress(Address $address): self
    {
        $this->addresses->detach($address);

        return $this;
    }

    public function removeEMail(EMail $eMail): self
    {
        $this->eMails->detach($eMail);

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        $this->events->detach($event);

        return $this;
    }

    public function removePhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumbers->detach($phoneNumber);

        return $this;
    }

    public function removeWebsite(Website $website): self
    {
        $this->websites->detach($website);

        return $this;
    }

    public function setAddresses(ObjectStorage $addresses): self
    {
        $this->addresses = $addresses;

        return $this;
    }

    public function setCompanies(ObjectStorage $companies): self
    {
        $this->companies = $companies;

        return $this;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function setEMails(ObjectStorage $eMails): self
    {
        $this->eMails = $eMails;

        return $this;
    }

    public function setEvents(ObjectStorage $events): self
    {
        $this->events = $events;

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

    public function setMiddleNames(string $middleNames): self
    {
        $this->middleNames = $middleNames;

        return $this;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function setPhoneNumbers(ObjectStorage $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    public function setShowInAddressBook(bool $showInAddressBook): self
    {
        $this->showInAddressBook = $showInAddressBook;

        return $this;
    }

    public function setType(ContactType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setWebsites(ObjectStorage $websites): self
    {
        $this->websites = $websites;

        return $this;
    }
}
