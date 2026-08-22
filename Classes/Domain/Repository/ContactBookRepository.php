<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
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
     * @param FrontendUser|null $frontendUser
     *
     * @return QueryResultInterface<int, ContactBook>
     * @throws Exception
     */
    public function findByOwner(?FrontendUser $frontendUser = null): QueryResultInterface
    {
        if (null === $frontendUser) {
            $frontendUser = GlobalVariableService::get(FrontendUserProvider::class . '.user');
        }

        /** @var Query<ContactBook> $query */
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage(false);
        $query->matching($query->contains('owners', $frontendUser));

        return $query->execute();
    }
}
