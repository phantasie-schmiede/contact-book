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
 * Class ContactType
 *
 * @package PSBits\ContactBook\Domain\Enum
 */
enum ContactType: string
{
    use EnumToArrayTrait;

    case Company = 'company';
    case Person  = 'person';

    public function getGeneralRelationType(): GeneralType
    {
        return match ($this) {
            self::Company => GeneralType::Business,
            self::Person  => GeneralType::Private,
        };
    }
}
