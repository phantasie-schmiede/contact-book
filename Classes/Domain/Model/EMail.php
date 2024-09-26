<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class EMail
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label: 'eMailAddress', searchFields: [
    'eMailAddress',
    'notes',
])]
class EMail extends AbstractContactRelatedEntity
{
    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_emails', mmOppositeField: 'eMails', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $eMailAddress = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected GeneralType $type = GeneralType::Private;

    public function getEMailAddress(): string
    {
        return $this->eMailAddress;
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

    public function getCompactValue(): string
    {
        return $this->eMailAddress;
    }
}
