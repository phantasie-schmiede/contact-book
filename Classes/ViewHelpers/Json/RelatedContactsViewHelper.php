<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\ViewHelpers\Json;

use JsonException;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactRelationInterface;
use PSBits\ContactBook\Utility\ContactUtility;
use RuntimeException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class RelatedContactsViewHelper
 *
 * @package PSBits\ContactBook\ViewHelpers\Json
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

        $contacts = ContactUtility::sortByListName($relatedContacts->toArray());
        $data = [];

        /** @var Contact $contact */
        foreach ($contacts as $contact) {
            $data[] = [
                'label' => $contact->getListName(),
                'value' => $contact->getUid(),
            ];
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
