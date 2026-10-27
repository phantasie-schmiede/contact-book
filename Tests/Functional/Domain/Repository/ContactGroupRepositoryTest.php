<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class ContactGroupRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class ContactGroupRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/friendsoftypo3/content-blocks',
        'typo3conf/ext/psbits/contact-book',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/frontend-templates',
        'typo3conf/ext/psbits/frontend-users',
        'typo3conf/ext/psbits/view-helpers',
        'typo3conf/ext/wapplersystems/ws-scss',
    ];

    #[Test]
    public function groupsOfTheActiveContactBookAreReturned(): void
    {
        $alice = $this->createFrontendUser('alice@example.org');

        $bookOne = $this->createContactBook('Book One', $alice);
        $bookTwo = $this->createContactBook('Book Two', $alice);
        $groupOne = $this->createContactGroup('Family', $bookOne);
        $groupTwo = $this->createContactGroup('Work', $bookTwo);
        $this->persistAll($alice, $bookOne, $bookTwo, $groupOne, $groupTwo);

        $alice->setSetting('activeContactBook', $bookOne->getUid());
        $this->seedCurrentFrontendUser($alice);

        $repository = GeneralUtility::makeInstance(ContactGroupRepository::class);

        self::assertSame(
            [$groupOne->getUid()],
            $this->getUids($repository->findAllOfActiveContactBook())
        );
    }

    private function createFrontendUser(string $email): FrontendUser
    {
        $user = new FrontendUser();
        $user->setUsername((string)str_replace('@example.org', '', $email))
            ->setEmail($email);

        return $user;
    }

    private function createContactBook(string $name, FrontendUser ...$owners): ContactBook
    {
        $contactBook = new ContactBook();
        $contactBook->setName($name);

        foreach ($owners as $owner) {
            $contactBook->addOwner($owner);
        }

        return $contactBook;
    }

    private function createContactGroup(string $name, ContactBook $contactBook): ContactGroup
    {
        $contactGroup = new ContactGroup();
        $contactGroup->setName($name)
            ->setContactBook($contactBook);

        return $contactGroup;
    }

    /**
     * @throws ReflectionException
     */
    private function seedCurrentFrontendUser(FrontendUser $user): void
    {
        $property = new ReflectionProperty(GlobalVariableService::class, 'cachedVariables');
        $property->setValue(null, [
            FrontendUserProvider::class => [
                'loginPage' => null,
                'sysFolder' => null,
                'user'      => $user,
            ],
        ]);
    }

    /**
     * @param object[] $objects
     */
    private function persistAll(object ...$objects): void
    {
        $persistenceManager = $this->get(PersistenceManager::class);
        foreach ($objects as $object) {
            $persistenceManager->add($object);
        }

        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    /**
     * @return list<int>
     */
    private function getUids(iterable $result): array
    {
        $uids = [];
        foreach ($result as $contactGroup) {
            $uids[] = $contactGroup->getUid();
        }

        sort($uids);

        return $uids;
    }
}
