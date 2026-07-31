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
 * Class CountyUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class CountyUtility
{
    public const COUNTIES = [
        'GERMANY' => [
            'Baden-Württemberg',
            'Bayern',
            'Berlin',
            'Brandenburg',
            'Bremen',
            'Hamburg',
            'Hessen',
            'Mecklenburg-Vorpommern',
            'Niedersachsen',
            'Nordrhein-Westfalen',
            'Rheinland-Pfalz',
            'Saarland',
            'Sachsen',
            'Sachsen-Anhalt',
            'Schleswig-Holstein',
            'Thüringen',
        ],
    ];

    /** @return string[] */
    public static function getGermanCounties(): array
    {
        return self::COUNTIES['GERMANY'];
    }
}
