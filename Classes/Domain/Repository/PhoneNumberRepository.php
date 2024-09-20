<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\PhoneNumberDemand;
use PSB\PsbContacts\Domain\Model\PhoneNumber;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class PhoneNumberRepository
 *
 * @extends Repository<PhoneNumber>
 * @package PSB\PsbContacts\Domain\Repository
 */
class PhoneNumberRepository extends Repository
{
    public function findByDemand(PhoneNumberDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if ('' !== $demand->getAreaCode()) {
            $constraints[] = $query->equals('areaCode', $demand->getAreaCode());
        }

        if ('' !== $demand->getConnectionIdentifier()) {
            $constraints[] = $query->equals('connectionIdentifier', $demand->getConnectionIdentifier());
        }

        if ('' !== $demand->getExtension()) {
            $constraints[] = $query->equals('extension', $demand->getExtension());
        }

        if ('' !== $demand->getInternationalDiallingCode()) {
            $constraints[] = $query->equals('internationalDiallingCode', $demand->getInternationalDiallingCode());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
