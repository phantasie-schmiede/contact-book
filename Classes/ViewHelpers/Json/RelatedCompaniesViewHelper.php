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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class RelatedCompaniesViewHelper
 *
 * @package PSBits\ContactBook\ViewHelpers\Json
 */
class RelatedCompaniesViewHelper extends AbstractViewHelper
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
        /** @var Contact|null $contact */
        $contact = $this->arguments['contact'];

        if (!$contact instanceof Contact) {
            return '';
        }

        $data = [];

        /** @var Contact $company */
        foreach ($contact->getCompanies() as $company) {
            $data[] = [
                'label' => $company->getDisplayName(),
                'value' => $company->getUid(),
            ];
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
