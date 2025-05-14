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
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Utility\NameUtility;
use PSB\PsbFoundation\Attribute\TCA\Column;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Inline;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Slug;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use PSB\PsbFoundation\Utility\StringUtility;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
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
    public const array SLUG_GENERATOR_OPTIONS = [
        'fallbackCharacter'    => '',
        'fieldSeparator'       => '-',
        'fields'               => [
            'company_name',
            'title',
            'first_name',
            'middle_names',
            'last_name',
        ],
        'prefixParentPageSlug' => true,
        'replacements'         => [
            ' ' => '-',
            '/' => '',
            '.' => '-',
        ],
    ];

    /** @var ObjectStorage<Address> */
    #[Inline(linkedModel: Address::class, mm: 'tx_psbcontacts_contacts_addresses')]
    #[Lazy]
    protected ObjectStorage $addresses;

    #[Column(position: 'after:nickname')]
    #[Input]
    protected string $birthName = '';

    /** @var ObjectStorage<Contact> */
    #[Select(foreignTableWhere: 'AND {#tx_psbcontacts_domain_model_contact}.{#type} = "company"', linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_companies', renderType: SelectRenderType::selectMultipleSideBySide)]
    #[Lazy]
    protected ObjectStorage $companies;

    #[Input]
    protected string $companyName = '';

    /** @var ObjectStorage<ContactGroup> */
    #[Inline(linkedModel: ContactGroup::class, mm: 'tx_psbcontacts_contacts_contactgroups')]
    #[Lazy]
    protected ObjectStorage $contactGroups;

    /** @var ObjectStorage<Document> */
    #[Inline(linkedModel: Document::class, mm: 'tx_psbcontacts_contacts_documents')]
    #[Lazy]
    protected ObjectStorage $documents;

    /** @var ObjectStorage<EMail> */
    #[Inline(linkedModel: EMail::class, mm: 'tx_psbcontacts_contacts_emails')]
    #[Lazy]
    protected ObjectStorage $eMails;

    /** @var ObjectStorage<Event> */
    #[Inline(linkedModel: Event::class, mm: 'tx_psbcontacts_contacts_events')]
    #[Lazy]
    protected ObjectStorage $events;

    #[Column(position: 'after:title')]
    #[Input]
    protected string $firstName = '';

    #[Column(position: 'after:middleNames')]
    #[Input]
    protected string $lastName = '';

    #[Column(position: 'after:firstName')]
    #[Input]
    protected string $middleNames = '';

    #[Column(position: 'after:lastName')]
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
    #[Lazy]
    protected ObjectStorage $phoneNumbers;

    #[Column(position: 'after:notes')]
    #[Slug(generatorOptions: self::SLUG_GENERATOR_OPTIONS)]
    protected string $slug = '';

    #[Column(position: 'after:type')]
    #[Input]
    protected string $title = '';

    #[Select(items   : [
        'Company' => 'company',
        'Person'  => 'person',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected ContactType $type = ContactType::Person;

    #[Column(position: 'after:slug', readOnly: true)]
    #[Input(eval: 'unique')]
    protected string $uuid = '';

    /** @var ObjectStorage<Website> */
    #[Inline(linkedModel: Website::class, mm: 'tx_psbcontacts_contacts_websites')]
    #[Lazy]
    protected ObjectStorage $websites;

    public function __construct()
    {
        if ('' === $this->uuid) {
            $this->setUuid();
        }

        $this->initializeObject();
    }

    public function addAddresses(Address ...$addresses): self
    {
        foreach ($addresses as $address) {
            if (!$this->addresses->contains($address)) {
                $this->addresses->attach($address);
            }
        }

        return $this;
    }

    public function addCompanies(Contact ...$companies): self
    {
        foreach ($companies as $company) {
            if (!$this->companies->contains($company)) {
                $this->companies->attach($company);
            }
        }

        return $this;
    }

    public function addCompany(Contact $company): self
    {
        $this->companies->attach($company);

        return $this;
    }

    public function addContactGroup(ContactGroup $contactGroup): self
    {
        $this->contactGroups->attach($contactGroup);

        return $this;
    }

    public function addEMails(EMail ...$eMails): self
    {
        foreach ($eMails as $eMail) {
            if (!$this->eMails->contains($eMail)) {
                $this->eMails->attach($eMail);
            }
        }

        return $this;
    }

    public function addEvents(Event ...$events): self
    {
        foreach ($events as $event) {
            foreach ($this->events as $existingEvent) {
                if ($event->getType() === $existingEvent->getType() && $event->getDate()
                        ->format('m.d.Y') === $existingEvent->getDate()
                        ->format('m.d.Y')) {
                    continue 2;
                }
            }

            $this->events->attach($event);
        }

        return $this;
    }

    public function addNote(string $note): self
    {
        $note = trim($note, StringUtility::EMPTY_CHARACTERS);

        if (!empty($note)) {
            $this->notes .= LF . $note;
        }

        return $this;
    }

    public function addPhoneNumbers(PhoneNumber ...$phoneNumbers): self
    {
        foreach ($phoneNumbers as $phoneNumber) {
            if (!$this->phoneNumbers->contains($phoneNumber)) {
                $this->phoneNumbers->attach($phoneNumber);
            }
        }

        return $this;
    }

    public function addWebsites(Website ...$websites): self
    {
        foreach ($websites as $website) {
            if (!$this->websites->contains($website)) {
                $this->websites->attach($website);
            }
        }

        return $this;
    }

    public function getAddressByUid(int $uid): Address
    {
        foreach ($this->addresses as $address) {
            if ($uid === $address->getUid()) {
                return $address;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Address with UID ' . $uid . ' was not found!', 1724164780);
    }

    public function getAddresses(): ObjectStorage
    {
        return $this->addresses;
    }

    public function getBirthName(): string
    {
        return $this->birthName;
    }

    public function getCompanies(): ObjectStorage
    {
        return $this->companies;
    }

    public function getCompanyByUid(int $uid): Contact
    {
        foreach ($this->companies as $company) {
            if ($uid === $company->getUid()) {
                return $company;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Company with UID ' . $uid . ' was not found!', 1729023370);
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getContactGroupByUid(int $uid): EMail
    {
        foreach ($this->contactGroups as $contactGroup) {
            if ($uid === $contactGroup->getUid()) {
                return $contactGroup;
            }
        }

        throw new InvalidArgumentException(
            __CLASS__ . ': Contact group with UID ' . $uid . ' was not found!', 1728419001
        );
    }

    public function getContactGroups(): ObjectStorage
    {
        return $this->contactGroups;
    }

    public function getDisplayName(): string
    {
        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $displayName = '';

        if ('' !== $this->getLastName()) {
            $displayName = $this->getLastName();

            if ('' !== $this->getFirstName()) {
                $displayName .= ', ' . $this->getFirstName();
            }
        } elseif ('' !== $this->getFirstName()) {
            $displayName = $this->getFirstNames();
        }

        return $displayName;
    }

    public function getDocumentByUid(int $uid): Document
    {
        foreach ($this->documents as $document) {
            if ($uid === $document->getUid()) {
                return $document;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Document with UID ' . $uid . ' was not found!', 1730819265);
    }

    public function getDocuments(): ObjectStorage
    {
        return $this->documents;
    }

    public function getDocumentsWithoutImages(): array
    {
        $documents = [];

        foreach ($this->documents as $document) {
            if (!str_starts_with(
                $document->getFile()
                    ->getOriginalResource()
                    ->getMimeType(),
                'image/'
            )) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    public function getEMailByUid(int $uid): EMail
    {
        foreach ($this->eMails as $eMail) {
            if ($uid === $eMail->getUid()) {
                return $eMail;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': E-Mail with UID ' . $uid . ' was not found!', 1724164830);
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

        throw new InvalidArgumentException(__CLASS__ . ': Event with UID ' . $uid . ' was not found!', 1720095212);
    }

    /**
     * @return ObjectStorage<Event>
     */
    public function getEvents(): ObjectStorage
    {
        return $this->events;
    }

    public function getEventsInDescendingOrder(): array
    {
        $events = $this->events->toArray();

        usort(
            $events,
            static function(Event $eventA, Event $eventB) {
            return $eventB->getDate() <=> $eventA->getDate();
        }
        );

        return $events;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getFirstNames(): string
    {
        $name = $this->getFirstName();

        if ('' !== $this->getMiddleNames()) {
            $name .= ' ' . $this->getMiddleNames();
        }

        return $name;
    }

    public function getFullName(): string
    {
        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $nameParts = [];

        if ('' !== $this->getTitle()) {
            $nameParts[] = $this->getTitle();
        }

        if ('' !== $this->getFirstName()) {
            $nameParts[] = $this->getFirstNames();
        }

        if ('' !== $this->getLastName()) {
            $nameParts[] = $this->getLastName();
        }

        return implode(' ', $nameParts);
    }

    public function getGenitiveForm(): string
    {
        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            $name = $this->getCompanyName();
        } else {
            $name = $this->getFirstNames();
        }

        return NameUtility::getGenitiveForm($name);
    }

    public function getImages(): array
    {
        $images = [];

        foreach ($this->documents as $document) {
            if (str_starts_with(
                $document->getFile()
                    ->getOriginalResource()
                    ->getMimeType(),
                'image/'
            )) {
                $images[] = $document;
            }
        }

        return $images;
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
        return StringUtility::isEmpty($this->notes) ? '' : $this->notes;
    }

    public function getPhoneNumberByUid(int $uid): PhoneNumber
    {
        foreach ($this->phoneNumbers as $phoneNumber) {
            if ($uid === $phoneNumber->getUid()) {
                return $phoneNumber;
            }
        }

        throw new InvalidArgumentException(
            __CLASS__ . ': Phone number with UID ' . $uid . ' was not found!', 1724164856
        );
    }

    /** @return ObjectStorage<PhoneNumber> */
    public function getPhoneNumbers(): ObjectStorage
    {
        return $this->phoneNumbers;
    }

    public function getSearchTerms(): string
    {
        $searchTerms = [
            'type:' . $this->type->value,
        ];

        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            $searchTerms[] = $this->getCompanyName();
        } else {
            if ('' !== $this->getBirthName()) {
                $searchTerms[] = $this->getBirthName();
            }

            if ('' !== $this->getFirstName()) {
                $searchTerms[] = $this->getFirstName();
            }

            if ('' !== $this->getLastName()) {
                $searchTerms[] = $this->getLastName();
            }

            if ('' !== $this->getMiddleNames()) {
                $searchTerms[] = $this->getMiddleNames();
            }

            if ('' !== $this->getNickname()) {
                $searchTerms[] = $this->getNickname();
            }

            foreach ($this->companies as $company) {
                $searchTerms[] = $company->getCompanyName();
            }
        }

        foreach ($this->addresses as $address) {
            $searchTerms[] = str_replace(',', '', $address->getCompactValue());
        }

        foreach ($this->eMails as $eMail) {
            $searchTerms[] = $eMail->getEMailAddress();
        }

        if ('' !== $this->getNotes()) {
            array_push($searchTerms, ...StringUtility::explodeByLineBreaks($this->getNotes()));
        }

        foreach ($this->websites as $website) {
            $searchTerms[] = $website->getUrl();
        }

        foreach ($this->getPhoneNumbers() as $phoneNumber) {
            $searchTerms[] = 'phoneType:' . $phoneNumber->getPhoneNumberType()->value;
            $searchTerms[] = $phoneNumber->getCompleteNumber();
        }

        return implode(' ', array_unique($searchTerms));
    }

    public function getShortName(): string
    {
        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $nameParts = [];

        if ('' !== $this->getFirstName()) {
            $nameParts[] = $this->getFirstName();
        }

        if ('' !== $this->getLastName()) {
            $nameParts[] = $this->getLastName();
        }

        return implode(' ', $nameParts);
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): ContactType
    {
        return $this->type;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getWebsiteByUid(int $uid): Website
    {
        foreach ($this->websites as $website) {
            if ($uid === $website->getUid()) {
                return $website;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Website with UID ' . $uid . ' was not found!', 1724164873);
    }

    public function getWebsites(): ObjectStorage
    {
        return $this->websites;
    }

    public function hasPhoneNumber(): bool
    {
        return $this->phoneNumbers->count() > 0;
    }

    public function initializeObject(): void
    {
        $this->addresses = new ObjectStorage();
        $this->companies = new ObjectStorage();
        $this->contactGroups = new ObjectStorage();
        $this->documents = new ObjectStorage();
        $this->eMails = new ObjectStorage();
        $this->events = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
        $this->websites = new ObjectStorage();
    }

    public function isAlive(): bool
    {
        foreach ($this->events as $event) {
            if (EventType::Death === $event->getType()) {
                return false;
            }
        }

        return true;
    }

    public function removeAddress(Address $address): self
    {
        $this->addresses->detach($address);

        return $this;
    }

    public function removeAllCompanies(): self
    {
        $this->companies = new ObjectStorage();

        return $this;
    }

    public function removeAllContactGroups(): self
    {
        $this->contactGroups = new ObjectStorage();

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        $this->documents->detach($document);

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

    public function setBirthName(string $birthName): self
    {
        $this->birthName = $birthName;

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

    public function setContactGroups(ObjectStorage $contactGroups): self
    {
        $this->contactGroups = $contactGroups;

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
        $this->notes = htmlspecialchars(trim($notes));

        return $this;
    }

    public function setPhoneNumbers(ObjectStorage $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setType(ContactType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setUuid(): self
    {
        if ('' !== $this->uuid) {
            throw new RuntimeException(__CLASS__ . ': UUID is already set!', 1739963501);
        }

        $this->uuid = Uuid::uuid4()
            ->toString();

        return $this;
    }

    public function setWebsites(ObjectStorage $websites): self
    {
        $this->websites = $websites;

        return $this;
    }
}
