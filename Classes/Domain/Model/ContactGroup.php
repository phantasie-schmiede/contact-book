<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Slug;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Annotation\Validate;
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
class ContactGroup extends AbstractContactRelatedEntity
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

    #[Select(linkedModel: ContactBook::class, maxItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_INT)]
    protected ?ContactBook $contactBook = null;

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_contactgroups', mmOppositeField: 'contactGroups', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Column(required: true)]
    #[Input]
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $name = '';

    #[Slug(generatorOptions: self::SLUG_GENERATOR_OPTIONS)]
    protected string $slug = '';

    public function getContactBook(): ?ContactBook
    {
        return $this->contactBook;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setContactBook(?ContactBook $contactBook): self
    {
        $this->contactBook = $contactBook;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
