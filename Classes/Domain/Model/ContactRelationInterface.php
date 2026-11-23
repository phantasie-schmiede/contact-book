<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

interface ContactRelationInterface
{
    public function addContact(Contact $contact): self;

    /** @return ObjectStorage<Contact> */
    public function getContacts(): ObjectStorage;

    public function removeAllContacts(): self;

    public function removeContact(Contact $contact): self;

    /** @param ObjectStorage<Contact> $contacts */
    public function setContacts(ObjectStorage $contacts): self;
}
