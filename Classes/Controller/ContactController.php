<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbFoundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

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

    /** @throws IllegalObjectTypeException */
    #[PluginAction(uncached: true)]
    public function createAction(Contact $contact): ResponseInterface
    {
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
        $this->contactRepository->update($contact);

        return $this->redirect(
            actionName: 'show', arguments: ['contact' => $contact]
        );
    }

    private function prepareFormView(?Contact $contact = null): void
    {
        $this->view->assignMultiple([
            'companies' => $this->contactRepository->findByType(ContactType::Company),
            'contact'   => $contact,
            'types'     => ContactType::toArray(),
        ]);
    }
}
