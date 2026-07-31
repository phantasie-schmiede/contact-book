<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller\Backend;

use JsonException;
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Service\ImportService;
use PSBits\Foundation\Attribute\ModuleAction;
use PSBits\Foundation\Controller\Backend\AbstractModuleController;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
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
 * @package PSBits\ContactBook\Controller\Backend
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
        $file         = $uploadedFile->getTemporaryFileName();
        $this->importService->parseVCardFile($file);
        $this->addFlashMessage(LocalizationUtility::translate($ll . 'flashMessage.success.body'));

        return $this->redirect('showUploadForm');
    }

    #[ModuleAction(default: true)]
    public function showUploadFormAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }
}
