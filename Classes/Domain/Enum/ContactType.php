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
 * Class ContactType
 *
 * @package PSB\PsbContacts\Domain\Enum
 */
enum ContactType: string
{
    use EnumToArrayTrait;

    case Company = 'company';
    case Person  = 'person';
}
