<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use Doctrine\DBAL\Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbContacts\Domain\Model\ContactGroup;
use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Service\ContactGroupService;
use PSB\PsbContacts\Service\ContactService;
use PSB\PsbContacts\Service\Enum\VCardVersion;
use PSB\PsbContacts\Service\VCardService;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Traits\ControllerCacheTrait;
use PSB\PsbFoundation\Utility\FileUtility;
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
        protected readonly VCardService           $vCardService,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
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
        $this->deletePagesCache(self::CACHE_TAG);

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
        $this->deletePagesCache(self::CACHE_TAG, self::CACHE_TAG . '_' . $contactGroup->getUid());

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

    #[NoReturn]
    #[PluginAction(uncached: true)]
    public function exportAction(ContactGroup $contactGroup, ?VCardVersion $version = null): void
    {
        $vCards = [];

        foreach ($contactGroup->getContacts() as $contact) {
            $vCards[] = $this->vCardService->getVCard($contact, $version ?? VCardVersion::V4_0);
        }

        FileUtility::initiateDownload(
            'text/vcard',
            implode('', $vCards),
            FileUtility::sanitizeFileName($contactGroup->getName()) . '.vcf',
        );

        exit;
    }

    #[PluginAction(default: true)]
    public function listAction(): ResponseInterface
    {
        $this->setCacheTags(self::CACHE_TAG);
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

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function refreshListAction(ContactGroup $contactGroup): ResponseInterface
    {
        $list = $this->contactService->renderListAndUpdateCache(
            $this->contactGroupService->getCacheEntryIdentifier($contactGroup),
            $contactGroup->getContactsSorted(),
            $this->request
        );

        return $this->jsonResponse(json_encode(['html' => $list], JSON_THROW_ON_ERROR));
    }

    #[PluginAction(uncached: true)]
    public function showAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->contactGroupService->prepareView($contactGroup, $this->request, $this->view);

        return $this->htmlResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
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
        $this->deletePagesCache(self::CACHE_TAG, self::CACHE_TAG . '_' . $contactGroup->getUid());

        return $this->redirect(actionName: 'show', arguments: ['contactGroup' => $contactGroup]);
    }
}
