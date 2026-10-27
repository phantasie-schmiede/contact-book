<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Enum;

use PSBits\Foundation\Utility\Typo3VersionUtility;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Tests\Unit\Fixtures\FakeLanguageServiceFactory;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EventTypeTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Enum
 */
class EventTypeTest extends UnitTestCase
{
    // The language service factory is a pre-seeded singleton, which must not leak between tests.
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The labels come from the extension language file, which the core language stack cannot load in unit tests.
         * A pre-seeded language service factory serves them instead.
         */
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('getLocale')
            ->willReturn(new Locale('en'));
        $labels = static function (string $key): string {
            $position = strrpos($key, ':');
            $key      = false === $position ? $key : substr($key, $position + 1);

            return match ($key) {
                'baptism'                => 'Baptism',
                'birthday'               => 'Birthday',
                'birthday.anniversary'   => 'Birthday (anniversary)',
                'confirmation'           => 'Confirmation',
                'death'                  => 'Death',
                'divorce'                => 'Divorce',
                'firstDayAtSchool'       => 'First day at school',
                'other'                  => 'Other',
                'wedding'                => 'Wedding',
                default                  => $key,
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
    public function theLabelsAreTranslated(): void
    {
        self::assertSame('Baptism', EventType::Baptism->getLabel());
        self::assertSame('Birthday', EventType::Birthday->getLabel());
        self::assertSame('Confirmation', EventType::Confirmation->getLabel());
        self::assertSame('Death', EventType::Death->getLabel());
        self::assertSame('Divorce', EventType::Divorce->getLabel());
        self::assertSame('First day at school', EventType::FirstDayAtSchool->getLabel());
        self::assertSame('Other', EventType::Other->getLabel());
        self::assertSame('Wedding', EventType::Wedding->getLabel());
    }

    #[Test]
    public function theAnniversaryLabelsAreTranslated(): void
    {
        self::assertSame('Birthday (anniversary)', EventType::Birthday->getLabel('anniversary'));
    }

    #[Test]
    public function theTypesAreListedAsArray(): void
    {
        self::assertSame([
            'baptism'          => 'Baptism',
            'birthday'         => 'Birthday',
            'confirmation'     => 'Confirmation',
            'death'            => 'Death',
            'divorce'          => 'Divorce',
            'firstDayAtSchool' => 'FirstDayAtSchool',
            'other'            => 'Other',
            'wedding'         => 'Wedding',
        ], EventType::toArray());
    }
}
