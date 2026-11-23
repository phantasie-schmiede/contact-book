<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Enum;

use PSBits\ContactBook\Trait\EnumToArrayTrait;

enum GeneralType: string
{
    use EnumToArrayTrait;

    case Business = 'business';
    case Other    = 'other';
    case Private  = 'private';

    public function getIconIdentifier(): string
    {
        return match ($this) {
            self::Business => 'briefcase',
            self::Other    => 'question-circle',
            self::Private  => 'person',
        };
    }
}
