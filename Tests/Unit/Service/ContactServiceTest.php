<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Data\ExtensionInformation;
use PSBits\ContactBook\Domain\Model\Address;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Repository\ContactRepository;
use PSBits\ContactBook\Service\ContactService;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Service\TypoScriptProviderService;
use PSBits\FrontendTemplates\Service\ViewService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class ContactServiceTest extends UnitTestCase
{
    #[Test]
    public function theCachedListIsReturned(): void
    {
        $frontendCache = $this->createMock(FrontendInterface::class);
        $frontendCache->expects(self::once())
            ->method('get')
            ->with('tx_contactbook_contacts_list_42')
            ->willReturn('<p>Cached list</p>');

        $service = $this->createService(frontendCache: $frontendCache);

        self::assertSame('<p>Cached list</p>', $service->getCachedList('tx_contactbook_contacts_list_42'));
    }

    #[Test]
    public function theCachedListIsFalseWithoutACacheEntry(): void
    {
        $frontendCache = $this->createMock(FrontendInterface::class);
        $frontendCache->method('get')
            ->willReturn(false);

        $service = $this->createService(frontendCache: $frontendCache);

        self::assertFalse($service->getCachedList('tx_contactbook_contacts_list_42'));
    }

    #[Test]
    public function theContactsOfARelatedEntityAreReplaced(): void
    {
        $contact = new Contact();
        $entity = new Address();
        $entity->addContact($contact);

        $replacement = new Contact();

        $this->createService()->setContactsOfRelatedEntity($entity, $replacement);

        self::assertCount(1, $entity->getContacts());
        self::assertSame($replacement, $entity->getContacts()
            ->toArray()[0]);
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function additionalContactsAreAddedByTheirUids(): void
    {
        $contact = new Contact();
        $additionalContactOne = new Contact();
        $additionalContactTwo = new Contact();
        $entity = new Address();

        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByUid')
            ->willReturnCallback(
                static fn(int $uid) => $uid === 1 ? $additionalContactOne : $additionalContactTwo
            );

        $this->createService(contactRepository: $contactRepository)
            ->setContactsOfRelatedEntity($entity, $contact, [1, 2]);

        self::assertCount(3, $entity->getContacts());
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function additionalContactsAreAddedFromAJsonString(): void
    {
        $contact = new Contact();
        $additionalContact = new Contact();
        $entity = new Address();

        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByUid')
            ->with(1)
            ->willReturn($additionalContact);

        $this->createService(contactRepository: $contactRepository)
            ->setContactsOfRelatedEntity($entity, $contact, '[1]');

        self::assertCount(2, $entity->getContacts());
    }

    /**
     * @throws \JsonException
     */
    #[Test]
    public function alreadyAttachedContactsAreNotAddedAgain(): void
    {
        $contact = new Contact();
        $entity = new Address();
        $entity->addContact($contact);

        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->method('findByUid')
            ->willReturn($contact);

        $this->createService(contactRepository: $contactRepository)
            ->setContactsOfRelatedEntity($entity, $contact, [1]);

        self::assertCount(1, $entity->getContacts());
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Test]
    public function theAutocompleteListIsRendered(): void
    {
        $view = $this->createMock(ViewInterface::class);
        $view->expects(self::once())
            ->method('assign')
            ->with('contacts', []);
        $view->method('render')
            ->willReturn('<p>Autocomplete list</p>');

        $service = $this->createService(view: $view);
        $request = $this->createMock(RequestInterface::class);

        self::assertSame(
            '<p>Autocomplete list</p>',
            $service->renderAutocompleteList([], $request)
        );
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Test]
    public function theRenderedListIsStoredInTheCache(): void
    {
        $view = $this->createMock(ViewInterface::class);
        $view->method('render')
            ->willReturn('<p>Contact list</p>');

        $frontendCache = $this->createMock(FrontendInterface::class);
        $frontendCache->expects(self::once())
            ->method('set')
            ->with('tx_contactbook_contacts_list_42', '<p>Contact list</p>', [], 0);

        $service = $this->createService(frontendCache: $frontendCache, view: $view);
        $request = $this->createMock(RequestInterface::class);

        self::assertSame(
            '<p>Contact list</p>',
            $service->renderListAndUpdateCache('tx_contactbook_contacts_list_42', [], $request)
        );
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function createService(
        ?ContactRepository    $contactRepository = null,
        ?FrontendInterface    $frontendCache = null,
        ?ViewInterface        $view = null,
    ): ContactService {
        /*
         * The view service is a readonly class that cannot be mocked; its two dependencies are public and can be
         * stubbed instead.
         */
        $viewFactory = $this->createMock(ViewFactoryInterface::class);
        $viewFactory->method('create')
            ->willReturn($view ?? $this->createMock(ViewInterface::class));

        $viewService = new ViewService(
            $this->createMock(TypoScriptProviderService::class),
            $viewFactory,
        );

        return new ContactService(
            $contactRepository ?? $this->createMock(ContactRepository::class),
            GeneralUtility::makeInstance(ExtensionInformation::class),
            $frontendCache ?? $this->createMock(FrontendInterface::class),
            $this->createMock(PersistenceManager::class),
            $this->createMock(TcaService::class),
            $viewService,
        );
    }
}
