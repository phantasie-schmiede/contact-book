<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Enum;

/**
 * Class EventType
 *
 * @package PSB\PsbContacts\Domain\Enum
 */
enum EventType: string
{
    case Baptism      = 'baptism';
    case Birthday     = 'birthday';
    case Confirmation = 'confirmation';
    case Death        = 'death';
    case Divorce      = 'divorce';
    case Other        = 'other';
    case Wedding      = 'wedding';

    public static function toArray(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->value] = $case->name;
        }

        return $result;
    }
}
