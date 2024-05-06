<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use DateTime;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Datetime as TcaDatetime;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Event
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl]
class Event extends AbstractEntity
{
    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_events', mmOppositeField: 'events', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[TcaDatetime]
    protected DateTime $date;

    #[Select(items      : [
        'Baptism'      => 'baptism',
        'Birthday'     => 'birthday',
        'Confirmation' => 'confirmation',
        'Death'        => 'death',
        'Divorce'      => 'divorce',
        'Wedding'      => 'wedding',
        'Other'        => 'other',
    ],       prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected EventType $eventType = EventType::Birthday;

    #[Text]
    protected string $notes = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }
}
