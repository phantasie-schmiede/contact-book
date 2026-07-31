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
use PSBits\Templates\Service\ViewService;
use PSBits\Templates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use function is_string;

/**
 * Class ContactService
 *
 * @package PSBits\ContactBook\Service
 */
readonly class ContactService
{
    public function __construct(
        protected ContactRepository    $contactRepository,
        protected ExtensionInformation $extensionInformation,
        protected FrontendInterface    $frontendCache,
        protected PersistenceManager   $persistenceManager,
        protected TcaService           $tcaService,
        protected ViewService          $viewService,
    ) {
    }

    public function getCachedList(string $cacheEntryIdentifier): false|string
    {
        return $this->frontendCache->get($cacheEntryIdentifier);
    }

    /**
     * @param Contact[]        $contacts
     * @param RequestInterface $request
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function renderAutocompleteList(
        array            $contacts,
        RequestInterface $request,
    ): string {
        $view = $this->viewService->createStandaloneView(
            $this->extensionInformation,
            $request,
            'Ajax/AutocompleteList'
        );
        $view->assign('contacts', $contacts);

        return $view->render();
    }

    /**
     * @param string           $cacheEntryIdentifier
     * @param Contact[]        $contacts
     * @param RequestInterface $request
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function renderListAndUpdateCache(
        string           $cacheEntryIdentifier,
        array            $contacts,
        RequestInterface $request,
    ): string {
        $view = $this->viewService->createStandaloneView($this->extensionInformation, $request, 'Cache/ContactList');
        $view->assign('contacts', $contacts);

        $list = $view->render();
        $this->frontendCache->set($cacheEntryIdentifier, $list, [], 0);

        return $list;
    }

    /**
     * @param ContactRelationInterface $entity
     * @param Contact|null             $contact
     * @param int[]|string             $additionalContacts
     *
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
            $contactIds = json_decode($additionalContacts, true, 2, JSON_THROW_ON_ERROR);
        }

        $contactIds = array_map('\intval', $contactIds ?? $additionalContacts);

        foreach ($contactIds as $contactId) {
            $additionalContact = $this->contactRepository->findByUid($contactId);

            if ($additionalContact instanceof Contact && !$entity->getContacts()
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
        $this->persistenceManager->persistAll();
    }
}
