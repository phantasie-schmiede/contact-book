<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Controller;

use JsonException;
use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Enum\FilterMode;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\Foundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class ContactCardController
 *
 * @package PSBits\ContactBook\Controller
 */
class ContactCardController extends ActionController
{
    public function __construct(
        protected readonly ContactRepository $contactRepository,
    ) {
    }

    /**
     * @throws JsonException
     */
    #[PluginAction(default: true, uncached: true)]
    public function displayAction(): ResponseInterface
    {
        $demand = GeneralUtility::makeInstance(ContactDemand::class);
        $demand->unsetContactBookUid()
            ->setRespectStoragePage(true);
        $filterMode = FilterMode::from($this->settings['filterMode']);

        switch ($filterMode) {
            case FilterMode::Categories:
                $demand->setCategoryUids(...GeneralUtility::intExplode(',', $this->settings['categories'], true));

                break;
            case FilterMode::ContactGroup:
                $demand->setContactGroupUid((int)$this->settings['contactGroup']);

                break;
            case FilterMode::Contacts:
                $demand->setContactUids(...GeneralUtility::intExplode(',', $this->settings['contacts'], true));

                break;
        }

        $this->view->assign('contacts', $this->contactRepository->findByDemand($demand));

        return $this->htmlResponse();
    }
}
