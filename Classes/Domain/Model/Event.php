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
use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbFoundation\Attribute\TCA\Column;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Check;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Datetime as TcaDatetime;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Number;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Select;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use PSB\PsbFoundation\Enum\SelectRenderType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use function sprintf;

/**
 * Class Event
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label        : 'date', labelAlt: [
    'type',
    'contacts',
],     labelAltForce: true, searchFields: [
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

    #[Column(databaseDefinition: 'INT UNSIGNED DEFAULT NULL', nullable: true)]
    #[Number(rangeLower: 1, rangeUpper: 31)]
    protected ?int $day = null;

    #[Column(databaseDefinition: 'INT UNSIGNED DEFAULT NULL', nullable: true)]
    #[Number(rangeLower: 1, rangeUpper: 12)]
    protected ?int $month = null;

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

    #[Column(databaseDefinition: 'INT DEFAULT NULL', nullable: true)]
    #[Number]
    protected ?int $year = null;

    #[Check]
    protected bool $yearUnknown = false;

    public function getAnniversaryForYear(int $year): int
    {
        if ($this->isYearUnknown()) {
            return 0;
        }

        $anniversary = $year - $this->year;

        if (1 > $anniversary) {
            return 0;
        }

        return $anniversary;
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getCompactValue(): string
    {
        return $this->getLabel() . ': ' . $this->getDateFormatted();
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getContactsAsEnumeration(): string
    {
        return ContactUtility::createEnumeration($this->contacts->toArray());
    }

    public function getDateLegacy(): DateTime
    {
        return $this->date;
    }

    public function getDate(bool $useFallbacks = false): ?DateTime
    {
        if (!$useFallbacks && !$this->isDateComplete()) {
            return null;
        }

        $dateString = sprintf('%04d-%02d-%02d', $this->year ?? 0, $this->month ?? 1, $this->day ?? 1);

        return DateTime::createFromFormat('Y-m-d', $dateString);
    }

    public function getDateFormatted(): ?string
    {
        if ($this->isDateComplete()) {
            return $this->getDate()
                ?->format('d.m.Y');
        }

        if ($this->isMonthAndDayGiven()) {
            return $this->getDate(true)
                ?->format('d.m.');
        }

        if (null !== $this->year && null !== $this->month) {
            return $this->getDate(true)
                ?->format('m.Y');
        }

        if (null !== $this->year && null !== $this->day) {
            return $this->getDate(true)
                ?->format('d.??.Y');
        }

        if (null !== $this->year) {
            return (string)$this->year;
        }

        if (null !== $this->month) {
            return $this->getDate(true)
                ?->format('M');
        }

        return null;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getLabel(?int $year = null): string
    {
        if (EventType::Other === $this->type && !empty($this->notes)) {
            return $this->notes;
        }

        if (null !== $year) {
            $anniversary = $this->getAnniversaryForYear($year);

            if (0 < $anniversary) {
                return $anniversary . '. ' . $this->type->getLabel('anniversary');
            }
        }

        return $this->type->getLabel($this->isAnniversary() ? 'anniversary' : '');
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function getNextAnniversary(): int
    {
        if (!$this->isDateComplete()) {
            return 0;
        }

        $now = new DateTime();
        $anniversary = (int)$now->format('Y') - $this->year;

        if (1 > $anniversary) {
            return 0;
        }

        if ($now->format('m') > $this->month || ($now->format('m') === $this->month && $now->format(
                    'd'
                ) > $this->day)) {
            $anniversary++;
        }

        return $anniversary;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function isAnniversary(): bool
    {
        return $this->isYearUnknown() || date('Y') > $this->year;
    }

    public function isDateComplete(): bool
    {
        if (null === $this->year) {
            return false;
        }

        if (null === $this->month) {
            return false;
        }

        if (null === $this->day) {
            return false;
        }

        return true;
    }

    public function isMonthAndDayGiven(): bool
    {
        if (null === $this->month) {
            return false;
        }

        if (null === $this->day) {
            return false;
        }

        return true;
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
        if (property_exists($this, 'yearUnknown')) {
            return $this->yearUnknown;
        }

        return null === $this->year;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setDay(?int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function setMonth(?int $month): self
    {
        $this->month = $month;

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

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }
}
