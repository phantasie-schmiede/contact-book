<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\AddressDemand;
use PSB\PsbContacts\Domain\Model\Address;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AddressRepository
 *
 * @extends Repository<Address>
 * @package PSB\PsbContacts\Domain\Repository
 */
class AddressRepository extends Repository
{
    public function findByDemand(AddressDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if ('' !== $demand->getCity()) {
            $constraints[] = $query->equals('city', $demand->getCity());
        }

        if ('' !== $demand->getCountry()) {
            $constraints[] = $query->equals('country', $demand->getCountry());
        }

        if ('' !== $demand->getCounty()) {
            $constraints[] = $query->equals('county', $demand->getCounty());
        }

        if ('' !== $demand->getHouseNumber()) {
            $constraints[] = $query->equals('houseNumber', $demand->getHouseNumber());
        }

        if ('' !== $demand->getStreet()) {
            $constraints[] = $query->equals('street', $demand->getStreet());
        }

        if ('' !== $demand->getZipCode()) {
            $constraints[] = $query->equals('zipCode', $demand->getZipCode());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
