<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Utility;

use Doctrine\DBAL\Exception;
use InvalidArgumentException;
use PSBits\ContactBook\Data\ExtensionInformation;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function substr;

readonly class BankingAccountUtility
{
    public const string DATA_TABLE = 'tx_contactbook_bank_data';

    public function __construct(
        protected ExtensionInformation $extensionInformation,
    ) {
    }

    /**
     * @return array{bic?: string, city?: string, short_name?: string}
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public static function getBankInformationFromIban(string $iban): array
    {
        // Extract the bank code (BLZ) from the IBAN
        $bankCode = substr($iban, 4, 8);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::DATA_TABLE);
        $record = $queryBuilder->select('bic', 'city', 'short_name')
            ->from(self::DATA_TABLE)
            ->where(
                $queryBuilder->expr()
                    ->eq('bank_code', $queryBuilder->createNamedParameter($bankCode))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $record ?: [];
    }
}
