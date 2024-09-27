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
use JsonException;
use PSB\PsbContacts\Data\ExtensionInformation;
use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Enum\ContactType;
use PSB\PsbContacts\Domain\Enum\EventType;
use PSB\PsbContacts\Domain\Enum\GeneralType;
use PSB\PsbContacts\Domain\Enum\PhoneNumberType;
use PSB\PsbContacts\Domain\Model\Address;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\ContactRelationInterface;
use PSB\PsbContacts\Domain\Model\EMail;
use PSB\PsbContacts\Domain\Model\Event;
use PSB\PsbContacts\Domain\Model\PhoneNumber;
use PSB\PsbContacts\Domain\Model\Website;
use PSB\PsbContacts\Domain\Repository\AddressRepository;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Domain\Repository\EMailRepository;
use PSB\PsbContacts\Domain\Repository\EventRepository;
use PSB\PsbContacts\Domain\Repository\PhoneNumberRepository;
use PSB\PsbContacts\Domain\Repository\WebsiteRepository;
use PSB\PsbFoundation\Attribute\PluginAction;
use PSB\PsbFoundation\Service\GlobalVariableProviders\RequestParameterProvider;
use PSB\PsbFoundation\Service\GlobalVariableService;
use PSB\PsbFoundation\Utility\Configuration\FilePathUtility;
use PSB\PsbFoundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use function is_array;
use function is_string;

/**
 * Class ContactController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactController extends ActionController
{
    public const string CACHE_TAG = 'tx_psbcontacts';

    public function __construct(
        protected readonly AddressRepository     $addressRepository,
        protected readonly ContactRepository     $contactRepository,
        protected readonly EMailRepository       $eMailRepository,
        protected readonly EventRepository       $eventRepository,
        protected readonly ExtensionInformation  $extensionInformation,
        protected readonly PhoneNumberRepository $phoneNumberRepository,
        protected readonly WebsiteRepository     $websiteRepository,
    ) {
    }

    /**
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AspectNotFoundException
     */
    #[PluginAction(uncached: true)]
    public function confirmDeleteAction(Contact $contact): ResponseInterface
    {
        $this->view->assign('contact', $contact);
        $languageFilePath = FilePathUtility::getLanguageFilePath($this->extensionInformation);

        return $this->jsonResponse(json_encode([
            'body'   => LocalizationUtility::translate(
                key      : $languageFilePath . 'Frontend/confirmDeleteModal.xlf:body',
                arguments: ['name' => $contact->getDisplayName()]
            ),
            'footer' => $this->view->render(),
            'title'  => LocalizationUtility::translate($languageFilePath . 'Frontend/confirmDeleteModal.xlf:title'),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Contact $contact): ResponseInterface
    {
        $this->processRelations($contact);
        $this->contactRepository->add($contact);
        $this->deletePageCache();

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
        $this->deletePageCache();

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

    #[PluginAction(default: true)]
    public function listAction(): ResponseInterface
    {
        $this->setCacheTag();
        $allContacts = $this->contactRepository->findAll()
            ->toArray();

        // Sort contacts by displayName
        usort($allContacts, static fn(Contact $a, Contact $b) => $a->getDisplayName() <=> $b->getDisplayName());

        $this->view->assign('contacts', $allContacts);

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
        $this->setCacheTag();
        $this->view->assign('contact', $contact);

        return $this->htmlResponse();
    }

    /**
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheGroupException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Contact $contact): ResponseInterface
    {
        $this->processRelations($contact);
        $this->contactRepository->update($contact);
        $this->deletePageCache();

        return $this->redirect(
            actionName: 'show', arguments: ['contact' => $contact]
        );
    }

    /**
     * @throws NoSuchCacheGroupException
     */
    private function deletePageCache(): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesInGroupByTag('pages', self::CACHE_TAG);
    }

    /**
     * @throws JsonException
     */
    private function prepareFormView(?Contact $contact = null): void
    {
        $contactDemand = new ContactDemand();

        if ($contact instanceof Contact) {
            $contactDemand->addExcludedContact($contact);
        }

        $this->view->assignMultiple([
            'generalTypes'     => GeneralType::toArray(GeneralType::Private),
            'companies'        => $this->contactRepository->findByType(ContactType::Company),
            'contact'          => $contact,
            'allContacts'      => json_encode(
                array_map(
                    static fn(Contact $contact) => [
                        'label' => $contact->getDisplayName(),
                        'value' => $contact->getUid(),
                    ],
                    $this->contactRepository->findByDemand($contactDemand)
                        ->toArray()
                ),
                JSON_THROW_ON_ERROR
            ),
            'eventTypes'       => EventType::toArray(EventType::Birthday),
            'contactTypes'     => ContactType::toArray(ContactType::Person),
            'phoneNumberTypes' => PhoneNumberType::toArray(PhoneNumberType::Mobile),
        ]);
    }

    /**
     * @throws Exception
     */
    private function processRelations(Contact $contact): void
    {
        foreach ([
                     'address',
                     'eMail',
                     'event',
                     'phoneNumber',
                     'website',
                 ] as $relationType) {
            $existingEntities = match ($relationType) {
                'address'     => clone $contact->getAddresses(),
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
                foreach ($newEntities as $entityData) {
                    switch ($relationType) {
                        case 'address':
                            $address = new Address();
                            $this->updateEntityProperties($contact, $address, $entityData);
                            $this->addressRepository->add($address);
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

    private function setCacheTag(): void
    {
        /** @var CacheDataCollector $cacheDataCollector */
        $cacheDataCollector = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector');
        $cacheDataCollector->addCacheTags(new CacheTag(self::CACHE_TAG));
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

    private function updateEMailProperties(EMail $entity, array $entityData): void
    {
        $entity->setEMailAddress($entityData['eMailAddress']);
        $entity->setNotes($entityData['notes']);
        $entity->setType(GeneralType::from($entityData['type']));
    }

    /**
     * @throws Exception
     */
    private function updateEntityProperties(Contact $contact, ContactRelationInterface $entity, array $entityData): void
    {
        $entity->removeAllContacts();
        $entity->addContact($contact);

        if (!empty($entityData['contacts'])) {
            if (is_string($entityData['contacts'])) {
                $entityData['contacts'] = json_decode($entityData['contacts'], true, 512, JSON_THROW_ON_ERROR);
            }

            $contactIds = array_map('\intval', $entityData['contacts']);

            foreach ($contactIds as $contactId) {
                $additionalContact = $this->contactRepository->findByUid($contactId);

                if (null !== $additionalContact) {
                    $entity->addContact($additionalContact);
                }
            }
        }

        switch (true) {
            case $entity instanceof Address:
                $this->updateAddressProperties($entity, $entityData);
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
