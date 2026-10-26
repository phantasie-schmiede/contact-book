<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Service;

use Doctrine\DBAL\Exception;
use PSBits\ContactBook\Controller\ContactGroupController;
use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\FrontendTemplates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContactGroupService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ContactGroupService
{
    public function __construct(
        protected ContactGroupRepository $contactGroupRepository,
        protected ContactService         $contactService,
        protected PersistenceManager     $persistenceManager,
        protected TcaService             $tcaService,
    ) {
    }

    public function getCacheEntryIdentifier(ContactGroup $contactGroup): string
    {
        return ContactGroupController::CACHE_TAG . '_' . $contactGroup->getUid();
    }

    public function prepareView(ContactGroup $contactGroup, RequestInterface $request, ViewInterface $view): void
    {
        $list = $this->contactService->getCachedList(
            $this->getCacheEntryIdentifier($contactGroup)
        );

        if (false === $list) {
            $list = $this->contactService->renderListAndUpdateCache(
                $this->getCacheEntryIdentifier($contactGroup),
                $contactGroup->getContactsSorted(),
                $request
            );
        }

        $view->assignMultiple([
            'contactGroup' => $contactGroup,
            'list'         => $list,
            'pluginUid'    => $request->getAttribute('currentContentObject')->data['uid'],
            'totalCount'   => $contactGroup->getContacts()
                ->count(),
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws IllegalObjectTypeException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function updateSlug(ContactGroup $contactGroup): void
    {
        $slug = SlugUtility::generateUniqueSlug(
            $contactGroup->getUid(),
            $this->tcaService->convertClassNameToTableName(ContactGroup::class)
        );

        if (false !== $slug) {
            $contactGroup->setSlug($slug);
        }

        $this->contactGroupRepository->update($contactGroup);
        $this->persistenceManager->persistAll();
    }
}
