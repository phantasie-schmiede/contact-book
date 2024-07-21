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
 * Class ContactType
 *
 * @package PSB\PsbContacts\Domain\Enum
 */
enum ContactType: string
{
    case Company = 'company';
    case Person  = 'person';

    public static function toArray(ContactType $firstValue = null): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            if (null !== $firstValue && $case->value === $firstValue->value) {
                $result = [$case->value => $case->name] + $result;
            } else {
                $result[$case->value] = $case->name;
            }
        }

        return $result;
    }
}
