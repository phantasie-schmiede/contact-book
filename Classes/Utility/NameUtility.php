<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use PSBits\Foundation\Utility\StringUtility;

use function in_array;
use function mb_strtolower;
use function str_replace;
use function strtolower;
use function substr;

class NameUtility
{
    /*
     * This method replaces the German umlauts before using StringUtility::normalizeForSorting() because
     * normalizeForSorting() will replace ä with a, ö with o and ü with u, which will not result in a correct sorting
     * of a list of names according to DIN 5007-1.
     */
    public static function getCompareString(string $name): string
    {
        $search = [
            'ä',
            'ö',
            'ü',
        ];

        $replace = [
            'ae',
            'oe',
            'ue',
        ];

        return StringUtility::normalizeForSorting(str_replace($search, $replace, mb_strtolower($name)));
    }

    public static function getGenitiveForm(string $name): string
    {
        if (in_array(
            strtolower(substr($name, -1)),
            [
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
