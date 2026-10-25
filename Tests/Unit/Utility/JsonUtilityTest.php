<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PSBits\ContactBook\Utility\JsonUtility;
use ReflectionProperty;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

final class JsonUtilityTest extends TestCase
{
    public function testReturnsTheUidsAsJsonArray(): void
    {
        self::assertSame('[1,2,3]', JsonUtility::getUidsAsJsonArray(
            self::entityWithUid(1),
            self::entityWithUid(2),
            self::entityWithUid(3)
        ));
    }

    public function testReturnsAnEmptyJsonArrayWithoutDomainObjects(): void
    {
        self::assertSame('[]', JsonUtility::getUidsAsJsonArray());
    }

    private static function entityWithUid(int $uid): PhoneNumber
    {
        $entity = new PhoneNumber();

        (new ReflectionProperty(AbstractDomainObject::class, 'uid'))->setValue($entity, $uid);

        return $entity;
    }
}
