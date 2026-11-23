<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Enum;

use InvalidArgumentException;
use JsonException;
use PSBits\ContactBook\Trait\EnumToArrayTrait;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

enum EventType: string
{
    use EnumToArrayTrait;

    case Baptism          = 'baptism';
    case Birthday         = 'birthday';
    case Confirmation     = 'confirmation';
    case Death            = 'death';
    case Divorce          = 'divorce';
    case FirstDayAtSchool = 'firstDayAtSchool';
    case Other            = 'other';
    case Wedding          = 'wedding';

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function getLabel(string $subType = ''): string
    {
        $key = $this->value . ($subType ? ('.' . $subType) : '');

        return LocalizationUtility::translate(
            'LLL:EXT:contact_book/Resources/Private/Language/Frontend/eventType.xlf:' . $key
        );
    }
}
