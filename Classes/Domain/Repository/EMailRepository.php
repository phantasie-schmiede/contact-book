<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\EMailDemand;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\Foundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class EMailRepository
 *
 * @extends Repository<EMail>
 * @package PSBits\ContactBook\Domain\Repository
 */
class EMailRepository extends Repository
{
    /** @return QueryResultInterface<int, EMail> */
    public function findByDemand(EMailDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if ('' !== $demand->getEMailAddress()) {
            $constraints[] = $query->equals('eMailAddress', $demand->getEMailAddress());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
