<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Event;

use InvalidArgumentException;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\FrontendTemplates\Event\AbstractAdditionalContentEvent;

class AdditionalEventListContentEvent extends AbstractAdditionalContentEvent
{
    protected Event $event;

    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setParameters(array $parameters): static
    {
        if (!isset($parameters['event']) || !$parameters['event'] instanceof Event) {
            throw new InvalidArgumentException(__CLASS__ . ': Missing or invalid event parameter!', 1729146424);
        }

        $this->event = $parameters['event'];

        return $this;
    }
}
