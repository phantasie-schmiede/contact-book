<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use Doctrine\DBAL\Exception;
use PSBits\ContactBook\Utility\BankingAccountUtility;
use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class BankAccount
 *
 * @package PSBits\ContactBook\Domain\Model
 */
#[Ctrl(label: 'iban', searchFields: [
    'bankName',
    'bic',
    'iban',
])]
class BankAccount extends AbstractContactRelatedEntity
{
    #[Column(readOnly: true)]
    #[Input]
    protected string $bankName = '';

    #[Column(readOnly: true)]
    #[Input]
    protected string $bic = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_bankaccounts', mmOppositeField: 'bankAccounts', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Column(required: true)]
    #[Input]
    protected string $iban = '';

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function getBic(): string
    {
        return $this->bic;
    }

    public function getCompactValue(): string
    {
        return $this->getIbanFormatted();
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getIbanFormatted(): string
    {
        return trim(chunk_split($this->iban, 4, ' '));
    }

    public function setBankName(string $bankName): self
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function setBic(string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setIban(string $iban): self
    {
        $this->iban = str_replace(' ', '', strtoupper($iban));

        $additionalBankInformation = BankingAccountUtility::getBankInformationFromIban($this->iban);

        if (isset($additionalBankInformation['bic'])) {
            $this->bic = $additionalBankInformation['bic'];
        }

        if (isset($additionalBankInformation['short_name'])) {
            $short_name = $additionalBankInformation['short_name'];

            if (isset($additionalBankInformation['city']) && !str_contains(
                    $short_name,
                    $additionalBankInformation['city']
                )) {
                $short_name .= ' (' . $additionalBankInformation['city'] . ')';
            }

            $this->bankName = $short_name;
        }

        return $this;
    }
}
