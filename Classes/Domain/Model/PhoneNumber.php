<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\Foundation\Attribute\TCA\ColumnType\Check;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Enum\SelectRenderType;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

use function sprintf;
use function trim;

/**
 * Number formats:
 *  - 0<area code> <connection identifier>
 *  - 0<area code> <connection identifier>-<extension>
 *  - +<international dialling code> <area code> <connection identifier>
 *  - +<international dialling code> <area code> <connection identifier>-<extension>
 */
#[Ctrl(label        : 'internationalDiallingCode', labelAlt: [
    'areaCode',
    'connectionIdentifier',
    'extension',
], labelAltForce: true, searchFields: [
    'areaCode',
    'connectionIdentifier',
    'extension',
    'internationalDiallingCode',
    'notes',
])]
class PhoneNumber extends AbstractContactRelatedEntity
{
    public const string ACTION_ICON_IDENTIFIER = 'telephone';
    public const array SHORT_URLS              = [
        'SIGNAL'   => 'https://signal.me/#p=%s',
        'TELEGRAM' => 'https://t.me/%s',
        'WHATSAPP' => 'https://wa.me/%s',
    ];

    #[Input]
    protected string $areaCode = '';

    #[Input]
    protected string $connectionIdentifier = '';

    /** @var ObjectStorage<Contact> */
    #[Select(linkedModel: Contact::class, mm: 'tx_contactbook_contacts_phonenumbers', mmOppositeField: 'phoneNumbers', renderType: SelectRenderType::selectMultipleSideBySide)]
    protected ObjectStorage $contacts;

    #[Input]
    protected string $extension = '';

    #[Select(items   : [
        // PSBits/alphabetical_array_keys=off
        'Private'  => 'private',
        'Business' => 'business',
        'Other'    => 'other',
    ], minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_STRING)]
    protected GeneralType $generalType = GeneralType::Private;

    #[Input]
    protected string $internationalDiallingCode = '';

    #[Select(items   : [
        // PSBits/alphabetical_array_keys=off
        'Landline' => 'landline',
        'Mobile'   => 'mobile',
        'Fax'      => 'fax',
        'Other'    => 'other',
    ], minItems: 1, prependItem: Select::EMPTY_DEFAULT_ITEM_MANDATORY_STRING)]
    protected PhoneNumberType $phoneNumberType = PhoneNumberType::Mobile;

    #[Check]
    protected bool $signal = false;

    #[Check]
    protected bool $telegram = false;

    #[Check]
    protected bool $whatsApp = false;

    /**
     * The array keys are the icon identifiers.
     *
     * @return array<string, string>
     */
    public function getActionLinks(): array
    {
        if (PhoneNumberType::Mobile === $this->getPhoneNumberType()) {
            if ($this->isSignal()) {
                $links['signal'] = $this->getSignalLink();
            }

            if ($this->isTelegram()) {
                $links['telegram'] = $this->getTelegramLink();
            }

            if ($this->isWhatsApp()) {
                $links['whatsapp'] = $this->getWhatsAppLink();
            }

            $links['chat-left-dots'] = 'sms:' . $this->getCompleteNumber();
        }

        $links['telephone'] = $this->getLink();

        return $links;
    }

    public function getAreaCode(): string
    {
        return $this->areaCode;
    }

    public function getCompactValue(): string
    {
        return trim(
            sprintf(
                '+%s %s %s %s',
                $this->internationalDiallingCode,
                $this->areaCode,
                $this->connectionIdentifier,
                $this->extension,
            )
        );
    }

    public function getCompleteNumber(): string
    {
        return '+' . $this->internationalDiallingCode . $this->areaCode . $this->connectionIdentifier . $this->extension;
    }

    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getGeneralType(): GeneralType
    {
        return $this->generalType;
    }

    public function getInternationalDiallingCode(): string
    {
        return $this->internationalDiallingCode;
    }

    public function getLink(): string
    {
        return 'tel:' . $this->getCompleteNumber();
    }

    public function getPhoneNumberType(): PhoneNumberType
    {
        return $this->phoneNumberType;
    }

    public function getSignalLink(): ?string
    {
        if ($this->isSignal()) {
            return sprintf(self::SHORT_URLS['SIGNAL'], $this->getCompleteNumber());
        }

        return null;
    }

    public function getTelegramLink(): ?string
    {
        if ($this->isTelegram()) {
            return sprintf(self::SHORT_URLS['TELEGRAM'], $this->getCompleteNumber());
        }

        return null;
    }

    public function getWhatsAppLink(): ?string
    {
        if ($this->isWhatsApp()) {
            return sprintf(self::SHORT_URLS['WHATSAPP'], $this->getCompleteNumber());
        }

        return null;
    }

    public function isSignal(): bool
    {
        return $this->signal;
    }

    public function isTelegram(): bool
    {
        return $this->telegram;
    }

    public function isWhatsApp(): bool
    {
        return $this->whatsApp;
    }

    public function setAreaCode(string $areaCode): self
    {
        $this->areaCode = $areaCode;

        return $this;
    }

    public function setConnectionIdentifier(string $connectionIdentifier): self
    {
        $this->connectionIdentifier = $connectionIdentifier;

        return $this;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function setGeneralType(GeneralType $generalType): self
    {
        $this->generalType = $generalType;

        return $this;
    }

    public function setInternationalDiallingCode(string $internationalDiallingCode): self
    {
        $this->internationalDiallingCode = $internationalDiallingCode;

        return $this;
    }

    public function setPhoneNumberType(PhoneNumberType $phoneNumberType): self
    {
        $this->phoneNumberType = $phoneNumberType;

        return $this;
    }

    public function setSignal(bool $signal): self
    {
        $this->signal = $signal;

        return $this;
    }

    public function setTelegram(bool $telegram): self
    {
        $this->telegram = $telegram;

        return $this;
    }

    public function setWhatsApp(bool $whatsApp): self
    {
        $this->whatsApp = $whatsApp;

        return $this;
    }
}
