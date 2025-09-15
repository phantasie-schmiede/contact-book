<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use Doctrine\DBAL\Exception as DbalException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Domain\Model\ContactRelationInterface;
use PSBits\ContactBook\Domain\Model\Document;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\ContactBook\Domain\Model\Job;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\ContactBook\Domain\Repository\AddressRepository;
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Domain\Repository\DocumentRepository;
use PSBits\ContactBook\Domain\Repository\EMailRepository;
use PSBits\ContactBook\Domain\Repository\EventRepository;
use PSBits\ContactBook\Domain\Repository\JobRepository;
use PSBits\ContactBook\Domain\Repository\PhoneNumberRepository;
use PSBits\ContactBook\Domain\Repository\WebsiteRepository;
use PSBits\ContactBook\Service\CleanupService;
use PSBits\ContactBook\Service\ContactGroupService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\ContactBook\Service\Enum\VCardVersion;
use PSBits\ContactBook\Service\VCardService;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\Foundation\Attribute\PluginAction;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Service\UploadService;
use PSBits\Foundation\Traits\ControllerCacheTrait;
use PSBits\Foundation\Utility\FileUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use function is_array;

/**
 * Class ContactController
 *
 * @package PSBits\ContactBook\Controller
 */
class ContactController extends ActionController
{
    use ControllerCacheTrait;

    public const string CACHE_TAG = 'tx_contactbook_contacts';

