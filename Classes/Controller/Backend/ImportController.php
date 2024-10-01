<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller\Backend;

use JsonException;
use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbContacts\Service\ImportService;
use PSB\PsbFoundation\Attribute\ModuleAction;
use PSB\PsbFoundation\Controller\Backend\AbstractModuleController;
use PSB\PsbFoundation\Utility\Configuration\FilePathUtility;
use PSB\PsbFoundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

/**
 * Class ImportController
 *
 * @package PSB\PsbContacts\Controller\Backend
 */
#[AsController]
class ImportController extends AbstractModuleController
{
    public function __construct(
        protected readonly ExtensionInformation $extensionInformation,
        protected readonly ImportService        $importService,
        ModuleTemplateFactory                   $moduleTemplateFactory,
    ) {
        parent::__construct($moduleTemplateFactory);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws UnknownObjectException
     */
    #[ModuleAction]
    public function importAction(): ResponseInterface
    {
        $ll = FilePathUtility::getLanguageFilePathForCurrentFile($this->extensionInformation);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $this->request->getUploadedFiles()['contactsFile'];
        $file = $uploadedFile->getTemporaryFileName();
        $this->importService->parseVCardFile($file);
        $this->addFlashMessage(LocalizationUtility::translate($ll . 'flashMessage.success.body'));

        return $this->redirect('showUploadForm');
    }

    #[ModuleAction(default: true)]
    public function showUploadFormAction(): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($this->view->render()));
    }
}
