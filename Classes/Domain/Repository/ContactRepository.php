<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\Foundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContactRepository
 *
 * @extends Repository<Contact>
 * @package PSBits\ContactBook\Domain\Repository
 */
class ContactRepository extends Repository
{
    /**
     * @param ContactDemand $demand
     *
     * @return Contact[]|QueryResultInterface<int, Contact>
     * @throws InvalidQueryException
     */
    public function findByDemand(ContactDemand $demand): array|QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage($demand->getRespectStoragePage());

        if ([] !== $demand->getCategoryUids()) {
            $constraints[] = $query->in('categories.uid', $demand->getCategoryUids());
        }

        if ('' !== $demand->getCompanyName()) {
            $constraints[] = $query->equals('companyName', $demand->getCompanyName());
        }

        if (0 < $demand->getContactBookUid()) {
            $constraints[] = $query->equals('contactBook', $demand->getContactBookUid());
        }

        if (0 < $demand->getContactGroupUid()) {
            $constraints[] = $query->contains('contactGroups', $demand->getContactGroupUid());
        }

        if ([] !== $demand->getContactUids()) {
            $constraints[] = $query->in('uid', $demand->getContactUids());
        }

        if ([] !== $demand->getExcludedContacts()) {
            foreach ($demand->getExcludedContacts() as $excludedContact) {
                $constraints[] = $query->logicalNot($query->equals('uid', $excludedContact));
            }
        }

        if ('' !== $demand->getFirstName()) {
            $constraints[] = $query->equals('firstName', $demand->getFirstName());
        }

        if ('' !== $demand->getLastName()) {
            $constraints[] = $query->equals('lastName', $demand->getLastName());
        }

        if ([] !== $demand->getTypes()) {
            $orConstraints = [];

            foreach ($demand->getTypes() as $type) {
                $orConstraints[] = $query->equals('type', $type->value);
            }

            $constraints[] = $query->logicalOr(...$orConstraints);
        }

        QueryUtility::applyConstraints($constraints, $query);

        $contacts = $query->execute();

        if ($demand->getOnlyExportRelevantInformation()) {
            $filteredContacts = [];

            foreach ($contacts as $contact) {
                if ($contact->hasExportRelevantInformation()) {
                    $filteredContacts[] = $contact;
                }
            }

            return $filteredContacts;
        }

        return $contacts;
    }
}
