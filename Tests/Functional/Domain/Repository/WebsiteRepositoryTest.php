<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\WebsiteDemand;
use PSBits\ContactBook\Domain\Model\Website;
use PSBits\ContactBook\Domain\Repository\WebsiteRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class WebsiteRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class WebsiteRepositoryTest extends FunctionalTestCase
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
    public function websitesMatchingTheDemandAreReturned(): void
    {
        $websiteOne = $this->createWebsite('https://example.com');
        $websiteTwo = $this->createWebsite('https://psbits.de');
        $this->persistAll($websiteOne, $websiteTwo);

        $repository = GeneralUtility::makeInstance(WebsiteRepository::class);

        $demand = new WebsiteDemand();
        $demand->setUrl('https://example.com');

        self::assertSame(
            [$websiteOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );

        $demand = new WebsiteDemand();
        $demand->setUrl('https://unknown.example');

        self::assertCount(0, $repository->findByDemand($demand));
    }

    private function createWebsite(string $url): Website
    {
        $website = new Website();
        $website->setUrl($url);

        return $website;
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
        foreach ($result as $website) {
            $uids[] = $website->getUid();
        }

        sort($uids);

        return $uids;
    }
}
