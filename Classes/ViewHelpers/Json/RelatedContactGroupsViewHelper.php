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
use PSB\PsbContacts\Domain\Model\ContactGroup;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class RelatedContactGroupsViewHelper
 *
 * @package PSB\PsbContacts\ViewHelpers\Json
 */
class RelatedContactGroupsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'contact',
            Contact::class,
            'a record of type Contact',
            true
        );
    }

    /**
     * @throws JsonException
     */
    public function render(): string
    {
        /** @var Contact $contact */
        $contact = $this->arguments['contact'];
        $data = [];

        /** @var ContactGroup $contactGroup */
        foreach ($contact->getContactGroups() as $contactGroup) {
            $data[] = [
                'label' => $contactGroup->getName(),
                'value' => $contactGroup->getUid(),
            ];
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
