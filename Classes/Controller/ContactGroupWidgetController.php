<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use Error;
use Exception;
use InvalidArgumentException;
use JsonException;
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Service\ContactGroupService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use PSBits\FrontendUsers\Service\ValidationService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class ContactGroupWidgetController extends AbstractAccessController
{
    public function __construct(
        ContactBookRepository                     $contactBookRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactService         $contactService,
        protected readonly ExtensionInformation   $extensionInformation,
        ValidationService                         $validationService,
    ) {
        parent::__construct($contactBookRepository, $validationService);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Error
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ReflectionException
     */
    #[PluginAction]
    public function displayAction(): ResponseInterface
    {
        $contactGroup     = $this->contactGroupRepository->findByUid($this->settings['contactGroup']);
        $languageFilePath = FilePathUtility::getLanguageFilePath(
            $this->extensionInformation
        ) . 'Frontend/ContactGroupWidget/display.xlf:error.';

        if (null === $contactGroup) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    key      : $languageFilePath . 'message',
                    arguments: ['uid' => $this->settings['contactGroup']],
                ),
                LocalizationUtility::translate($languageFilePath . 'title'),
                ContextualFeedbackSeverity::ERROR
            );

            return $this->htmlResponse();
        }

        $this->contactGroupService->prepareView($contactGroup, $this->request, $this->view);

        return $this->htmlResponse();
    }
}
