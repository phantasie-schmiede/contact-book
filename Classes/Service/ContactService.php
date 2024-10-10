<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service;

use JsonException;
use PSB\PsbContacts\Domain\Demand\ContactDemand;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\ContactRelationInterface;
use PSB\PsbContacts\Domain\Repository\ContactRepository;
use PSB\PsbContacts\Utility\ContactUtility;
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
    ) {
    }

    /**
     * @param array $excludedContacts
     *
     * @return string
     * @throws JsonException
     */
    public function getAllAsJson(array $excludedContacts = []): string
    {
        $contactDemand = new ContactDemand();

        if ([] !== $excludedContacts) {
            foreach ($excludedContacts as $excludedContact) {
                $contactDemand->addExcludedContact($excludedContact);
            }
        }

        return json_encode(
            array_map(
                static fn(Contact $contact) => [
                    'label' => $contact->getDisplayName(),
                    'value' => $contact->getUid(),
                ],
                ContactUtility::sortByDisplayName(
                    $this->contactRepository->findByDemand($contactDemand)
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
}
