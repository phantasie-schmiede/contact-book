<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContactRepository
 *
 * @package PSB\PsbContacts\Domain\Repository
 */
class ContactRepository extends Repository
{
    public function findByDemand(ContactDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if ([] !== $demand->getExcludedContacts()) {
            foreach ($demand->getExcludedContacts() as $excludedContact) {
                $constraints[] = $query->logicalNot($query->equals('uid', $excludedContact));
            }
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }

    public function findByType(ContactType $type): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('type', $type->value));

        return $query->execute();
    }
}
