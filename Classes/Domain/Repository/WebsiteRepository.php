<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\WebsiteDemand;
use PSB\PsbContacts\Domain\Model\Website;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class WebsiteRepository
 *
 * @extends Repository<Website>
 * @package PSB\PsbContacts\Domain\Repository
 */
class WebsiteRepository extends Repository
{
    public function findByDemand(WebsiteDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if ('' !== $demand->getUrl()) {
            $constraints[] = $query->equals('url', $demand->getUrl());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
