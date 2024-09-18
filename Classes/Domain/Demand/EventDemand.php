<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Demand;

use DateTime;
use PSB\PsbContacts\Domain\Enum\EventType;

/**
 * Class EventDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
 */
class EventDemand
{
    protected ?DateTime $minDate           = null;
    protected ?bool     $showAnniversaries = null;
    protected ?bool     $showInCalendar    = null;

    protected array $types = [];

    public function addType(EventType $eventType): self
    {
        $this->types[] = $eventType;

        return $this;
    }

    public function getMinDate(): ?DateTime
    {
        return $this->minDate;
    }

    public function getShowAnniversaries(): ?bool
    {
        return $this->showAnniversaries;
    }

    public function getShowInCalendar(): ?bool
    {
        return $this->showInCalendar;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function setMinDate(?DateTime $minDate): self
    {
        $this->minDate = $minDate;

        return $this;
    }

    public function setShowAnniversaries(?bool $showAnniversaries): self
    {
        $this->showAnniversaries = $showAnniversaries;

        return $this;
    }

    public function setShowInCalendar(?bool $showInCalendar): self
    {
        $this->showInCalendar = $showInCalendar;

        return $this;
    }
}
