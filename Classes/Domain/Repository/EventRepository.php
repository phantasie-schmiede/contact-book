<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Repository;

use PSB\PsbContacts\Domain\Demand\EventDemand;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbFoundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class EventRepository
 *
 * @extends Repository<Event>
 * @package PSB\PsbContacts\Domain\Repository
 */
class EventRepository extends Repository
{
    /**
     * @param EventDemand $demand
     *
     * @return QueryResultInterface<Event>
     * @throws InvalidQueryException
     */
    public function findByDemand(EventDemand $demand): QueryResultInterface
    {
        $constraints = [];
        $query = $this->createQuery();

        if (null !== $demand->getMinDate()) {
            $constraints[] = $query->logicalOr(
                $query->greaterThanOrEqual('date', $demand->getMinDate()),
                $query->equals('showAnniversaries', true),
            );
        }

        if (null !== $demand->getShowAnniversaries()) {
            $constraints[] = $query->equals('showAnniversaries', $demand->getShowAnniversaries());
        }

        if (null !== $demand->getShowInCalendar()) {
            $constraints[] = $query->equals('showInCalendar', $demand->getShowInCalendar());
        }

        if ([] !== $demand->getTypes()) {
            $orConstraints = [];

            foreach ($demand->getTypes() as $type) {
                $orConstraints[] = $query->equals('type', $type->value);
            }

            $constraints[] = $query->logicalOr(...$orConstraints);
        }

        QueryUtility::applyConstraints($constraints, $query);

        return $query->execute();
    }
}
