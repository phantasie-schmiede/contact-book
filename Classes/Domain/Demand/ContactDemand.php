<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Demand;

use PSB\PsbContacts\Domain\Model\Contact;

/**
 * Class ContactDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
 */
class ContactDemand
{
    protected array $excludedContacts = [];

    public function addExcludedContact(Contact|int $contact): void
    {
        if ($contact instanceof Contact) {
            $this->excludedContacts[] = $contact->getUid();
        } else {
            $this->excludedContacts[] = $contact;
        }
    }

    public function getExcludedContacts(): array
    {
        return $this->excludedContacts;
    }
}
