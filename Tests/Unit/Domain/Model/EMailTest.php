<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Model\EMail;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EMailTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class EMailTest extends UnitTestCase
{
    #[Test]
    public function theCompactValueIsTheEMailAddress(): void
    {
        $eMail = new EMail();
        $eMail->setEMailAddress('max@example.com');

        self::assertSame('max@example.com', $eMail->getCompactValue());
    }

    #[Test]
    public function theMailToLinkIsGenerated(): void
    {
        $eMail = new EMail();
        $eMail->setEMailAddress('max@example.com');

        self::assertSame('mailto:max@example.com', $eMail->getLink());
    }

    #[Test]
    public function theTypeDefaultsToPrivate(): void
    {
        self::assertSame(GeneralType::Private, (new EMail())->getType());
    }
}
