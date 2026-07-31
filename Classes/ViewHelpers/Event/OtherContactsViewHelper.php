<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\ViewHelpers\Event;

use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\Event;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class OtherContactsViewHelper
 *
 * @package PSBits\ContactBook\ViewHelpers\Event
 */
class OtherContactsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'contact',
            Contact::class,
            'the reference contact that will be excluded from the list',
            true
        );
        $this->registerArgument(
            'event',
            Event::class,
            'an event record related to the contact',
            true
        );
        $this->registerArgument(
            'returnFirst',
            'bool',
            'return only the first contact',
            false,
            false
        );
    }

    /** @return Contact[]|Contact */
    public function render(): array|Contact
    {
        /** @var Contact $contact */
        $contact = $this->arguments['contact'];
        /** @var Event $event */
        $event = $this->arguments['event'];

        if (!$contact instanceof Contact || !$event instanceof Event) {
            return [];
        }

        $otherContacts = [];

        foreach ($event->getContacts() as $otherContact) {
            if ($otherContact->getUid() !== $contact->getUid()) {
                if ($this->arguments['returnFirst']) {
                    return $otherContact;
                }

                $otherContacts[] = $otherContact;
            }
        }

        return $otherContacts;
    }
}
