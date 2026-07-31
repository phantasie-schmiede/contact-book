<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use Exception;
use JsonException;
use PSBits\ContactBook\Utility\ContactBookUtility;
use PSBits\ContactBook\Utility\JsonUtility;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Slug;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class ContactGroup
 *
 * @package PSBits\ContactBook\Domain\Model
 */
#[Ctrl(label: 'name', searchFields: [
    'name',
    'notes',
])]
class ContactBook extends AbstractEntity
{
    public const array SLUG_GENERATOR_OPTIONS = [
        'fields'               => [
            'name',
        ],
        'prefixParentPageSlug' => true,
        'replacements'         => [
            ' ' => '-',
            '/' => '',
            '.' => '-',
        ],
    ];

    /** @var ObjectStorage<ContactGroup> */
    #[Cascade(['value' => 'remove'])]
    #[Inline(foreignField: 'contactBook', linkedModel: ContactGroup::class)]
    protected ObjectStorage $contactGroups;

    /** @var ObjectStorage<Contact> */
    #[Cascade(['value' => 'remove'])]
    #[Inline(foreignField: 'contactBook', linkedModel: Contact::class)]
    protected ObjectStorage $contacts;

    #[Column(required: true)]
    #[Input]
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $name = '';

    #[Text]
    protected string $notes = '';

    /** @var ObjectStorage<FrontendUser> */
    #[Select(linkedModel: FrontendUser::class, mm: 'tx_contactbook_contactbooks_feusers', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $owners;

    #[Slug(generatorOptions: self::SLUG_GENERATOR_OPTIONS)]
    protected string $slug = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function addOwner(FrontendUser $frontendUser): self
    {
        $this->owners->attach($frontendUser);

        return $this;
    }

    /**
     * @return ObjectStorage<ContactGroup>
     */
    public function getContactGroups(): ObjectStorage
    {
        return $this->contactGroups;
    }

    /** @return ObjectStorage<Contact> */
    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @return ObjectStorage<FrontendUser>
     * @throws Exception
     */
    public function getOtherOwners(): ObjectStorage
    {
        $currentUserUid = GlobalVariableService::get(FrontendUserProvider::class . '.user')
            ->getUid();
        $owners = clone $this->getOwners();

        foreach ($owners as $owner) {
            if ($owner->getUid() === $currentUserUid) {
                $owners->detach($owner);

                break;
            }
        }

        return $owners;
    }

    /**
     * @throws JsonException
     */
    public function getOwnerUidsAsJson(): false|string
    {
        return JsonUtility::getUidsAsJsonArray(...$this->getOwners());
    }

    /** @return ObjectStorage<FrontendUser> */
    public function getOwners(): ObjectStorage
    {
        return $this->owners;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
        $this->contactGroups = new ObjectStorage();
        $this->owners = new ObjectStorage();
    }

    /**
     * @throws JsonException
     */
    public function isActive(): bool
    {
        return ContactBookUtility::getActiveContactBookUid() === $this->getUid();
    }

    public function removeAllOwners(): self
    {
        $this->owners = new ObjectStorage();

        return $this;
    }

    /** @param ObjectStorage<ContactGroup> $contactGroups */
    public function setContactGroups(ObjectStorage $contactGroups): self
    {
        $this->contactGroups = $contactGroups;

        return $this;
    }

    /** @param ObjectStorage<Contact> $contacts */
    public function setContacts(ObjectStorage $contacts): self
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = htmlspecialchars(trim($notes));

        return $this;
    }

    /** @param ObjectStorage<FrontendUser> $owners */
    public function setOwners(ObjectStorage $owners): self
    {
        $this->owners = $owners;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
