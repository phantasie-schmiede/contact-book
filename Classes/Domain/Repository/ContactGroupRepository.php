<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use Exception;
use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Utility\ContactBookUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<ContactGroup>
 */
class ContactGroupRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, ContactGroup>
     * @throws Exception
     */
    public function findAllOfActiveContactBook(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage(false);

        $query->matching($query->equals('contactBook', ContactBookUtility::getActiveContactBookUid()));

        return $query->execute();
    }
}
