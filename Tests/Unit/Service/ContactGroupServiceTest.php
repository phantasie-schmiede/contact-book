<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Service;

use PSBits\ContactBook\Domain\Model\ContactGroup;
use PSBits\ContactBook\Domain\Repository\ContactGroupRepository;
use PSBits\ContactBook\Service\ContactGroupService;
use PSBits\ContactBook\Service\ContactService;
use PSBits\Foundation\Service\Configuration\TcaService;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactGroupServiceTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Service
 */
class ContactGroupServiceTest extends UnitTestCase
{
    #[Test]
    public function theCacheEntryIdentifierContainsTheContactGroupUid(): void
    {
        $contactGroup = new ContactGroup();

        self::assertSame('tx_contactbook_groups_', $this->createService()->getCacheEntryIdentifier($contactGroup));
    }

    #[Test]
    public function theCachedListIsUsedWhenAvailable(): void
    {
        $contactService = $this->createMock(ContactService::class);
        $contactService->expects(self::once())
            ->method('getCachedList')
            ->willReturn('<p>Cached list</p>');
        $contactService->expects(self::never())
            ->method('renderListAndUpdateCache');

        $view = $this->createMock(ViewInterface::class);
        $view->expects(self::once())
            ->method('assignMultiple')
            ->with(self::callback(
                static fn(array $arguments): bool => '<p>Cached list</p>' === $arguments['list']
                    && 3 === $arguments['pluginUid']
                    && 0 === $arguments['totalCount']
            ));

        $this->createService($contactService)->prepareView(new ContactGroup(), $this->createRequest(), $view);
    }

    #[Test]
    public function theListIsRenderedAndCachedWhenNotAvailable(): void
    {
        $contactGroup = new ContactGroup();

        $contactService = $this->createMock(ContactService::class);
        $contactService->method('getCachedList')
            ->willReturn(false);
        $contactService->expects(self::once())
            ->method('renderListAndUpdateCache')
            ->with('tx_contactbook_groups_', [], $this->isInstanceOf(RequestInterface::class))
            ->willReturn('<p>Rendered list</p>');

        $view = $this->createMock(ViewInterface::class);
        $view->expects(self::once())
            ->method('assignMultiple')
            ->with(self::callback(
                static fn(array $arguments): bool => '<p>Rendered list</p>' === $arguments['list']
            ));

        $this->createService($contactService)->prepareView($contactGroup, $this->createRequest(), $view);
    }

    private function createService(?ContactService $contactService = null): ContactGroupService
    {
        return new ContactGroupService(
            $this->createMock(ContactGroupRepository::class),
            $contactService ?? $this->createMock(ContactService::class),
            $this->createMock(PersistenceManager::class),
            $this->createMock(TcaService::class),
        );
    }

    private function createRequest(): RequestInterface
    {
        $request = $this->createMock(RequestInterface::class);

        $contentObject = new stdClass();
        $contentObject->data = [
            'uid' => 3,
        ];
        $request->method('getAttribute')
            ->with('currentContentObject')
            ->willReturn($contentObject);

        return $request;
    }
}
