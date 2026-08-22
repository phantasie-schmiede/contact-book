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
use JsonException;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Service\ContactBookService;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Traits\ControllerCacheTrait;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Domain\Repository\Typo3\FrontendUserRepository;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContactBookController
 *
 * @package PSBits\ContactBook\Controller
 */
class ContactBookController extends ActionController
{
    use ControllerCacheTrait;

    public function __construct(
        protected readonly ContactBookRepository  $contactBookRepository,
        protected readonly ContactBookService     $contactBookService,
        protected readonly FrontendUserRepository $frontendUserRepository,
        protected readonly PersistenceManager     $persistenceManager,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     * @throws \Exception
     */
    #[PluginAction(uncached: true)]
    public function createAction(ContactBook $contactBook): ResponseInterface
    {
        $this->setOwners($contactBook);
        $this->contactBookRepository->add($contactBook);
        $this->persistenceManager->persistAll();
        $this->contactBookService->updateSlug($contactBook);

        if (1 === $this->contactBookRepository->findByOwner()
                ->count()) {
            $this->contactBookService->setActiveContactBookForCurrentUser($contactBook);
        }

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws UnknownObjectException
     * @throws \Exception
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(ContactBook $contactBook): ResponseInterface
    {
        $this->contactBookRepository->remove($contactBook);
        $this->persistenceManager->persistAll();

        if (0 < $this->contactBookRepository->findByOwner()
                ->count()) {
            // Ensure there is still an active contact book if the active one was deleted.
            $this->contactBookService->getActiveContactBookForCurrentUser();
        }

        return $this->redirect('list');
    }

    #[PluginAction(uncached: true)]
    public function editAction(ContactBook $contactBook): ResponseInterface
    {
        $this->view->assignMultiple([
            'contactBook'   => $contactBook,
            'frontendUsers' => $this->frontendUserRepository->findAll(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @throws \Exception
     */
    #[PluginAction(default: true, uncached: true)]
    public function listAction(): ResponseInterface
    {
        $this->view->assign('contactBooks', $this->contactBookRepository->findByOwner());

        return $this->htmlResponse();
    }

    /**
     * @throws \Exception
     */
    #[PluginAction(uncached: true)]
    public function newAction(?ContactBook $contactBook = null): ResponseInterface
    {
        $this->view->assignMultiple([
            'contactBook'   => $contactBook ?? (new ContactBook())->addOwner(
                GlobalVariableService::get(FrontendUserProvider::class . '.user')
            ),
            'frontendUsers' => $this->frontendUserRepository->findAll(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function selectAction(ContactBook $contactBook): ResponseInterface
    {
        $this->contactBookService->setActiveContactBookForCurrentUser($contactBook);

        return $this->redirect('list');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(ContactBook $contactBook): ResponseInterface
    {
        $this->setOwners($contactBook);
        $this->contactBookRepository->update($contactBook);
        $this->persistenceManager->persistAll();
        $this->contactBookService->updateSlug($contactBook);

        return $this->redirect('list');
    }

    /**
     * @throws JsonException
     */
    private function setOwners(ContactBook $contactBook): void
    {
        $contactBook->removeAllOwners();
        $ownerUids = json_decode(
            $this->request->getArgument('owners') ?: '[]',
            true,
            2,
            JSON_THROW_ON_ERROR
        );

        foreach ($ownerUids as $ownerUid) {
            $owner = $this->frontendUserRepository->findByUid($ownerUid);

            if ($owner instanceof FrontendUser) {
                $contactBook->addOwner($owner);
            }
        }
    }
}
