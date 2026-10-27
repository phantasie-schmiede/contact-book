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
use PSBits\ContactBook\Domain\Repository\ContactBookRepository;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use PSBits\FrontendUsers\Service\GlobalVariableProviders\FrontendUserProvider;
use PSBits\Qa\Testing\Support\PersistenceSupportTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class ContactBookRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class ContactBookRepositoryTest extends FunctionalTestCase
{
    use PersistenceSupportTrait;

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
    public function contactBooksOwnedByTheGivenFrontendUserAreReturned(): void
    {
        $alice = $this->createFrontendUser('alice@example.org');
        $bob   = $this->createFrontendUser('bob@example.org');

        $bookOne = $this->createContactBook('Book One', $alice);
        $bookTwo = $this->createContactBook('Book Two', $alice, $bob);
        $bookThree = $this->createContactBook('Book Three', $bob);
        $this->persistAll($alice, $bob, $bookOne, $bookTwo, $bookThree);

        $repository = GeneralUtility::makeInstance(ContactBookRepository::class);
        $uids       = $this->getUids($repository->findByOwner($alice));

        self::assertContains($bookOne->getUid(), $uids);
        self::assertContains($bookTwo->getUid(), $uids);
        self::assertNotContains($bookThree->getUid(), $uids);
    }

    #[Test]
    public function theCurrentFrontendUserIsUsedWithoutAnExplicitUser(): void
    {
        $alice = $this->createFrontendUser('alice@example.org');
        $bob   = $this->createFrontendUser('bob@example.org');

        $bookOne = $this->createContactBook('Book One', $alice);
        $bookTwo = $this->createContactBook('Book Two', $bob);
        $this->persistAll($alice, $bob, $bookOne, $bookTwo);

        $this->seedCurrentFrontendUser($alice);

        $repository = GeneralUtility::makeInstance(ContactBookRepository::class);

        self::assertSame([$bookOne->getUid()], $this->getUids($repository->findByOwner()));
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
     * @return list<int>
     */
    private function getUids(iterable $result): array
    {
        $uids = [];
        foreach ($result as $contactBook) {
            $uids[] = $contactBook->getUid();
        }

        sort($uids);

        return $uids;
    }
}
