<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\File;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class EMail
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label: 'notes', searchFields: [
    'notes',
])]
class Document extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'download';
    public const string TYPE_ICON_IDENTIFIER   = 'file-earmark-text';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_documents', mmOppositeField: 'documents', renderType: SelectRenderType::selectMultipleSideBySide)]
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
    ],     maxItems: 1, uploadDuplicationBehaviour: DuplicationBehavior::RENAME, uploadFileNameGeneratorPartSeparator: '_', uploadFileNameGeneratorPrefix: 'document', uploadFileNameGeneratorProperties: ['uid'], uploadFileNameGeneratorSuffix: 'file', uploadTargetFolder: 'user_upload/psb_contacts/documents',)]
    protected ?FileReference $file = null;

    #[Input]
    protected string $title = '';

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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
