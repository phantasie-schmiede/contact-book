<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use Doctrine\DBAL\Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Service\ContactBookService;
use PSBits\ContactBook\Service\ContactGroupService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\ContactBook\Service\VCardService;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Traits\ControllerCacheTrait;
use PSBits\Foundation\Utility\FileUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContactGroupController
 *
 * @package PSBits\ContactBook\Controller
 */
class ContactGroupController extends AbstractCheckAccessController
{
    use ControllerCacheTrait;

    public const string CACHE_TAG = 'tx_contactbook_groups';

    public function __construct(
        ContactBookRepository                     $contactBookRepository,
        protected readonly ContactBookService     $contactBookService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactRepository      $contactRepository,
        protected readonly ContactService         $contactService,
        protected readonly ExtensionInformation   $extensionInformation,
        protected readonly PersistenceManager     $persistenceManager,
        protected readonly VCardService           $vCardService,
    ) {
        parent::__construct($contactBookRepository);
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
        $contactGroup->setContactBook($this->contactBookService->getActiveContactBookForCurrentUser());
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

    #[PluginAction(uncached: true)]
    public function editAction(ContactGroup $contactGroup): ResponseInterface
    {
        $this->view->assign('contactGroup', $contactGroup);

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

    /**
     * @throws JsonException
     */
    #[PluginAction(default: true)]
    public function listAction(): ResponseInterface
    {
        $this->setCacheTags(self::CACHE_TAG);
        $this->view->assign('contactGroups', $this->contactGroupRepository->findAllOfActiveContactBook());

        return $this->htmlResponse();
    }

    #[PluginAction(uncached: true)]
    public function newAction(): ResponseInterface
    {
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
