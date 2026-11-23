<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Trait;

trait EnumToArrayTrait
{
    /** @return array<int|string, string> */
    public static function toArray(object $defaultValue = null): array
    {
        $result = [];

        foreach (static::cases() as $case) {
            if (null !== $defaultValue && $case->value === $defaultValue->value) {
                $result = [$case->value => $case->name] + $result;
            } else {
                $result[$case->value] = $case->name;
            }
        }

        return $result;
    }
}
