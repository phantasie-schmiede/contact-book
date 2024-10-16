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
use PSB\PsbContacts\Domain\Model\ContactGroup;
use PSB\PsbContacts\Domain\Repository\ContactGroupRepository;
use PSB\PsbFoundation\Service\Configuration\TcaService;
use PSB\PsbTemplates\Utility\SlugUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

/**
 * Class ContactGroupService
 *
 * @package PSB\PsbContacts\Service
 */
readonly class ContactGroupService
{
    public function __construct(
        protected ContactGroupRepository $contactGroupRepository,
        protected TcaService             $tcaService,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function getAsJson(): string
    {
        return json_encode(
            array_map(
                static fn(ContactGroup $contactGroup) => [
                    'label' => $contactGroup->getName(),
                    'value' => $contactGroup->getUid(),
                ],
                $this->contactGroupRepository->findAll()
                    ->toArray()
            ),
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws IllegalObjectTypeException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SiteNotFoundException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function updateSlug(ContactGroup $contactGroup): void
    {
        $slug = SlugUtility::generateUniqueSlug(
            $contactGroup->getUid(),
            $this->tcaService->convertClassNameToTableName(ContactGroup::class)
        );

        if (false !== $slug) {
            $contactGroup->setSlug($slug);
        }

        $this->contactGroupRepository->update($contactGroup);
    }
}
