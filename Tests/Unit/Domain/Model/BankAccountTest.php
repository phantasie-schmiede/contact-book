<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use PSBits\ContactBook\Domain\Model\BankAccount;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionProperty;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class BankAccountTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class BankAccountTest extends UnitTestCase
{
    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theIbanIsFormattedInBlocksOfFourCharacters(): void
    {
        $bankAccount = new BankAccount();
        $this->setIban($bankAccount, 'DE89370400440532013000');

        self::assertSame('DE89 3704 0044 0532 0130 00', $bankAccount->getIbanFormatted());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function theCompactValueIsTheFormattedIban(): void
    {
        $bankAccount = new BankAccount();
        $this->setIban($bankAccount, 'DE89370400440532013000');

        self::assertSame('DE89 3704 0044 0532 0130 00', $bankAccount->getCompactValue());
    }

    /**
     * The setter performs a database lookup for the bank information, which is not available in unit tests.
     *
     * @throws ReflectionException
     */
    private function setIban(BankAccount $bankAccount, string $iban): void
    {
        $property = new ReflectionProperty(BankAccount::class, 'iban');
        $property->setValue($bankAccount, $iban);
    }
}
