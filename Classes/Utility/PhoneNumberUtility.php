<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use JsonException;
use PSBits\ContactBook\Domain\Model\PhoneNumber;

/**
 * Class PhoneNumberUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class PhoneNumberUtility
{
    /**
     * @throws JsonException
     */
    public static function getPhoneNumberFromString(string $phoneNumber): PhoneNumber
    {
        // Remove all whitespaces.
        $phoneNumber = preg_replace('/[\s| ]+/u', '', $phoneNumber);

        $pattern = '/\+(?<internationalDiallingCode>49)(?<areaCode>' . implode(
            '|',
            array_merge(NumberBlockUtility::getGermanNumberBlocksWithoutLeadingZero(), ['\d{4}'])
        ) . ')(?<connectionIdentifier>.*)/';
        preg_match($pattern, $phoneNumber, $matches);

        return (new PhoneNumber())->setAreaCode($matches['areaCode'])
            ->setConnectionIdentifier($matches['connectionIdentifier'])
            ->setInternationalDiallingCode($matches['internationalDiallingCode']);
    }
}
