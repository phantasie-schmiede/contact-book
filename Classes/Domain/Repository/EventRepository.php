<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Repository;

use Exception;
use PSBits\ContactBook\Domain\Demand\EventDemand;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Service\EventService;
use PSBits\Foundation\Utility\QueryUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class EventRepository
 *
 * @extends Repository<Event>
 * @package PSBits\ContactBook\Domain\Repository
 */
class EventRepository extends Repository
{
    public function __construct(
        protected readonly EventService $eventService,
    ) {
        parent::__construct();
    }

    /**
     * @param EventDemand $demand
     *
     * @return QueryResultInterface<int, Event>
     * @throws Exception
     * @throws InvalidQueryException
     */
    public function findByDemand(EventDemand $demand): QueryResultInterface
    {
        $query = $this->createQuery();

        // Only show events related to contacts of contact books the current frontend user has access to!
        $constraints[] = $query->in('uid', $this->eventService->getAllEventUidsForCurrentFrontendUser() ?: [0]);

        if (null !== $demand->getMinDate()) {
            $constraints[] = $query->logicalOr(
                $query->equals('showAnniversaries', true),
                $query->greaterThan(
                    'year',
                    $demand->getMinDate()
                        ->format('Y')
                ),
                $query->logicalAnd(
                    $query->equals(
                        'year',
                        $demand->getMinDate()
                            ->format('Y')
                    ),
                    $query->logicalOr(
                        $query->greaterThan(
                            'month',
                            $demand->getMinDate()
                                ->format('m')
                        ),
                        $query->logicalAnd(
                            $query->equals(
                                'month',
                                $demand->getMinDate()
                                    ->format('m')
                            ),
                            $query->greaterThanOrEqual(
                                'day',
                                $demand->getMinDate()
                                    ->format('d')
                            )
                        )
                    )
                )
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
