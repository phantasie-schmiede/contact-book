<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbContacts\Domain\Model\Address;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\ContactGroup;
use PSB\PsbContacts\Domain\Model\ContactRelationInterface;
use PSB\PsbContacts\Domain\Model\Document;
use PSB\PsbContacts\Domain\Model\EMail;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Model\PhoneNumber;
use PSB\PsbContacts\Domain\Model\Website;
use PSB\PsbContacts\Domain\Repository\AddressRepository;
use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Domain\Repository\DocumentRepository;
use PSB\PsbContacts\Domain\Repository\EMailRepository;
use PSB\PsbContacts\Domain\Repository\EventRepository;
use PSB\PsbContacts\Domain\Repository\PhoneNumberRepository;
use PSB\PsbContacts\Domain\Repository\WebsiteRepository;
use PSB\PsbContacts\Service\CleanupService;
use PSB\PsbContacts\Service\ContactGroupService;
use PSB\PsbContacts\Service\ContactService;
use PSB\PsbContacts\Service\VCardService;
use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Exceptions\MisconfiguredTcaException;
use PSB\PsbFoundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSB\PsbFoundation\Service\GlobalVariableService;
use PSB\PsbFoundation\Service\UploadService;
use PSB\PsbFoundation\Traits\ControllerCacheTrait;
use PSB\PsbFoundation\Utility\FileUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use function is_array;

