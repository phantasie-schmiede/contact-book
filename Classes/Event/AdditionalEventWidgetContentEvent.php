<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Event;

use InvalidArgumentException;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbTemplates\Event\AbstractAdditionalContentEvent;

/**
 * Class AdditionalEventWidgetContentEvent
 *
 * @package PSB\PsbContacts\Event
 */
class AdditionalEventWidgetContentEvent extends AbstractAdditionalContentEvent
{
    protected Event $event;

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setParameters(array $parameters): static
    {
        if (!isset($parameters['event']) || !$parameters['event'] instanceof Event) {
            throw new InvalidArgumentException(__CLASS__ . ': Missing or invalid event parameter!', 1729146424);
        }

        $this->event = $parameters['event'];

        return $this;
    }
}
