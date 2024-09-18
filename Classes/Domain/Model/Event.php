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
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
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
    protected string $type = 'birthday';

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws InvalidConfigurationTypeException
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

    public function getLabel(): string
    {
        if (EventType::Other->value === $this->type) {
            return $this->notes;
        }

        return $this->type;
    }

    public function getNextAnniversary(): int
    {
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

    public function getType(): string
    {
        return $this->type;
    }

    public function isAnniversary(): bool
    {
        return date('Y') > $this->date->format('Y');
    }

    public function isShowAnniversaries(): bool
    {
        return $this->showAnniversaries;
    }

    public function isShowInCalendar(): bool
    {
        return $this->showInCalendar;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
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