    public function __construct(
        protected readonly AddressRepository      $addressRepository,
        protected readonly CleanupService         $cleanupService,
        protected readonly ContactBookRepository  $contactBookRepository,
        protected readonly ContactGroupRepository $contactGroupRepository,
        protected readonly ContactGroupService    $contactGroupService,
        protected readonly ContactRepository      $contactRepository,
        protected readonly ContactService         $contactService,
        protected readonly DocumentRepository     $documentRepository,
        protected readonly EMailRepository        $eMailRepository,
        protected readonly EventRepository        $eventRepository,
        protected readonly ExtensionInformation   $extensionInformation,
        protected readonly JobRepository          $jobRepository,
        protected readonly PersistenceManager     $persistenceManager,
        protected readonly PhoneNumberRepository  $phoneNumberRepository,
        protected readonly UploadService          $uploadService,
        protected readonly VCardService           $vCardService,
        protected readonly ViewFactoryInterface   $viewFactory,
        protected readonly WebsiteRepository      $websiteRepository,
    ) {
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws DbalException
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Contact $contact): ResponseInterface
    {
        $this->processRelations($contact);
        $this->contactRepository->add($contact);
        $this->persistenceManager->persistAll();
        $this->contactService->updateSlug($contact);
        $this->deletePagesCache(self::CACHE_TAG);

        return $this->redirect(
            actionName: 'show',
            arguments: ['contact' => $contact]
        );
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(Contact $contact): ResponseInterface
    {
        $this->contactRepository->remove($contact);
        $this->deletePagesCache(self::CACHE_TAG, self::CACHE_TAG . '_' . $contact->getUid());

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

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    #[NoReturn]
    #[PluginAction(uncached: true)]
    public function exportAction(?Contact $contact = null, ?VCardVersion $version = null): void
    {
        $vCards = [];

        if ($contact instanceof Contact) {
            $fileName = $contact->getFullName();
            $vCards[] = $this->vCardService->getVCard($contact, $version ?? VCardVersion::V4_0);
        } else {
            $contactDemand = new ContactDemand();
            $contactDemand->setOnlyExportRelevantInformation(true);
            $allContacts = $this->contactRepository->findByDemand($contactDemand);
            $fileName = LocalizationUtility::translate(
                'LLL:EXT:contact_book/Resources/Private/Language/Frontend/exportModal.xlf:allContacts'
            );

            foreach ($allContacts as $singleContact) {
                $vCards[] = $this->vCardService->getVCard($singleContact, $version ?? VCardVersion::V4_0);
            }
        }

        FileUtility::initiateDownload(
            'text/vcard',
            implode('', $vCards),
            FileUtility::sanitizeFileName($fileName) . '.vcf',
        );

        exit;
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function getAutoCompleteListAction(bool $companies = false, Contact $contact = null): ResponseInterface
    {
        $demand = new ContactDemand();

        if ($companies) {
            $demand->addType(ContactType::Company);
        }

        if ($contact) {
            $demand->addExcludedContact($contact);
        }

        $contacts = ContactUtility::sortByListName(
            $this->contactRepository->findByDemand($demand)
                ->toArray()
        );

        return $this->jsonResponse(
            json_encode(['html' => $this->contactService->renderAutocompleteList($contacts, $this->request)],
                JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws Exception
     */
    #[PluginAction(default: true, uncached: true)]
    public function listAction(): ResponseInterface
    {
        if (0 === $this->contactBookRepository->findByOwner()
                ->count()) {
            return $this->redirect('list', 'ContactBook');
        }

        $list = $this->contactService->getCachedList('list');

        if (false === $list) {
            $list = $this->getList();
        }

        $this->view->assignMultiple([
            'list'       => $list,
            'pluginUid'  => $this->request->getAttribute('currentContentObject')->data['uid'],
            'totalCount' => $this->contactRepository->findByDemand(new ContactDemand())
                ->count(),
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

    /**
     * @throws JsonException
     */
    #[PluginAction(uncached: true)]
    public function refreshListAction(): ResponseInterface
    {
        return $this->jsonResponse(json_encode(['html' => $this->getList()], JSON_THROW_ON_ERROR));
    }

    #[PluginAction]
    public function showAction(Contact $contact): ResponseInterface
    {
        $this->setCacheTags(self::CACHE_TAG . '_' . $contact->getUid());
        $this->view->assign('contact', $contact);

        return $this->htmlResponse();
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws DbalException
     * @throws IllegalObjectTypeException
     * @throws JsonException
     * @throws MisconfiguredTcaException
     * @throws NoSuchCacheGroupException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
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
        $this->deletePagesCache(self::CACHE_TAG, self::CACHE_TAG . '_' . $contact->getUid());

        return $this->redirect(
            actionName: 'show',
            arguments: ['contact' => $contact]
        );
    }

    private function getList(): string
    {
        // Find all contacts.
        $contacts = ContactUtility::sortByListName(
            $this->contactRepository->findByDemand(new ContactDemand())
                ->toArray()
        );

        return $this->contactService->renderListAndUpdateCache('list', $contacts, $this->request);
    }

    /**
     * @throws JsonException
     */
    private function prepareFormView(?Contact $contact = null): void
    {
        $this->view->assignMultiple([
            'allContactGroups' => $this->contactGroupRepository->findAll(),
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
                     'job',
                     'phoneNumber',
                     'website',
                 ] as $relationType) {
            $existingEntities = match ($relationType) {
                'address'     => clone $contact->getAddresses(),
                'document'    => clone $contact->getDocuments(),
                'eMail'       => clone $contact->getEMails(),
                'event'       => clone $contact->getEvents(),
                'job'         => clone $contact->getJobs(),
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
                        'job'         => $contact->getJobByUid($uid),
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
                        case 'job':
                            $contact->removeJob($entity);
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
                        case 'job':
                            $job = new Job();
                            $this->updateEntityProperties($contact, $job, $entityData);
                            $this->jobRepository->add($job);
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

    private function updateAddressProperties(Address $address, array $entityData): void
    {
        $address->setCity($entityData['city'])
            ->setCountry($entityData['country'])
            ->setCounty($entityData['county'])
            ->setHouseNumber((string)$entityData['houseNumber'])
            ->setNotes($entityData['notes'])
            ->setStreet($entityData['street'])
            ->setType(GeneralType::from($entityData['type']))
            ->setZipCode((string)$entityData['zipCode']);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws PropertyNotAccessibleException
     * @throws ReflectionException
     */
    private function updateDocumentProperties(Document $document, array $entityData, int $tempUid = null): void
    {
        if (null !== $tempUid) {
            $uploadedFiles = ServerRequestFactory::fromGlobals()
                ->getUploadedFiles();

            if (isset($uploadedFiles['document'][$tempUid]['file'])) {
                $uploadedFile = $uploadedFiles['document'][$tempUid]['file'];
                $this->uploadService->uploadForDomainObject(
                    $document,
                    'file',
                    $uploadedFile
                );
            }
        }

        $document->setNotes($entityData['notes'])
            ->setTitle($entityData['title']);
    }

    private function updateEMailProperties(EMail $eMail, array $entityData): void
    {
        $eMail->setEMailAddress($entityData['eMailAddress'])
            ->setNotes($entityData['notes'])
            ->setType(GeneralType::from($entityData['type']));
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
        $this->contactService->setContactsOfRelatedEntity($entity, $contact, $entityData['contacts'] ?? []);

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
            case $entity instanceof Job:
                $this->updateJobProperties($entity, $entityData);
                break;
            case $entity instanceof PhoneNumber:
                $this->updatePhoneNumberProperties($entity, $entityData);
                break;
            case $entity instanceof Website:
                $this->updateWebsiteProperties($entity, $entityData);
                break;
        }
    }

    private function updateEventProperties(Event $event, array $entityData): void
    {
        $event->setDay($entityData['day'] ? (int)$entityData['day'] : null)
            ->setLocation($entityData['location'])
            ->setMonth($entityData['month'] ? (int)$entityData['month'] : null)
            ->setNotes($entityData['notes'])
            ->setShowAnniversaries((bool)$entityData['showAnniversaries'])
            ->setShowInCalendar((bool)$entityData['showInCalendar'])
            ->setType(EventType::from($entityData['type']))
            ->setYear($entityData['year'] ? (int)$entityData['year'] : null);
    }

    private function updateJobProperties(Job $job, array $entityData): void
    {
        $job->setCompany($entityData['company'] ? $this->contactRepository->findByUid($entityData['company']) : null)
            ->setNotes($entityData['notes'])
            ->setPosition($entityData['position'])
            ->setRole($entityData['role']);
    }

    private function updatePhoneNumberProperties(PhoneNumber $phoneNumber, array $entityData): void
    {
        $phoneNumber->setAreaCode((string)$entityData['areaCode'])
            ->setConnectionIdentifier((string)$entityData['connectionIdentifier'])
            ->setExtension((string)$entityData['extension'])
            ->setGeneralType(GeneralType::from($entityData['generalType']))
            ->setInternationalDiallingCode((string)$entityData['internationalDiallingCode'])
            ->setNotes($entityData['notes'])
            ->setPhoneNumberType(PhoneNumberType::from($entityData['phoneNumberType']));
    }

    private function updateWebsiteProperties(Website $website, array $entityData): void
    {
        $website->setNotes($entityData['notes'])
            ->setType(GeneralType::from($entityData['type']))
            ->setUrl($entityData['url']);
    }
}
