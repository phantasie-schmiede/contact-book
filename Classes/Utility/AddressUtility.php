<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use function explode;
use function implode;

class AddressUtility
{
    /**
     * @return array{houseNumber: string, street: string}
     */
    public static function splitStreetAndHouseNumber(string $streetAndHouseNumber): array
    {
        $streetAndHouseNumberParts = explode(' ', $streetAndHouseNumber);
        $houseNumber               = array_pop($streetAndHouseNumberParts);

        // Check if the house number begins with a digit. If not, the house number is empty.
        if (!is_numeric($houseNumber[0])) {
            $streetAndHouseNumberParts[] = $houseNumber;
            $houseNumber                 = '';
        }

        $street = implode(' ', $streetAndHouseNumberParts);

        return [
            'houseNumber' => $houseNumber,
            'street'      => $street,
        ];
    }
}
