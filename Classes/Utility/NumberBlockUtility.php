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
use LogicException;

use function array_map;
use function array_merge;
use function dirname;
use function file_get_contents;
use function json_decode;
use function sprintf;
use function strcmp;
use function strlen;
use function substr;
use function usort;

/**
 * Class NumberBlockUtility
 *
 * The number blocks are stored as JSON files below Resources/Private/Json with
 * one directory per block type and one file per country. The German landline
 * area codes are based on the "Vorwahlverzeichnis" of the Bundesnetzagentur
 * (Stand: 2022-07-27, https://www.bundesnetzagentur.de/DE/Fachthemen/Telekommunikation/Nummerierung/ONRufnr/Einteilung_ONB/artikel.html).
 *
 * @package PSBits\ContactBook\Utility
 */
class NumberBlockUtility
{
    private const string BLOCK_LANDLINE_AREA_CODES = 'LandlineAreaCodes';
    private const string BLOCK_MOBILE_SERVICES     = 'MobileServices';
    private const string BLOCK_SERVICES            = 'Services';

    /** @var array<string, string[]> */
    private static array $numberBlocks = [];

    /** @return string[] */
    public static function getGermanLandlineAreaCodesWithoutLeadingZero(): array
    {
        return self::stripLeadingZero(self::loadNumberBlocks(self::BLOCK_LANDLINE_AREA_CODES, 'Germany'));
    }

    /** @return string[] */
    public static function getGermanMobileServicesWithoutLeadingZero(): array
    {
        return self::stripLeadingZero(
            array_merge(
                self::loadNumberBlocks(self::BLOCK_MOBILE_SERVICES, 'Germany'),
                self::loadNumberBlocks(self::BLOCK_SERVICES, 'Germany')
            )
        );
    }

    /**
     * Returns all German number blocks (landline area codes, mobile services
     * and services) without the leading zero, sorted by length (longest
     * first) and alphabetically, so that the longest matching block wins.
     *
     * @return string[]
     */
    public static function getGermanNumberBlocksWithoutLeadingZero(): array
    {
        $numberBlocks = array_merge(
            self::loadNumberBlocks(self::BLOCK_LANDLINE_AREA_CODES, 'Germany'),
            self::loadNumberBlocks(self::BLOCK_MOBILE_SERVICES, 'Germany'),
            self::loadNumberBlocks(self::BLOCK_SERVICES, 'Germany')
        );

        usort(
            $numberBlocks,
            static fn(string $a, string $b): int => (strlen($b) <=> strlen($a)) ?: strcmp($a, $b)
        );

        return self::stripLeadingZero($numberBlocks);
    }

    /**
     * @throws JsonException
     * @throws LogicException
     *
     * @return string[]
     */
    private static function loadNumberBlocks(string $block, string $country): array
    {
        $key = $block . '/' . $country;

        if (!isset(self::$numberBlocks[$key])) {
            $file         = dirname(__DIR__, 2) . '/Resources/Private/Json/' . $block . '/' . $country . '.json';
            $numberBlocks = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($numberBlocks)) {
                throw new LogicException(sprintf('Invalid number block data in %s.', $file));
            }

            self::$numberBlocks[$key] = $numberBlocks;
        }

        return self::$numberBlocks[$key];
    }

    /**
     * @param string[] $numberBlocks
     *
     * @return string[]
     */
    private static function stripLeadingZero(array $numberBlocks): array
    {
        return array_map(
            static fn(string $numberBlock): string => substr($numberBlock, 1),
            $numberBlocks
        );
    }
}
