<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use JsonException;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\FrontendUsers\Service\ValidationService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as ExtbaseException;

/**
 * Class AbstractAccessController
 *
 * @package PSBits\ContactBook\Controller
 */
abstract class AbstractAccessController extends ActionController
{
    public function __construct(
        protected readonly ContactBookRepository $contactBookRepository,
        protected readonly ValidationService     $validationService,
    ) {
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws ExtbaseException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws PropagateResponseException
     */
    protected function initializeAction(): void
    {
        $this->validationService->requiresFrontendUser();

        if (0 === $this->contactBookRepository->findByOwner()
                ->count()) {
            throw new PropagateResponseException(
                new RedirectResponse($this->getRedirectUri(), 401),
                1758282051
            );
        }
    }

    private function getRedirectUri(): string
    {
        return $this->uriBuilder->reset()
            ->uriFor(actionName: 'list', controllerName: 'ContactBook');
    }
}
