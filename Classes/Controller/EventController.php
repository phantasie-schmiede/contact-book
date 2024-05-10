<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use Exception;
use JsonException;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Domain\Repository\EventRepository;
use PSB\PsbFoundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class EventController
 *
 * @package PSB\PsbContacts\Controller
 */
class EventController extends ActionController
{
    public function __construct(
        protected readonly ContactRepository $contactRepository,
        protected readonly EventRepository   $eventRepository,
    ) {
    }

    /** @throws JsonException */
    #[PluginAction(uncached: true)]
    public function createAction(Event $event): ResponseInterface
    {
        try {
            $this->eventRepository->add($event);
            $success = true;
        } catch (Exception) {
            $success = false;
        }

        return $this->buildJsonResponse($success);
    }

    /** @throws JsonException */
    #[PluginAction(uncached: true)]
    public function deleteAction(Event $event): ResponseInterface
    {
        try {
            $this->eventRepository->remove($event);
            $success = true;
        } catch (Exception) {
            $success = false;
        }

        return $this->buildJsonResponse($success);
    }

    #[PluginAction(uncached: true)]
    public function editAction(Event $event): ResponseInterface
    {
        $this->prepareFormView($event);

        return $this->htmlResponse();
    }

    #[PluginAction(uncached: true)]
    public function newAction(Event $event = null): ResponseInterface
    {
        $this->prepareFormView($event);

        return $this->htmlResponse();
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Event $event): ResponseInterface
    {
        try {
            $this->eventRepository->update($event);
            $success = true;
        } catch (Exception) {
            $success = false;
        }

        return $this->buildJsonResponse($success);
    }

    /** @throws JsonException */
    private function buildJsonResponse(bool $success): ResponseInterface
    {
        return $this->jsonResponse(
            json_encode(
                [
                    'success' => $success,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function prepareFormView(Event $event = null): void
    {
        $this->view->assignMultiple([
            'contacts' => $this->contactRepository->findAll(),
            'event'    => $event,
            'types'    => EventType::toArray(),
        ]);
    }
}
