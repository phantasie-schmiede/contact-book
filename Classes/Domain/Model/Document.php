<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\Check;
use PSBits\Foundation\Attribute\TCA\ColumnType\File;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

#[Ctrl(label: 'notes', searchFields: [
    'notes',
])]
class Document extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'download';
    public const string TYPE_ICON_IDENTIFIER   = 'file-earmark-text';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_documents', mmOppositeField: 'documents', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[File(allowed : [
        'common-image-types',
        'common-media-types',
        'doc',
        'docx',
        'odt',
        'pdf',
        'rtf',
        'txt',
    ], maxItems: 1, uploadDuplicationBehaviour: DuplicationBehavior::RENAME, uploadFileNameGeneratorPartSeparator: '_', uploadFileNameGeneratorPrefix: 'document', uploadFileNameGeneratorProperties: ['uid'], uploadFileNameGeneratorSuffix: 'file', uploadTargetFolder: 'user_upload/contact_book/documents', )]
    protected ?FileReference $file = null;

    #[Input]
    protected string $title = '';

    #[Check]
    protected bool $useAsProfileImage = false;

    public function getCompactValue(): string
    {
        return $this->getTitle() ?: $this->notes ?: $this->file?->getOriginalResource()
            ->getName() ?: (string)$this->getUid();
    }

    public function getFile(): FileReference
    {
        return $this->file;
    }

    public function getLink(): string
    {
        return $this->file->getOriginalResource()
            ->getPublicUrl();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTypeIconIdentifier(): string
    {
        return self::TYPE_ICON_IDENTIFIER;
    }

    public function isUseAsProfileImage(): bool
    {
        return $this->useAsProfileImage;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setUseAsProfileImage(bool $useAsProfileImage): self
    {
        $this->useAsProfileImage = $useAsProfileImage;

        return $this;
    }
}
