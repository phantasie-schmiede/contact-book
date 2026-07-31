<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\AddressDemand;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\Foundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AddressRepository
 *
 * @extends Repository<Address>
 * @package PSBits\ContactBook\Domain\Repository
 */
class AddressRepository extends Repository
{
    /** @return QueryResultInterface<int, Address> */
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
