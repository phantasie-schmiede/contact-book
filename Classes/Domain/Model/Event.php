<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use DateTime;
use JsonException;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Check;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use PSBits\Foundation\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use function sprintf;

/**
 * Class Event
 *
 * @package PSBits\ContactBook\Domain\Model
 */
#[Ctrl(label        : 'year', labelAlt: [
    'type',
    'contacts',
],     labelAltForce: true, searchFields: [
    'notes',
    'year',
])]
class Event extends AbstractContactRelatedEntity
{
    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_events', mmOppositeField: 'events', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Column(databaseDefinition: 'INT UNSIGNED DEFAULT NULL', nullable: true)]
    #[Number(rangeLower: 1, rangeUpper: 31)]
    protected ?int $day = null;

    #[Text]
    protected string $location = '';

    #[Column(databaseDefinition: 'INT UNSIGNED DEFAULT NULL', nullable: true)]
    #[Number(rangeLower: 1, rangeUpper: 12)]
    protected ?int $month = null;

    #[Check]
    protected bool $showAnniversaries = false;

    #[Check]
    protected bool $showInCalendar = false;

    #[Select(items   : [
        'Baptism'          => 'baptism',
        'Birthday'         => 'birthday',
        'Confirmation'     => 'confirmation',
        'Death'            => 'death',
        'Divorce'          => 'divorce',
        'FirstDayAtSchool' => 'firstDayAtSchool',
        'Wedding'          => 'wedding',
        'Other'            => 'other',
    ],       minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_STRING)]
    protected EventType $type = EventType::Birthday;

    #[Column(databaseDefinition: 'INT DEFAULT NULL', nullable: true)]
    #[Number]
    protected ?int $year = null;

    public function getAnniversaryForYear(int $year): int
    {
        if (null === $this->year) {
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

    /** @return Contact[] */
    public function getContactsAsArray(): array
    {
        return $this->contacts->toArray();
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

    public function getLocation(): string
    {
        return implode(', ', StringUtility::explodeByLineBreaks($this->location));
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

        $currentMonth = (int)$now->format('m');
        $currentDay = (int)$now->format('d');

        if ($currentMonth > $this->month || ($currentMonth === $this->month && $currentDay > $this->day)) {
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

    public function hasDeceasedContact(): bool
    {
        foreach ($this->contacts as $contact) {
            if (!$contact->isAlive()) {
                return true;
            }
        }

        return false;
    }

    public function isAnniversary(): bool
    {
        return null === $this->year || ($this->year && date('Y') > $this->year);
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

    public function setDay(?int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

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
