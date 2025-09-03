<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use InvalidArgumentException;
use JsonException;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\ContactBook\Utility\JsonUtility;
use PSBits\ContactBook\Utility\NameUtility;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Slug;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Utility\StringUtility;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Contact
 *
 * @package PSBits\ContactBook\Domain\Model
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
    #[Inline(linkedModel: Address::class, mm: 'tx_contactbook_contacts_addresses')]
    #[Lazy]
    protected ObjectStorage $addresses;

    #[Column(position: 'after:nickname')]
    #[Input]
    protected string $birthName = '';

    #[Input]
    protected string $companyName = '';

    /** @var ObjectStorage<ContactGroup> */
    #[Inline(linkedModel: ContactGroup::class, mm: 'tx_contactbook_contacts_contactgroups')]
    #[Lazy]
    protected ObjectStorage $contactGroups;

    /** @var ObjectStorage<Document> */
    #[Inline(linkedModel: Document::class, mm: 'tx_contactbook_contacts_documents')]
    #[Lazy]
    protected ObjectStorage $documents;

    /** @var ObjectStorage<EMail> */
    #[Inline(linkedModel: EMail::class, mm: 'tx_contactbook_contacts_emails')]
    #[Lazy]
    protected ObjectStorage $eMails;

    /** @var ObjectStorage<Job> */
    #[Inline(foreignField: 'company', linkedModel: Job::class)]
    #[Lazy]
    protected ObjectStorage $employments;

    /** @var ObjectStorage<Event> */
    #[Inline(linkedModel: Event::class, mm: 'tx_contactbook_contacts_events')]
    #[Lazy]
    protected ObjectStorage $events;

    #[Column(position: 'after:title')]
    #[Input]
    protected string $firstName = '';

    /** @var ObjectStorage<Job> */
    #[Inline(foreignField: 'contact', linkedModel: Job::class)]
    #[Lazy]
    protected ObjectStorage $jobs;

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
    #[Inline(linkedModel: PhoneNumber::class, mm: 'tx_contactbook_contacts_phonenumbers')]
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
    #[Inline(linkedModel: Website::class, mm: 'tx_contactbook_contacts_websites')]
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

    public function addJob(Job $job): self
    {
        $this->jobs->attach($job);

        return $this;
    }

    public function addJobs(Contact ...$jobs): self
    {
        foreach ($jobs as $job) {
            if (!$this->jobs->contains($job)) {
                $this->jobs->attach($job);
            }
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

    /** @return ObjectStorage<Address> */
    public function getAddresses(): ObjectStorage
    {
        return $this->addresses;
    }

    public function getBirthName(): string
    {
        return $this->birthName;
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

    /**
     * @throws JsonException
     */
    public function getContactGroupUidsAsJson(): false|string
    {
        return JsonUtility::getUidsAsJsonArray(...$this->getContactGroups());
    }

    /** @return ObjectStorage<ContactGroup> */
    public function getContactGroups(): ObjectStorage
    {
        return $this->contactGroups;
    }

    public function getDecoratedListName(): string
    {
        $decorations = '';

        if (!$this->isAlive()) {
            $decorations .= '† ';
        }

        return $decorations . $this->getListName();
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

    /** @return ObjectStorage<Document> */
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

    /** @return ObjectStorage<EMail> */
    public function getEMails(): ObjectStorage
    {
        return $this->eMails;
    }

    /** @return ObjectStorage<Job> */
    public function getEmployments(): ObjectStorage
    {
        return $this->employments;
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

    /** @return ObjectStorage<Event> */
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

    public function getJobByUid(int $uid): Job
    {
        foreach ($this->jobs as $job) {
            if ($uid === $job->getUid()) {
                return $job;
            }
        }

        throw new InvalidArgumentException(__CLASS__ . ': Job with UID ' . $uid . ' was not found!', 1729023370);
    }

    /** @return ObjectStorage<Job> */
    public function getJobs(): ObjectStorage
    {
        return $this->jobs;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getListName(): string
    {
        if (ContactType::Company === $this->type && '' !== $this->getCompanyName()) {
            return $this->getCompanyName();
        }

        $listName = '';

        if ('' !== $this->getLastName()) {
            $listName .= $this->getLastName();

            if ('' !== $this->getFirstName()) {
                $listName .= ', ' . $this->getFirstName();
            }
        } elseif ('' !== $this->getFirstName()) {
            $listName .= $this->getFirstNames();
        }

        return $listName;
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

            $birthEvent = ContactUtility::getBirthEvent($this);

            if (null !== $birthEvent?->getYear()) {
                $searchTerms[] = $birthEvent->getYear();
            }

            foreach ($this->jobs as $job) {
                if (null !== $job->getCompany()) {
                    $searchTerms[] = $job->getCompany()
                        ->getFullName();
                }

                if ('' !== $job->getPosition()) {
                    $searchTerms[] = $job->getPosition();
                }

                if ('' !== $job->getRole()) {
                    $searchTerms[] = $job->getRole();
                }
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

    /**
     * @return Job[]
     */
    public function getSortedEmployments(): array
    {
        $jobs = $this->employments->toArray();

        usort(
            $jobs,
            static fn(Job $a, Job $b) => strnatcasecmp(
            NameUtility::getCompareString(
                $a->getContact()
                    ->getListName()
            ),
            NameUtility::getCompareString(
                $b->getContact()
                    ->getListName()
            )
        )
        );

        return $jobs;
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

    /**
     * Checks if the contact has at least one set of information that is relevant on another device like a phone or
     * tablet.
     */
    public function hasExportRelevantInformation(): bool
    {
        if (!$this->isAlive()) {
            return false;
        }

        // @formatter:off
        return 0 < $this->addresses->count()
            || 0 < $this->eMails->count()
            || 0 < $this->phoneNumbers->count()
            || 0 < $this->websites->count()
            || null !== ContactUtility::getBirthEvent($this);
        // @formatter:on
    }

    public function initializeObject(): void
    {
        $this->addresses = new ObjectStorage();
        $this->contactGroups = new ObjectStorage();
        $this->documents = new ObjectStorage();
        $this->eMails = new ObjectStorage();
        $this->events = new ObjectStorage();
        $this->jobs = new ObjectStorage();
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

    public function isOfTypeCompany(): bool
    {
        return ContactType::Company === $this->type;
    }

    public function isOfTypePerson(): bool
    {
        return ContactType::Person === $this->type;
    }

    public function removeAddress(Address $address): self
    {
        $this->addresses->detach($address);

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

    public function removeJob(Job $job): self
    {
        $this->jobs->detach($job);

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

    public function setEmployments(ObjectStorage $employments): self
    {
        $this->employments = $employments;

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

    public function setJobs(ObjectStorage $jobs): self
    {
        $this->jobs = $jobs;

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
