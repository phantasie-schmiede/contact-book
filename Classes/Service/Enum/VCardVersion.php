<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Service\Enum;

/**
 * Class VCardVersion
 *
 * @package PSB\PsbContacts\Service\Enum
 */
enum VCardVersion: string
{
    case V3_0 = '3.0';
    case V4_0 = '4.0';
}
