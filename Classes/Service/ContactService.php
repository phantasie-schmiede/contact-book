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
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactRelationInterface;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Templates\Data\ExtensionInformation as TemplatesExtensionInformation;
use PSBits\Templates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use function is_string;

/**
 * Class ContactService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ContactService
{
    public function __construct(
        protected ContactRepository             $contactRepository,
        protected ExtensionInformation          $extensionInformation,
        protected FrontendInterface             $frontendCache,
        protected TcaService                    $tcaService,
        protected TemplatesExtensionInformation $templatesExtensionInformation,
        protected ViewFactoryInterface          $viewFactory,
    ) {
    }

    public function getCachedList(string $cacheEntryIdentifier): false|string
    {
        return $this->frontendCache->get($cacheEntryIdentifier);
    }

    public function renderAutocompleteList(
        array            $contacts,
        RequestInterface $request,
    ): string {
        $privateResourcePath = FilePathUtility::getPrivateResourcePath($this->extensionInformation);

        // @formatter:off
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: [$privateResourcePath . 'Templates/Ajax'],
            partialRootPaths : [
                FilePathUtility::getPrivateResourcePath($this->templatesExtensionInformation) . 'Partials',
                $privateResourcePath . 'Partials',
            ],
            layoutRootPaths  : [$privateResourcePath . 'Layouts'],
            request          : $request,
        );
        // @formatter:on

        $view = $this->viewFactory->create($viewFactoryData);
        $view->assign('contacts', $contacts);

        return $view->render('AutocompleteList');
    }

    public function renderListAndUpdateCache(
        string           $cacheEntryIdentifier,
        array            $contacts,
        RequestInterface $request,
    ): string {
        $privateResourcePath = FilePathUtility::getPrivateResourcePath($this->extensionInformation);

        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: [$privateResourcePath . 'Templates/Cache'],
            partialRootPaths : [$privateResourcePath . 'Partials'],
            layoutRootPaths  : [$privateResourcePath . 'Layouts'],
            request          : $request,
        );

        $view = $this->viewFactory->create($viewFactoryData);
        $view->assign('contacts', $contacts);

        $list = $view->render('ContactList');
        $this->frontendCache->set($cacheEntryIdentifier, $list, [], 0);

        return $list;
    }

    /**
     * @throws JsonException
     */
    public function setContactsOfRelatedEntity(
        ContactRelationInterface $entity,
        Contact                  $contact = null,
        array|string             $additionalContacts = [],
    ): void {
        $entity->removeAllContacts();

        if ($contact instanceof Contact) {
            $entity->addContact($contact);
        }

        if ('' === $additionalContacts) {
            return;
        }

        if (is_string($additionalContacts)) {
            $contactIds = json_decode($additionalContacts, true, 512, JSON_THROW_ON_ERROR);
        }

        $contactIds = array_map('\intval', $contactIds ?? $additionalContacts);

        foreach ($contactIds as $contactId) {
            /** @var Contact $additionalContact */
            $additionalContact = $this->contactRepository->findByUid($contactId);

            if (null !== $additionalContact && !$entity->getContacts()
                    ->contains($additionalContact)) {
                $entity->addContact($additionalContact);
            }
        }
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
    public function updateSlug(Contact $contact): void
    {
        $slug = SlugUtility::generateUniqueSlug(
            $contact->getUid(),
            $this->tcaService->convertClassNameToTableName(Contact::class)
        );

        if (false !== $slug) {
            $contact->setSlug($slug);
        }

        $this->contactRepository->update($contact);
    }
}
