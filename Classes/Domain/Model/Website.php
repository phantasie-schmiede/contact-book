<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Website
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl]
class Website extends AbstractEntity
{
    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_websites', mmOppositeField: 'websites')]
    protected ObjectStorage $contacts;

    #[Text]
    protected string $notes = '';

    #[Select(items   : [
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $type = 'private';

    #[Input]
    protected string $url = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
