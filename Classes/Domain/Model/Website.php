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
 * Class Website
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label: 'url', searchFields: [
    'notes',
    'url',
])]
class Website extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'box-arrow-up-right';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_websites', mmOppositeField: 'websites', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected GeneralType $type = GeneralType::Private;

    #[Input]
    protected string $url = '';

    public function getCompactValue(): string
    {
        return $this->url;
    }

    public function getLink(): string
    {
        return $this->url;
    }

    public function getType(): GeneralType
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setType(GeneralType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
