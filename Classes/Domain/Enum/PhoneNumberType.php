<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Enum;

use PSB\PsbContacts\Trait\EnumToArrayTrait;

/**
 * Class PhoneNumberType
 *
 * @package PSB\PsbContacts\Domain\Enum
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
            self::Fax      => 'file-text',
            self::Landline => 'house',
            self::Mobile   => 'phone',
            self::Other    => 'question-circle',
        };
    }
}
