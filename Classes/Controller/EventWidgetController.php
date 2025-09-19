<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use DateMalformedStringException;
use DateTime;
use JsonException;
use PSBits\ContactBook\Domain\Demand\EventDemand;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\EventRepository;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Templates\Utility\StringUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use function count;

/**
 * Class EventWidgetController
 *
 * @package PSBits\ContactBook\Controller
 */
class EventWidgetController extends AbstractCheckAccessController
{
    public function __construct(
        ContactBookRepository              $contactBookRepository,
        protected readonly EventRepository $eventRepository,
    ) {
        parent::__construct($contactBookRepository);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws InvalidQueryException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[PluginAction(uncached: true)]
    public function displayAction(): ResponseInterface
    {
        $eventDemand = (new EventDemand())->setShowInCalendar(true)
            ->setMinDate(new DateTime('today'));
        $types = GeneralUtility::trimExplode(',', $this->settings['types'], true);

        if (!empty($types)) {
            foreach ($types as $type) {
                $eventDemand->addType(EventType::from($type));
            }
        }

        $allEvents = $this->eventRepository->findByDemand($eventDemand)
            ->toArray();
        $today = (new DateTime())->format('m-d');
        $currentYear = (int)date('Y');
        $nextYear = $currentYear + 1;
        $allEventsSorted = [];

        /** @var Event $event */
        foreach ($allEvents as $event) {
            $eventDate = $event->getDate(true)
                ->format('m-d');
            $eventYear = $event->getYear();

            if ($eventYear < $nextYear) {
                $eventYear = $eventDate >= $today ? $currentYear : $nextYear;
            }

            $allEventsSorted[$eventYear . '-' . $eventDate][] = $event;
        }

        ksort($allEventsSorted);
        $events = [];

        if (0 < (int)$this->settings['maxEvents']) {
            $maxEvents = (int)$this->settings['maxEvents'];
        }

        if (0 < (int)$this->settings['minDays']) {
            $minDate = (new DateTime())->modify('+' . $this->settings['minDays'] . ' days')
                ->format('Y-m-d');
        }

        foreach ($allEventsSorted as $date => $eventsByDate) {
            /*
             * Breaks if the maximum number of events is set and reached, but includes all events up to the minimum date
             * if set.
             */
            if ((isset($minDate) || isset($maxEvents)) && (!isset($minDate) || $date >= $minDate) && (!isset($maxEvents) || count(
                        $events
                    ) >= $maxEvents)) {
                break;
            }

            $events[$date] = $eventsByDate;
        }

        $this->view->assignMultiple([
            'events'    => $events,
            'typeCount' => count($types),
            'typeList'  => StringUtility::createEnumeration(
                array_map(
                    static fn($type) => EventType::from($type)
                        ->getLabel(),
                    $types
                )
            ),
        ]);

        return $this->htmlResponse();
    }
}
