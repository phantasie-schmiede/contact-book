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
 * Class GeneralType
 *
 * @package PSB\PsbContacts\Domain\Enum
 */
enum GeneralType: string
{
    case Business = 'business';
    case Other    = 'other';
    case Private  = 'private';
}
