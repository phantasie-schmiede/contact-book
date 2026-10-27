<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Enum;

use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\Qa\Testing\Support\LanguageStackSeeder;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EventTypeTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Enum
 */
class EventTypeTest extends UnitTestCase
{
    use LanguageStackSeeder;

    // The language service factory is a pre-seeded singleton, which must not leak between tests.
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The labels come from the extension language file, which the core language stack cannot load in unit tests.
         * The seeder pre-seeds a language service factory that serves them instead.
         */
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
        $this->seedLanguageStack($labels);
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
