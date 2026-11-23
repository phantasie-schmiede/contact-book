<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

use DateTime;
use PSBits\ContactBook\Domain\Enum\EventType;

class EventDemand
{
    protected ?DateTime $minDate           = null;
    protected ?bool     $showAnniversaries = null;
    protected ?bool     $showInCalendar    = null;

    /** @var EventType[] */
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

    /** @return EventType[] */
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
