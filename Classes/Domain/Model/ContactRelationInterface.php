<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Interface ContactRelationInterface
 *
 * @package PSB\PsbContacts\Domain\Model
 */
interface ContactRelationInterface
{
    public function getContacts(): ObjectStorage;
}
