<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Enum;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Enum\GeneralType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactTypeTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Enum
 */
class ContactTypeTest extends UnitTestCase
{
    #[Test]
    public function companiesAreMappedToBusinessContacts(): void
    {
        self::assertSame(GeneralType::Business, ContactType::Company->getGeneralRelationType());
    }

    #[Test]
    public function personsAreMappedToPrivateContacts(): void
    {
        self::assertSame(GeneralType::Private, ContactType::Person->getGeneralRelationType());
    }

    #[Test]
    public function theTypesAreListedAsArray(): void
    {
        self::assertSame([
            'company' => 'Company',
            'person'  => 'Person',
        ], ContactType::toArray());
    }
}
