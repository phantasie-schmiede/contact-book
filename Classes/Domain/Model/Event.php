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
use PSB\PsbContacts\Domain\Enum\Type;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Check;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Datetime as TcaDatetime;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
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
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_events', mmOppositeField: 'events')]
    protected ObjectStorage $contacts;

    #[TcaDatetime]
    protected DateTime $date;

    #[Text]
    protected string $notes = '';

    #[Check]
    protected bool $showAnniversaries = false;

    #[Check]
    protected bool $showInCalendar = false;

    #[Select(items   : [
        'Baptism'      => 'baptism',
        'Birthday'     => 'birthday',
        'Confirmation' => 'confirmation',
        'Death'        => 'death',
        'Divorce'      => 'divorce',
        'Wedding'      => 'wedding',
        'Other'        => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM)]
    protected string $type = 'birthday';

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

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function initializeObject(): void
    {
        $this->contacts = new ObjectStorage();
    }

    public function isShowAnniversaries(): bool
    {
        return $this->showAnniversaries;
    }

    public function isShowInCalendar(): bool
    {
        return $this->showInCalendar;
    }

    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function setShowAnniversaries(bool $showAnniversaries): void
    {
        $this->showAnniversaries = $showAnniversaries;
    }

    public function setShowInCalendar(bool $showInCalendar): void
    {
        $this->showInCalendar = $showInCalendar;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
