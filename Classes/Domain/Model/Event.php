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
use JsonException;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Check;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Datetime as TcaDatetime;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use PSB\PsbTemplates\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Event
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label: 'date', searchFields: [
    'date',
    'notes',
])]
class Event extends AbstractContactRelatedEntity
{
    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_psbcontacts_contacts_events', mmOppositeField: 'events', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[TcaDatetime]
    protected DateTime $date;

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
    protected EventType $type = EventType::Birthday;

    #[Check]
    protected bool $yearUnknown = false;

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getCompactValue(): string
    {
        return $this->getLabel() . ': ' . $this->date->format('d.m.Y');
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getContactsAsEnumeration(): string
    {
        $names = [];

        foreach ($this->contacts as $contact) {
            $names[] = $contact->getFullName();
        }

        return StringUtility::createEnumeration($names);
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getLabel(): string
    {
        if (EventType::Other === $this->type) {
            return $this->notes;
        }

        return $this->type->getLabel();
    }

    public function getNextAnniversary(): int
    {
        if ($this->isYearUnknown()) {
            return 0;
        }

        $now = new DateTime();
        $anniversary = (int)$now->format('Y') - (int)$this->date->format('Y');

        if (1 > $anniversary) {
            return 0;
        }

        if ($now->format('md') > $this->date->format('md')) {
            $anniversary++;
        }

        return $anniversary;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function isAnniversary(): bool
    {
        return $this->isYearUnknown() || date('Y') > $this->date->format('Y');
    }

    public function isShowAnniversaries(): bool
    {
        return $this->showAnniversaries;
    }

    public function isShowInCalendar(): bool
    {
        return $this->showInCalendar;
    }

    public function isYearUnknown(): bool
    {
        return $this->yearUnknown;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setShowAnniversaries(bool $showAnniversaries): self
    {
        $this->showAnniversaries = $showAnniversaries;

        return $this;
    }

    public function setShowInCalendar(bool $showInCalendar): self
    {
        $this->showInCalendar = $showInCalendar;

        return $this;
    }

    public function setType(EventType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setYearUnknown(bool $yearUnknown): void
    {
        $this->yearUnknown = $yearUnknown;
    }
}
