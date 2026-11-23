<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use PSBits\ContactBook\Domain\Enum\GeneralType;
use PSBits\ContactBook\Domain\Enum\PhoneNumberType;
use PSBits\ContactBook\Domain\Model\PhoneNumber;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PhoneNumberTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class PhoneNumberTest extends UnitTestCase
{
    #[Test]
    public function theCompleteNumberCombinesAllParts(): void
    {
        $phoneNumber = $this->createPhoneNumber();

        self::assertSame('+4930123456', $phoneNumber->getCompleteNumber());
    }

    #[Test]
    public function theCompleteNumberIncludesTheExtension(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setExtension('77');

        self::assertSame('+493012345677', $phoneNumber->getCompleteNumber());
    }

    #[Test]
    public function theCompactValueUsesWhitespaceSeparators(): void
    {
        $phoneNumber = $this->createPhoneNumber();

        self::assertSame('+49 30 123456', $phoneNumber->getCompactValue());
    }

    #[Test]
    public function theTelephoneLinkIsGenerated(): void
    {
        $phoneNumber = $this->createPhoneNumber();

        self::assertSame('tel:+4930123456', $phoneNumber->getLink());
    }

    #[Test]
    public function theSignalLinkIsGeneratedForEnabledSignalNumbers(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setSignal(true);

        self::assertSame('https://signal.me/#p=+4930123456', $phoneNumber->getSignalLink());
    }

    #[Test]
    public function theSignalLinkIsNullForDisabledSignalNumbers(): void
    {
        self::assertNull($this->createPhoneNumber()->getSignalLink());
    }

    #[Test]
    public function theTelegramLinkIsGeneratedForEnabledTelegramNumbers(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setTelegram(true);

        self::assertSame('https://t.me/+4930123456', $phoneNumber->getTelegramLink());
    }

    #[Test]
    public function theWhatsAppLinkIsGeneratedForEnabledWhatsAppNumbers(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setWhatsApp(true);

        self::assertSame('https://wa.me/+4930123456', $phoneNumber->getWhatsAppLink());
    }

    #[Test]
    public function mobileNumbersGetAllActionLinks(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setSignal(true)
            ->setTelegram(true)
            ->setWhatsApp(true);

        self::assertSame([
            'signal'         => 'https://signal.me/#p=+4930123456',
            'telegram'       => 'https://t.me/+4930123456',
            'whatsapp'       => 'https://wa.me/+4930123456',
            'chat-left-dots' => 'sms:+4930123456',
            'telephone'      => 'tel:+4930123456',
        ], $phoneNumber->getActionLinks());
    }

    #[Test]
    public function landlineNumbersOnlyGetTheTelephoneActionLink(): void
    {
        $phoneNumber = $this->createPhoneNumber();
        $phoneNumber->setPhoneNumberType(PhoneNumberType::Landline);

        self::assertSame([
            'telephone' => 'tel:+4930123456',
        ], $phoneNumber->getActionLinks());
    }

    #[Test]
    public function theTypesDefaultToPrivateAndMobile(): void
    {
        $phoneNumber = new PhoneNumber();

        self::assertSame(GeneralType::Private, $phoneNumber->getGeneralType());
        self::assertSame(PhoneNumberType::Mobile, $phoneNumber->getPhoneNumberType());
    }

    private function createPhoneNumber(): PhoneNumber
    {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setAreaCode('30')
            ->setConnectionIdentifier('123456')
            ->setInternationalDiallingCode('49');

        return $phoneNumber;
    }
}
