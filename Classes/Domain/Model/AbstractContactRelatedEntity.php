<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Utility\StringUtility;
use ReflectionClass;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class AbstractContactRelatedEntity
 *
 * @package PSB\PsbContacts\Domain\Model
 */
abstract class AbstractContactRelatedEntity extends AbstractEntity implements ContactRelationInterface
{
    /* Override this constant in your concrete class. */
    public const string ACTION_ICON_IDENTIFIER = '';

    protected ObjectStorage $contacts;

    #[Text]
    protected string $notes = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function addContact(Contact $contact): self
    {
        $this->contacts->attach($contact);

        return $this;
    }

    public function getActionIconIdentifier(): string
    {
        return static::ACTION_ICON_IDENTIFIER;
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getContactsSorted(): array
    {
        return ContactUtility::sortByDisplayName($this->contacts->toArray());
    }

    public function getNotes(): string
    {
        return StringUtility::isEmpty($this->notes) ? '' : $this->notes;
    }

    public function getRecordType(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function removeAllContacts(): self
    {
        $this->contacts = new ObjectStorage();

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        $this->contacts->detach($contact);

        return $this;
    }

    public function setContacts(ObjectStorage $contacts): self
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = htmlspecialchars(trim($notes));

        return $this;
    }
}
