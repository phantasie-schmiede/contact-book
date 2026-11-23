<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\ContactDemand;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\ContactBook;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
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
 * Class ContactRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class ContactRepositoryTest extends FunctionalTestCase
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
    public function contactsMatchingTheDemandAreReturned(): void
    {
        $bookOne = $this->createContactBook('Book One');
        $bookTwo = $this->createContactBook('Book Two');
        $maxOne  = $this->createContact('Max', 'Mustermann', $bookOne);
        $maxTwo  = $this->createContact('Max', 'Meier', $bookTwo);
        $company = $this->createContact('', '', $bookOne);
        $company->setCompanyName('Acme GmbH')
            ->setType(ContactType::Company);
        $this->persistAll($bookOne, $bookTwo, $maxOne, $maxTwo, $company);

        $repository = GeneralUtility::makeInstance(ContactRepository::class);
        $this->seedCurrentFrontendUser(new FrontendUser());

        $demand = new ContactDemand();
        $demand->setFirstName('Max')
            ->setLastName('Mustermann');

        self::assertSame([$maxOne->getUid()], $this->getUids($repository->findByDemand($demand)));

        $demand = new ContactDemand();
        $demand->addType(ContactType::Company);

        self::assertSame([$company->getUid()], $this->getUids($repository->findByDemand($demand)));
    }

    #[Test]
    public function contactsOfTheActiveContactBookAreReturned(): void
    {
        $bookOne = $this->createContactBook('Book One');
        $bookTwo = $this->createContactBook('Book Two');
        $maxOne  = $this->createContact('Max', 'Mustermann', $bookOne);
        $maxTwo  = $this->createContact('Max', 'Meier', $bookTwo);
        $this->persistAll($bookOne, $bookTwo, $maxOne, $maxTwo);

        $user = new FrontendUser();
        $user->setSetting('activeContactBook', $bookOne->getUid());
        $this->seedCurrentFrontendUser($user);

        $repository = GeneralUtility::makeInstance(ContactRepository::class);
        $uids       = $this->getUids($repository->findByDemand(new ContactDemand()));

        self::assertContains($maxOne->getUid(), $uids);
        self::assertNotContains($maxTwo->getUid(), $uids);
    }

    #[Test]
    public function onlyExportRelevantContactsAreReturned(): void
    {
        $contactWithInformation = $this->createContact('Max', 'Mustermann');
        $eMail                  = new EMail();
        $eMail->setEMailAddress('max@example.com');
        $contactWithInformation->addEMails($eMail);

        $contactWithoutInformation = $this->createContact('Erika', 'Erika');
        $this->persistAll($contactWithInformation, $eMail, $contactWithoutInformation);

        $repository = GeneralUtility::makeInstance(ContactRepository::class);
        $this->seedCurrentFrontendUser(new FrontendUser());
        $demand     = new ContactDemand();
        $demand->setOnlyExportRelevantInformation(true);

        $contacts = $repository->findByDemand($demand);

        self::assertIsArray($contacts);
        self::assertSame(
            [$contactWithInformation->getUid()],
            array_map(
                static fn(Contact $contact): int => $contact->getUid(),
                $contacts
            )
        );
    }

    private function createContactBook(string $name): ContactBook
    {
        $contactBook = new ContactBook();
        $contactBook->setName($name);

        return $contactBook;
    }

    private function createContact(
        string       $firstName,
        string       $lastName,
        ?ContactBook $contactBook = null,
    ): Contact {
        $contact = new Contact();
        $contact->setFirstName($firstName)
            ->setLastName($lastName)
            ->setContactBook($contactBook);

        return $contact;
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
        foreach ($result as $contact) {
            $uids[] = $contact->getUid();
        }

        sort($uids);

        return $uids;
    }
}
