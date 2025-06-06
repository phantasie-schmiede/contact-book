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
    case Apple = 'Apple';
    case V3_0  = 'v3.0';
    case V4_0  = 'v4.0';

    public function getVersionNumber(): string
    {
        if (self::Apple === $this) {
            return self::V3_0->value;
        }

        return $this->value;
    }
}
