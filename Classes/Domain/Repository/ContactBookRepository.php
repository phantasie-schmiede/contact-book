<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\ContactBookDemand;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Utility\QueryUtility;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContactBookRepository
 *
 * @extends Repository<ContactBook>
 * @package PSBits\ContactBook\Domain\Repository
 */
class ContactBookRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, ContactBook>
     * @throws InvalidQueryException
     */
    public function findByDemand(ContactBookDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query       = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage($demand->getRespectStoragePage());

        if ($demand->getOwner() instanceof FrontendUser) {
            $constraints[] = $query->contains('owners', $demand->getOwner());
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }

    /**
     * @param FrontendUser|null $frontendUser
     *
     * @return QueryResultInterface<int, ContactBook>
     */
    public function findByOwner(?FrontendUser $frontendUser = null): QueryResultInterface
    {
        if (null === $frontendUser) {
            $frontendUser = GlobalVariableService::get(FrontendUserProvider::class . '.user');
        }

        $demand = (new ContactBookDemand())->setOwner($frontendUser)
            ->setRespectStoragePage(false);

        return $this->findByDemand($demand);
    }

    /**
     * @throws InvalidQueryException
     */
    public function findOneByDemand(ContactBookDemand $contactBookDemand): ?ContactBook
    {
        return $this->findByDemand($contactBookDemand)
            ->getFirst();
    }
}
