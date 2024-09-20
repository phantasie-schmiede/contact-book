<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\EMailDemand;
use PSB\PsbContacts\Domain\Model\EMail;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class EMailRepository
 *
 * @extends Repository<EMail>
 * @package PSB\PsbContacts\Domain\Repository
 */
class EMailRepository extends Repository
{
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
