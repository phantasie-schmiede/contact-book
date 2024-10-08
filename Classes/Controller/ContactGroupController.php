<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Controller;

use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class ContactGroupController
 *
 * @package PSB\PsbContacts\Controller
 */
class ContactGroupController extends ActionController
{
    public function __construct(
        protected readonly ContactGroupRepository $contactGroupRepository,
    ) {
    }
}
