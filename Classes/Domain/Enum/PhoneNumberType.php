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

/**
 * Class PhoneNumberType
 *
 * @package PSBits\ContactBook\Domain\Enum
 */
enum PhoneNumberType: string
{
    use EnumToArrayTrait;

    case Fax      = 'fax';
    case Landline = 'landline';
    case Mobile   = 'mobile';
    case Other    = 'other';

    public function getIconIdentifier(): string
    {
        return match ($this) {
            self::Fax      => 'printer',
            self::Landline => 'house',
            self::Mobile   => 'phone',
            self::Other    => 'question-circle',
        };
    }
}
