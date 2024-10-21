<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\ViewHelpers\Json;

use JsonException;
use PSB\PsbContacts\Domain\Model\Contact;
use PSB\PsbContacts\Domain\Model\ContactRelationInterface;
use PSB\PsbContacts\Utility\ContactUtility;
use RuntimeException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class RelatedContactsViewHelper
 *
 * @package PSB\PsbContacts\ViewHelpers\Json
 */
class RelatedContactsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('contact', 'object', 'contact entity that should be excluded from result');
        $this->registerArgument(
            'relatedEntity',
            'object',
            'domain object that implements ContactRelationInterface',
            true
        );
    }

    /**
     * @throws JsonException
     */
    public function render(): string
    {
        $contact = $this->arguments['contact'];
        $relatedEntity = $this->arguments['relatedEntity'];

        if (null === $relatedEntity) {
            return '';
        }

        if (!$relatedEntity instanceof ContactRelationInterface) {
            throw new RuntimeException(
                __CLASS__ . ': object passed as relatedEntity must implement ContactRelationInterface!', 1724149766
            );
        }

        $relatedContacts = $relatedEntity->getContacts();

        if ($contact instanceof Contact) {
            $relatedContacts->detach($contact);
        }

        $contacts = ContactUtility::sortByDisplayName($relatedContacts->toArray());
        $data = [];

        /** @var Contact $contact */
        foreach ($contacts as $contact) {
            $data[] = [
                'label' => $contact->getDisplayName(),
                'value' => $contact->getUid(),
            ];
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
