<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Enum;

use JsonException;
use PSB\PsbContacts\Trait\EnumToArrayTrait;
use PSB\PsbFoundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

/**
 * Class EventType
 *
 * @package PSB\PsbContacts\Domain\Enum
 */
enum EventType: string
{
    use EnumToArrayTrait;

    case Baptism      = 'baptism';
    case Birthday     = 'birthday';
    case Confirmation = 'confirmation';
    case Death        = 'death';
    case Divorce      = 'divorce';
    case Other        = 'other';
    case Wedding      = 'wedding';

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function getLabel(): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:psb_contacts/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.' . $this->value
        );
    }
}
