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
use JsonException;
use PSBits\ContactBook\Controller\ContactController;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Utility\ContactBookUtility;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Domain\Repository\Typo3\FrontendUserRepository;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PSBits\FrontendTemplates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use UnexpectedValueException;

/**
 * Class ContactBookService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ContactBookService
{
    public function __construct(
        protected ContactBookRepository  $contactBookRepository,
        protected FrontendUserRepository $frontendUserRepository,
        protected PersistenceManager     $persistenceManager,
        protected TcaService             $tcaService,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws UnknownObjectException
     * @throws \Exception
     */
    public function getActiveContactBookForCurrentUser(): ContactBook
    {
        $contactBook = $this->contactBookRepository->findByUid(ContactBookUtility::getActiveContactBookUid() ?? 0);

        if (null !== $contactBook) {
            return $contactBook;
        }

        /** @var ContactBook|null $defaultContactBook */
        $defaultContactBook = $this->contactBookRepository->findByOwner()
            ->getFirst();

        if (null === $defaultContactBook) {
            throw new UnexpectedValueException(
                __CLASS__ . ': No contact book found for the current user.',
                1758002632
            );
        }

        $this->setActiveContactBookForCurrentUser($defaultContactBook);

        return $defaultContactBook;
    }

    /**
     * @throws JsonException
     */
    public function getCacheEntryIdentifier(): string
    {
        return ContactController::CACHE_TAG . '_list_' . ContactBookUtility::getActiveContactBookUid();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws UnknownObjectException
     * @throws \Exception
     */
    public function setActiveContactBookForCurrentUser(ContactBook $contactBook): void
    {
        /** @var FrontendUser $user */
        $user = GlobalVariableService::get(FrontendUserProvider::class . '.user');
        $user->setSetting('activeContactBook', $contactBook->getUid());
        $this->frontendUserRepository->update($user);
        $this->persistenceManager->persistAll();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    public function updateSlug(ContactBook $contactBook): void
    {
        $slug = SlugUtility::generateUniqueSlug(
            $contactBook->getUid(),
            $this->tcaService->convertClassNameToTableName(ContactBook::class)
        );

        if (false !== $slug) {
            $contactBook->setSlug($slug);
        }

        $this->contactBookRepository->update($contactBook);
        $this->persistenceManager->persistAll();
    }
}
