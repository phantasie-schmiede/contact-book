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
use PSBits\ContactBook\Domain\Model\Contact;
use PSB\PsbTemplates\Event\AbstractAdditionalContentEvent;

/**
 * Class AdditionalContactContentEvent
 *
 * @package PSBits\ContactBook\Event
 */
class AdditionalContactContentEvent extends AbstractAdditionalContentEvent
{
    protected Contact $contact;

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setParameters(array $parameters): static
    {
        if (!isset($parameters['contact']) || !$parameters['contact'] instanceof Contact) {
            throw new InvalidArgumentException(__CLASS__ . ': Missing or invalid contact parameter!', 1721047163);
        }

        $this->contact = $parameters['contact'];

        return $this;
    }
}
