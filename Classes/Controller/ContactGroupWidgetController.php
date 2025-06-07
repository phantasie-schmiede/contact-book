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
use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use PSB\PsbContacts\Service\ContactGroupService;
use PSB\PsbContacts\Service\ContactService;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Utility\Configuration\FilePathUtility;
use PSB\PsbFoundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class ContactGroupWidgetController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactGroupWidgetController extends ActionController
{
    public function __construct(
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactService         $contactService,
        protected readonly ExtensionInformation   $extensionInformation,
    ) {
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
