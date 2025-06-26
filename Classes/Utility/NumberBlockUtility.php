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
 * Class NumberBlockUtility
 *
 * @package PSBits\ContactBook\Utility
 */
class NumberBlockUtility
{
    public const MOBILE_SERVICES = [
        'GERMANY' => [
            '015019',
            '015020',
            '01511',
            '01512',
            '01514',
            '01515',
            '01516',
            '01517',
            '015180',
            '015181',
            '015182',
            '015183',
            '01520',
            '01521',
            '01522',
            '01523',
            '01525',
            '01526',
            '01529',
            '015310',
            '015510',
            '015511',
            '015560',
            '015561',
            '015562',
            '015563',
            '015564',
            '015565',
            '015566',
            '015567',
            '015568',
            '015569',
            '015630',
            '015678',
            '015679',
            '015700',
            '015701',
            '015702',
            '015703',
            '015704',
            '015706',
            '01573',
            '01575',
            '01577',
            '01578',
            '01579',
            '015888',
            '01590',
            '0160',
            '0162',
            '0163',
            '0170',
            '0171',
            '0172',
            '0173',
            '0174',
            '0175',
            '0176',
            '0177',
            '0178',
            '0179',
        ],
    ];
    public const SERVICES        = [
        'GERMANY' => [
            '0700',
            '0800',
            '0900',
        ],
    ];

    public static function getGermanMobileServicesWithoutLeadingZero(): array
    {
        return array_map(
            static fn(string $numberBlock) => substr($numberBlock, 1),
            array_merge(self::MOBILE_SERVICES['GERMANY'], self::SERVICES['GERMANY'])
        );
    }
}
