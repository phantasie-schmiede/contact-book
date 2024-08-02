<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use DateTime;
use Exception;
use JsonException;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSB\PsbFoundation\Service\GlobalVariableService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use function is_array;

/**
 * Class ContactController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactController extends ActionController
{
    public function __construct(
        protected readonly ContactRepository $contactRepository,
    ) {
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function confirmDeleteAction(Contact $contact): ResponseInterface
    {
        $this->view->assign('contact', $contact);

        return $this->jsonResponse(json_encode([
            'body'   => $contact->getDisplayName() . ' wirklich löschen?',
            'footer' => $this->view->render(),
            'title'  => 'Löschen bestätigen',
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Exception
     * @throws IllegalObjectTypeException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Contact $contact): ResponseInterface
    {
        $this->processEvents($contact);
        $this->contactRepository->add($contact);

        return $this->redirect('list');
    }

    /** @throws IllegalObjectTypeException */
    #[PluginAction(uncached: true)]
    public function deleteAction(Contact $contact): ResponseInterface
    {
        $this->contactRepository->remove($contact);

        return $this->redirect('list');
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function editAction(Contact $contact): ResponseInterface
    {
        $this->prepareFormView($contact);

        return $this->htmlResponse();
    }

    #[PluginAction(default: true, uncached: true)]
    public function listAction(): ResponseInterface
    {
        $this->view->assign('contacts', $this->contactRepository->findAll());

        return $this->htmlResponse();
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function newAction(Contact $contact = null): ResponseInterface
    {
        $this->prepareFormView($contact);

        return $this->htmlResponse();
    }

    #[PluginAction(uncached: true)]
    public function showAction(Contact $contact): ResponseInterface
    {
        $this->view->assign('contact', $contact);

        return $this->htmlResponse();
    }

    /**
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Contact $contact): ResponseInterface
    {
        $this->processEvents($contact);
        $this->contactRepository->update($contact);

        return $this->redirect(
            actionName: 'show', arguments: ['contact' => $contact]
        );
    }

    /**
     * @throws JsonException
     */
    private function prepareFormView(?Contact $contact = null): void
    {
        $this->view->assignMultiple([
            'generalTypes'     => GeneralType::toArray(GeneralType::Private),
            'companies'        => $this->contactRepository->findByType(ContactType::Company),
            'contact'          => $contact,
            'allContacts'      => json_encode(
                array_map(
                    static fn(Contact $contact) => [
                        'label' => $contact->getDisplayName(),
                        'value' => $contact->getUid(),
                    ],
                    $this->contactRepository->findAll()
                        ->toArray()
                ),
                JSON_THROW_ON_ERROR
            ),
            'eventTypes'       => EventType::toArray(EventType::Birthday),
            'contactTypes'     => ContactType::toArray(ContactType::Person),
            'phoneNumberTypes' => PhoneNumberType::toArray(PhoneNumberType::Mobile),
        ]);
    }

    /**
     * @throws Exception
     */
    private function processEvents(Contact $contact): void
    {
        $existingEvents = clone $contact->getEvents();
        $updatedEvents = $this->request->hasArgument('event') ? $this->request->getArgument('event') : [];
        $newEvents = GlobalVariableService::get(RequestParameterProvider::class . '.event', false);

        if (is_array($updatedEvents)) {
            foreach ($updatedEvents as $uid => $eventData) {
                $event = $contact->getEventByUid($uid);
                $this->updateEventProperties($contact, $event, $eventData);
                $existingEvents->detach($event);
            }
        }

        if (0 < $existingEvents->count()) {
            // Delete events that were removed.
            foreach ($existingEvents as $event) {
                $contact->removeEvent($event);
            }
        }

        if (is_array($newEvents)) {
            foreach ($newEvents as $eventData) {
                $event = new Event();
                $this->updateEventProperties($contact, $event, $eventData);
                $contact->addEvent($event);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function updateEventProperties(Contact $contact, Event $event, array $eventData): void
    {
        $event->removeAllContacts();
        $event->addContact($contact);

        if (!empty($eventData['contacts'])) {
            $contactIds = array_map('\intval', json_decode($eventData['contacts'], false, 512, JSON_THROW_ON_ERROR));

            foreach ($contactIds as $contactId) {
                $additionalContact = $this->contactRepository->findByUid($contactId);

                if (null !== $additionalContact) {
                    $event->addContact($additionalContact);
                }
            }
        }

        $event->setDate(new DateTime($eventData['date']));
        $event->setNotes($eventData['notes']);
        $event->setShowAnniversaries((bool)$eventData['showAnniversaries']);
        $event->setShowInCalendar((bool)$eventData['showInCalendar']);
        $event->setType($eventData['type']);
    }
}
