<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service;

use Doctrine\DBAL\Exception;
use JsonException;
use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\ContactRelationInterface;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Utility\ContactUtility;
use PSB\PsbFoundation\Service\Configuration\TcaService;
use PSB\PsbTemplates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use function is_string;

/**
 * Class ContactService
 *
 * @package PSB\PsbContacts\Service
 */
readonly class ContactService
{
    public function __construct(
        protected ContactRepository $contactRepository,
        protected TcaService        $tcaService,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function getAsJson(ContactDemand $demand = null, Contact ...$contacts): string
    {
        if (!$demand instanceof ContactDemand && empty($contacts)) {
            $demand = new ContactDemand();
        }

        return json_encode(
            array_map(
                static fn(Contact $contact) => [
                    'label'       => $contact->getDisplayName(),
                    'searchTerms' => $contact->getSearchTerms(),
                    'value'       => $contact->getUid(),
                ],
                ContactUtility::sortByDisplayName(
                    $contacts ?: $this->contactRepository->findByDemand($demand)
                        ->toArray()
                )
            ),
            JSON_THROW_ON_ERROR
        );
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
            $additionalContact = $this->contactRepository->findByUid($contactId);

            if (null !== $additionalContact) {
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
