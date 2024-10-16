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
use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbContacts\Domain\Model\ContactGroup;
use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Service\ContactGroupService;
use PSB\PsbContacts\Service\ContactService;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Traits\ControllerCacheTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContactGroupController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactGroupController extends ActionController
{
    use ControllerCacheTrait;

    public const string CACHE_TAG = 'tx_psbcontactgroups';

    public function __construct(
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactRepository      $contactRepository,
        protected readonly ContactService         $contactService,
        protected readonly ExtensionInformation   $extensionInformation,
        protected readonly PersistenceManager     $persistenceManager,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function createAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->contactService->setContactsOfRelatedEntity(
            entity            : $contactGroup,
            additionalContacts: $this->request->getArgument('contacts')
        );
        $this->contactGroupRepository->add($contactGroup);
        $this->persistenceManager->persistAll();
        $this->contactGroupService->updateSlug($contactGroup);
        $this->deletePagesCache();

        return $this->redirect(actionName: 'show', arguments: ['contactGroup' => $contactGroup]);
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->contactGroupRepository->remove($contactGroup);
        $this->deletePagesCache();

        return $this->redirect('list');
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function editAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->view->assign('contactGroup', $contactGroup);
        $this->view->assign('allContacts', $this->contactService->getAsJson());

        return $this->htmlResponse();
    }

    #[PluginAction(default: true)]
    public function listAction(): ResponseInterface
    {
        $this->setCacheTag();
        $this->view->assign('contactGroups', $this->contactGroupRepository->findAll());

        return $this->htmlResponse();
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function newAction(): ResponseInterface
    {
        $this->view->assign('allContacts', $this->contactService->getAsJson());

        return $this->htmlResponse();
    }

    #[PluginAction]
    public function showAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->setCacheTag();
        $this->view->assign('contactGroup', $contactGroup);

        return $this->htmlResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->contactService->setContactsOfRelatedEntity(
            entity            : $contactGroup,
            additionalContacts: $this->request->getArgument('contacts')
        );
        $this->contactGroupRepository->update($contactGroup);
        $this->persistenceManager->persistAll();
        $this->contactGroupService->updateSlug($contactGroup);
        $this->deletePagesCache();

        return $this->redirect(actionName: 'show', arguments: ['contactGroup' => $contactGroup]);
    }
}
