<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Enum;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class GeneralTypeTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Enum
 */
class GeneralTypeTest extends UnitTestCase
{
    #[Test]
    public function businessHasTheBriefcaseIcon(): void
    {
        self::assertSame('briefcase', GeneralType::Business->getIconIdentifier());
    }

    #[Test]
    public function otherHasTheQuestionCircleIcon(): void
    {
        self::assertSame('question-circle', GeneralType::Other->getIconIdentifier());
    }

    #[Test]
    public function privateHasThePersonIcon(): void
    {
        self::assertSame('person', GeneralType::Private->getIconIdentifier());
    }

    #[Test]
    public function theTypesAreListedAsArray(): void
    {
        self::assertSame([
            'business' => 'Business',
            'other'    => 'Other',
            'private'  => 'Private',
        ], GeneralType::toArray());
    }
}
