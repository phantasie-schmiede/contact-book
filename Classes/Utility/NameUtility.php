<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Utility;

use function in_array;

/**
 * Class NameUtility
 *
 * @package PSB\PsbContacts\Utility
 */
class NameUtility
{
    public static function getGenitiveForm(string $name): string
    {
        if (in_array(
            strtolower(substr($name, -1)), [
                'ce',
                's',
                'x',
                'z',
                'ß',
            ]
        )) {
            return $name . "'";
        }

        return $name . 's';
    }
}
