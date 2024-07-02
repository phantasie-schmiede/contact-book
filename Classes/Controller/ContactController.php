<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use JsonException;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\EventType;
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
use function count;
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

    /** @throws IllegalObjectTypeException */
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

    private function prepareFormView(?Contact $contact = null): void
    {
        $this->view->assignMultiple([
            'companies'   => $this->contactRepository->findByType(ContactType::Company),
            'contact'     => $contact,
            'allContacts' => $this->contactRepository->findAll(),
            'eventTypes'  => EventType::toArray(),
            'types'       => ContactType::toArray(),
        ]);
    }

    private function processEvents(Contact $contact): void
    {
        $events = GlobalVariableService::get(RequestParameterProvider::class . '.event', false);

        if (is_array($events)) {
            $itemCount = count($events);

            for ($i = 0; $i < $itemCount; $i++) {
                $newEvent = new Event();
                $newEvent->setDate($events['date'][$i]);
                $newEvent->setNotes($events['notes'][$i]);
                // $newEvent->setShowAnniversaries((bool)($events['showAnniversaries'][$i] ?? false));
                // $newEvent->setShowInCalendar((bool)($events['showInCalendar'][$i] ?? false));
                $newEvent->setType($events['type'][$i]);
                $contact->addEvent($newEvent);
            }
        }
    }
}