/**
 * Class ContactController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactController extends ActionController
{
    use ControllerCacheTrait;

    public const string CACHE_TAG = 'tx_psbcontacts';

    public function __construct(
        protected readonly AddressRepository      $addressRepository,
        protected readonly CleanupService         $cleanupService,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactRepository      $contactRepository,
        protected readonly ContactService         $contactService,
        protected readonly DocumentRepository     $documentRepository,
        protected readonly EMailRepository        $eMailRepository,
        protected readonly EventRepository        $eventRepository,
        protected readonly ExtensionInformation   $extensionInformation,
        protected readonly PersistenceManager     $persistenceManager,
        protected readonly PhoneNumberRepository  $phoneNumberRepository,
        protected readonly UploadService          $uploadService,
        protected readonly VCardService           $vCardService,
        protected readonly WebsiteRepository      $websiteRepository,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Contact $contact): ResponseInterface
    {
        $this->processRelations($contact);
        $this->contactRepository->add($contact);
        $this->persistenceManager->persistAll();
        $this->contactService->updateSlug($contact);
        $this->deletePagesCache(self::CACHE_TAG);

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(Contact $contact): ResponseInterface
    {
        $this->contactRepository->remove($contact);
        $this->deletePagesCache(self::CACHE_TAG);

        return $this->redirect('list');
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function editAction(Contact $contact): ResponseInterface
    {
        $this->prepareFormView($contact);

        return $this->htmlResponse();
    }

    #[NoReturn]
    #[PluginAction(uncached: true)]
    public function exportAction(): void
    {
        $vCards = [];
        $contactDemand = new ContactDemand();
        $contactDemand->setHasPhoneNumber(true);
        $allContacts = $this->contactRepository->findByDemand($contactDemand);

        foreach ($allContacts as $contact) {
            $vCards[] = $this->vCardService->getVCard($contact);
        }

        FileUtility::initiateDownload(
            'text/vcard',
            implode('', $vCards),
            'allContacts.vcf',
        );

        exit;
    }

    #[PluginAction(default: true)]
    public function listAction(): ResponseInterface
    {
        $this->setCacheTags(self::CACHE_TAG);
        $this->view->assignMultiple([
            'contacts'  => ContactUtility::sortByDisplayName(
                $this->contactRepository->findAll()
                    ->toArray()
            ),
            'pluginUid' => $this->request->getAttribute('currentContentObject')->data['uid'],
        ]);

        return $this->htmlResponse();
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function newAction(Contact $contact = null): ResponseInterface
    {
        $this->prepareFormView($contact);

        return $this->htmlResponse();
    }

    #[PluginAction]
    public function showAction(Contact $contact): ResponseInterface
    {
        $this->setCacheTags(self::CACHE_TAG);
        $this->view->assign('contact', $contact);

        return $this->htmlResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Contact $contact): ResponseInterface
    {
        $this->processRelations($contact);
        $this->contactRepository->update($contact);
        $this->persistenceManager->persistAll();
        $this->contactService->updateSlug($contact);
        $this->cleanupService->cleanAll();
        $this->deletePagesCache(self::CACHE_TAG);

        return $this->redirect(
            actionName: 'show',
            arguments: ['contact' => $contact]
        );
    }

    /**
     * @throws JsonException
     */
    private function prepareFormView(?Contact $contact = null): void
    {
        $allCompaniesDemand = new ContactDemand();
        $allCompaniesDemand->addType(ContactType::Company);

        $allContactsDemand = new ContactDemand();
        $allContactsDemand->addType(ContactType::Person);

        if ($contact instanceof Contact) {
            $allContactsDemand->addExcludedContact($contact);
        }

        $this->view->assignMultiple([
            'allCompanies'     => $this->contactService->getAsJson($allCompaniesDemand),
            'allContactGroups' => $this->contactGroupService->getAsJson(),
            'allContacts'      => $this->contactService->getAsJson($allContactsDemand),
            'companies'        => $this->contactRepository->findByType(ContactType::Company),
            'contact'          => $contact,
            'contactTypes'     => ContactType::toArray(ContactType::Person),
            'eventTypes'       => EventType::toArray(EventType::Birthday),
            'generalTypes'     => GeneralType::toArray(GeneralType::Private),
            'phoneNumberTypes' => PhoneNumberType::toArray(PhoneNumberType::Mobile),
        ]);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    private function processRelations(Contact $contact): void
    {
        $contact->removeAllCompanies();
        $companyUids = json_decode($this->request->getArgument('companies') ?: '[]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($companyUids as $companyUid) {
            $company = $this->contactRepository->findByUid($companyUid);

            if ($company instanceof Contact) {
                $contact->addCompany($company);
            }
        }

        $contact->removeAllContactGroups();
        $contactGroupUids = json_decode(
            $this->request->getArgument('contactGroups') ?: '[]',
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($contactGroupUids as $contactGroupUid) {
            $contactGroup = $this->contactGroupRepository->findByUid($contactGroupUid);

            if ($contactGroup instanceof ContactGroup) {
                $contact->addContactGroup($contactGroup);
            }
        }

        foreach ([
                     'address',
                     'document',
                     'eMail',
                     'event',
                     'phoneNumber',
                     'website',
                 ] as $relationType) {
            $existingEntities = match ($relationType) {
                'address'     => clone $contact->getAddresses(),
                'document'    => clone $contact->getDocuments(),
                'eMail'       => clone $contact->getEMails(),
                'event'       => clone $contact->getEvents(),
                'phoneNumber' => clone $contact->getPhoneNumbers(),
                'website'     => clone $contact->getWebsites(),
            };

            $updatedEntities = $this->request->hasArgument($relationType) ? $this->request->getArgument(
                $relationType
            ) : [];
            $newEntities = GlobalVariableService::get(RequestParameterProvider::class . '.' . $relationType, false);

            if (is_array($updatedEntities)) {
                foreach ($updatedEntities as $uid => $entityData) {
                    $entity = match ($relationType) {
                        'address'     => $contact->getAddressByUid($uid),
                        'document'    => $contact->getDocumentByUid($uid),
                        'eMail'       => $contact->getEMailByUid($uid),
                        'event'       => $contact->getEventByUid($uid),
                        'phoneNumber' => $contact->getPhoneNumberByUid($uid),
                        'website'     => $contact->getWebsiteByUid($uid),
                    };
                    $this->updateEntityProperties($contact, $entity, $entityData);
                    $existingEntities->detach($entity);
                }
            }

            if (0 < $existingEntities->count()) {
                // Detach entities that were removed.
                foreach ($existingEntities as $entity) {
                    switch ($relationType) {
                        case 'address':
                            $contact->removeAddress($entity);
                            break;
                        case 'document':
                            $contact->removeDocument($entity);
                            break;
                        case 'eMail':
                            $contact->removeEMail($entity);
                            break;
                        case 'event':
                            $contact->removeEvent($entity);
                            break;
                        case 'phoneNumber':
                            $contact->removePhoneNumber($entity);
                            break;
                        case 'website':
                            $contact->removeWebsite($entity);
                            break;
                    }
                }
            }

            if (is_array($newEntities)) {
                foreach ($newEntities as $tempUid => $entityData) {
                    switch ($relationType) {
                        case 'address':
                            $address = new Address();
                            $this->updateEntityProperties($contact, $address, $entityData);
                            $this->addressRepository->add($address);
                            break;
                        case 'document':
                            $document = new Document();
                            $this->documentRepository->add($document);
                            $this->persistenceManager->persistAll();
                            $this->updateEntityProperties($contact, $document, $entityData, $tempUid);
                            $this->documentRepository->update($document);
                            break;
                        case 'eMail':
                            $eMail = new EMail();
                            $this->updateEntityProperties($contact, $eMail, $entityData);
                            $this->eMailRepository->add($eMail);
                            break;
                        case 'event':
                            $event = new Event();
                            $this->updateEntityProperties($contact, $event, $entityData);
                            $this->eventRepository->add($event);
                            break;
                        case 'phoneNumber':
                            $phoneNumber = new PhoneNumber();
                            $this->updateEntityProperties($contact, $phoneNumber, $entityData);
                            $this->phoneNumberRepository->add($phoneNumber);
                            break;
                        case 'website':
                            $website = new Website();
                            $this->updateEntityProperties($contact, $website, $entityData);
                            $this->websiteRepository->add($website);
                            break;
                    }
                }
            }
        }
    }

    private function updateAddressProperties(Address $entity, array $entityData): void
    {
        $entity->setCity($entityData['city']);
        $entity->setCountry($entityData['country']);
        $entity->setCounty($entityData['county']);
        $entity->setHouseNumber((string)$entityData['houseNumber']);
        $entity->setNotes($entityData['notes']);
        $entity->setStreet($entityData['street']);
        $entity->setType(GeneralType::from($entityData['type']));
        $entity->setZipCode((string)$entityData['zipCode']);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    private function updateDocumentProperties(Document $entity, array $entityData, int $tempUid = null): void
    {
        if (null !== $tempUid) {
            $uploadedFiles = ServerRequestFactory::fromGlobals()
                ->getUploadedFiles();

            if (isset($uploadedFiles['document'][$tempUid]['file'])) {
                $uploadedFile = $uploadedFiles['document'][$tempUid]['file'];
                $this->uploadService->uploadForDomainObject(
                    $entity,
                    'file',
                    $uploadedFile
                );
            }
        }

        $entity->setNotes($entityData['notes']);
        $entity->setTitle($entityData['title']);
    }

    private function updateEMailProperties(EMail $entity, array $entityData): void
    {
        $entity->setEMailAddress($entityData['eMailAddress']);
        $entity->setNotes($entityData['notes']);
        $entity->setType(GeneralType::from($entityData['type']));
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    private function updateEntityProperties(
        Contact                  $contact,
        ContactRelationInterface $entity,
        array                    $entityData,
        int                      $tempUid = null,
    ): void {
        $this->contactService->setContactsOfRelatedEntity($entity, $contact, $entityData['contacts']);

        switch (true) {
            case $entity instanceof Address:
                $this->updateAddressProperties($entity, $entityData);
                break;
            case $entity instanceof Document:
                $this->updateDocumentProperties($entity, $entityData, $tempUid);
                break;
            case $entity instanceof EMail:
                $this->updateEMailProperties($entity, $entityData);
                break;
            case $entity instanceof Event:
                $this->updateEventProperties($entity, $entityData);
                break;
            case $entity instanceof PhoneNumber:
                $this->updatePhoneNumberProperties($entity, $entityData);
                break;
            case $entity instanceof Website:
                $this->updateWebsiteProperties($entity, $entityData);
                break;
        }
    }

    /**
     * @throws Exception
     */
    private function updateEventProperties(Event $entity, array $entityData): void
    {
        $entity->setDate(new DateTime($entityData['date']));
        $entity->setNotes($entityData['notes']);
        $entity->setShowAnniversaries((bool)$entityData['showAnniversaries']);
        $entity->setShowInCalendar((bool)$entityData['showInCalendar']);
        $entity->setType(EventType::from($entityData['type']));
        $entity->setYearUnknown((bool)$entityData['yearUnknown']);
    }

    private function updatePhoneNumberProperties(PhoneNumber $entity, array $entityData): void
    {
        $entity->setAreaCode((string)$entityData['areaCode']);
        $entity->setConnectionIdentifier((string)$entityData['connectionIdentifier']);
        $entity->setExtension((string)$entityData['extension']);
        $entity->setGeneralType(GeneralType::from($entityData['generalType']));
        $entity->setInternationalDiallingCode((string)$entityData['internationalDiallingCode']);
        $entity->setNotes($entityData['notes']);
        $entity->setPhoneNumberType(PhoneNumberType::from($entityData['phoneNumberType']));
    }

    private function updateWebsiteProperties(Website $entity, array $entityData): void
    {
        $entity->setNotes($entityData['notes']);
        $entity->setType(GeneralType::from($entityData['type']));
        $entity->setUrl($entityData['url']);
    }
}
