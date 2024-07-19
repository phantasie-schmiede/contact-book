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
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbTemplates\Event\AdditionalContentEventInterface;

/**
 * Class EventController
 *
 * @package PSB\PsbContacts\Controller
 */
class AdditionalContactContentEvent implements AdditionalContentEventInterface
{
    protected Contact $contact;
    protected string  $content = '';

    public function addContent(string $content): void
    {
        $this->content .= $content;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setParameters(array $parameters): void
    {
        if (!isset($parameters['contact'])) {
            throw new InvalidArgumentException(__CLASS__ . ': Missing contact parameter!', 1721047163);
        }

        $this->contact = $parameters['contact'];
    }
}
