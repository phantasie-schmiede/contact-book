<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use PSBits\Foundation\Utility\Typo3VersionUtility;
use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Tests\Unit\Fixtures\FakeLanguageServiceFactory;
use PSBits\ContactBook\Utility\ContactUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactUtilityTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Utility
 */
class ContactUtilityTest extends UnitTestCase
{
    // The language service factory is a pre-seeded singleton, which must not leak between tests.
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The enumeration separators come from the frontend templates language file, which the core language stack
         * cannot load in unit tests. A pre-seeded language service factory serves them instead.
         */
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('getLocale')
            ->willReturn(new Locale('en'));
        $labels = static function (string $key): string {
            $position = strrpos($key, ':');
            $key      = false === $position ? $key : substr($key, $position + 1);

            return match ($key) {
                'conjunction' => 'and',
                'separator'   => ', ',
                default       => $key,
            };
        };
        $languageService->method('sL')
            ->willReturnCallback($labels);

        /*
         * On v14, the language service serves labels through `translate()`; the method does not exist on v13.
         */
        if (Typo3VersionUtility::isAtLeast('14.0')) {
            $languageService->method('translate')
                ->willReturnCallback($labels);
        }

        GeneralUtility::setSingletonInstance(
            LanguageServiceFactory::class,
            new FakeLanguageServiceFactory($languageService)
        );

        /*
         * On v13, the core keeps the language service in the runtime cache between lookups, which is not configured
         * in unit tests. A stubbed cache manager serves the pre-seeded language service instead.
         */
        if (false === Typo3VersionUtility::isAtLeast('14.0')) {
            $cache = $this->createMock(FrontendInterface::class);
            $cache->method('get')
                ->willReturn($languageService);

            $cacheManager = $this->createMock(CacheManager::class);
            $cacheManager->method('getCache')
                ->willReturn($cache);

            GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        }
    }

    #[Test]
    public function theEnumerationIsCombinedWithAConjunction(): void
    {
        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Moritz', 'Mustermann'),
            $this->createContact('Anna', 'Auer'),
        ];

        self::assertSame('Max and Moritz Mustermann and Anna Auer', ContactUtility::createEnumeration($contacts));
    }

    #[Test]
    public function theSingleContactIsReturnedAsIs(): void
    {
        $contacts = [
            $this->createContact('Anna', 'Auer'),
        ];

        self::assertSame('Anna Auer', ContactUtility::createEnumeration($contacts));
    }

    #[Test]
    public function theContactsAreLinkedWithTheUriBuilder(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('reset')
            ->willReturnSelf();
        $uriBuilder->method('uriFor')
            ->willReturn('https://example.com/contact');

        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Moritz', 'Mustermann'),
        ];

        self::assertSame(
            '<a class="text-decoration-none" href="https://example.com/contact">Max</a> and '
            . '<a class="text-decoration-none" href="https://example.com/contact">Moritz Mustermann</a>',
            ContactUtility::createEnumeration($contacts, $uriBuilder)
        );
    }

    #[Test]
    public function theContactsAreSortedByTheirListName(): void
    {
        $aerger = $this->createContact('Heinz', 'Ärger');
        $becker = $this->createContact('Erika', 'Becker');
        $mueller = $this->createContact('Fritz', 'Müller');

        self::assertSame([$aerger, $becker, $mueller], ContactUtility::sortByListName($mueller, $becker, $aerger));
    }

    private function createContact(string $firstName, string $lastName): Contact
    {
        $contact = new Contact();
        $contact->setFirstName($firstName)
            ->setLastName($lastName)
            ->setType(ContactType::Person);

        return $contact;
    }
}
