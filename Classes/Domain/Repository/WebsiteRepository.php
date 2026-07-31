<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\WebsiteDemand;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\Foundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class WebsiteRepository
 *
 * @extends Repository<Website>
 * @package PSBits\ContactBook\Domain\Repository
 */
class WebsiteRepository extends Repository
{
    /** @return QueryResultInterface<int, Website> */
    public function findByDemand(WebsiteDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query       = $this->createQuery();

        if ('' !== $demand->getUrl()) {
            $constraints[] = $query->equals('url', $demand->getUrl());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
