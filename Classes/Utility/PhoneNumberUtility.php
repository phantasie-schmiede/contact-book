<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Utility;

use PSB\PsbContacts\Domain\Model\PhoneNumber;

/**
 * Class PhoneNumberUtility
 *
 * @package PSB\PsbContacts\Utility
 */
class PhoneNumberUtility
{
    public static function getPhoneNumberFromString(string $phoneNumber): PhoneNumber
    {
        $pattern = '/(?<internationalDiallingCode>\+49)(?<areaCode>' . implode(
                '|',
                array_merge(NumberBlockUtility::getGermanMobileServicesWithoutLeadingZero(), ['\d{4}'])
            ) . ')(?<connectionIdentifier>.*)/';
        preg_match($pattern, $phoneNumber, $matches);

        return (new PhoneNumber())->setAreaCode($matches['areaCode'])
            ->setConnectionIdentifier($matches['connectionIdentifier'])
            ->setInternationalDiallingCode($matches['internationalDiallingCode']);
    }
}
