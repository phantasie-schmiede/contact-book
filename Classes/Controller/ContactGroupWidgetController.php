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
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Service\ContactGroupService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Class ContactGroupWidgetController
 *
 * @package PSBits\ContactBook\Controller
 */
class ContactGroupWidgetController extends AbstractAccessController
{
    public function __construct(
        ContactBookRepository                     $contactBookRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactService         $contactService,
        protected readonly ExtensionInformation   $extensionInformation,
    ) {
        parent::__construct($contactBookRepository);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[PluginAction]
    public function displayAction(): ResponseInterface
    {
        $contactGroup = $this->contactGroupRepository->findByUid($this->settings['contactGroup']);
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
