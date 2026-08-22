<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

/**
 * Class AddressUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class AddressUtility
{
    /**
     * @param string $streetAndHouseNumber
     *
     * @return array{street: string, houseNumber: string}
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
            'street'      => $street,
            'houseNumber' => $houseNumber,
        ];
    }
}
