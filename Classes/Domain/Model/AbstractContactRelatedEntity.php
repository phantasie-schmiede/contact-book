<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class AbstractContactRelatedEntity
 *
 * @package PSB\PsbContacts\Domain\Model
 */
abstract class AbstractContactRelatedEntity extends AbstractEntity implements ContactRelationInterface
{
    protected ObjectStorage $contacts;

    #[Text]
    protected string $notes = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function addContact(Contact $contact): void
    {
        $this->contacts->attach($contact);
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function removeAllContacts(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function removeContact(Contact $contact): void
    {
        $this->contacts->detach($contact);
    }

    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = htmlspecialchars($notes);
    }
}
