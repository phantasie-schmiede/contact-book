<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Functional\Domain\Repository;

use PSBits\ContactBook\Domain\Demand\EMailDemand;
use PSBits\ContactBook\Domain\Model\EMail;
use PSBits\ContactBook\Domain\Repository\EMailRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class EMailRepositoryTest
 *
 * @package PSBits\ContactBook\Tests\Functional\Domain\Repository
 */
class EMailRepositoryTest extends FunctionalTestCase
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
    public function eMailsMatchingTheDemandAreReturned(): void
    {
        $eMailOne = $this->createEMail('max@example.com');
        $eMailTwo = $this->createEMail('meier@example.com');
        $this->persistAll($eMailOne, $eMailTwo);

        $repository = GeneralUtility::makeInstance(EMailRepository::class);

        $demand = new EMailDemand();
        $demand->setEMailAddress('max@example.com');

        self::assertSame(
            [$eMailOne->getUid()],
            $this->getUids($repository->findByDemand($demand))
        );

        $demand = new EMailDemand();
        $demand->setEMailAddress('unknown@example.com');

        self::assertCount(0, $repository->findByDemand($demand));
    }

    private function createEMail(string $eMailAddress): EMail
    {
        $eMail = new EMail();
        $eMail->setEMailAddress($eMailAddress);

        return $eMail;
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
        foreach ($result as $eMail) {
            $uids[] = $eMail->getUid();
        }

        sort($uids);

        return $uids;
    }
}
