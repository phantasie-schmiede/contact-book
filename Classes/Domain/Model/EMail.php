<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class EMail
 *
 * @package PSBits\ContactBook\Domain\Model
 */
#[Ctrl(label: 'eMailAddress', searchFields: [
    'eMailAddress',
    'notes',
])]
class EMail extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'envelope-at';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_emails', mmOppositeField: 'eMails', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $eMailAddress = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ], minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_STRING)]
    protected GeneralType $type = GeneralType::Private;

    public function getCompactValue(): string
    {
        return $this->eMailAddress;
    }

    public function getEMailAddress(): string
    {
        return $this->eMailAddress;
    }

    public function getLink(): string
    {
        return 'mailto:' . $this->eMailAddress;
    }

    public function getType(): GeneralType
    {
        return $this->type;
    }

    public function setEMailAddress(string $eMailAddress): self
    {
        $this->eMailAddress = $eMailAddress;

        return $this;
    }

    public function setType(GeneralType $type): self
    {
        $this->type = $type;

        return $this;
    }
}
