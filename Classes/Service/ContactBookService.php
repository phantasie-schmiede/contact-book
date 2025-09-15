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
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Templates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContactBookService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ContactBookService
{
    public function __construct(
        protected ContactBookRepository $contactBookRepository,
        protected PersistenceManager    $persistenceManager,
        protected TcaService            $tcaService,
    ) {
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
