<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Hooks;

use PSBits\ContactBook\Hooks\ItemsProcFunc;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ItemsProcFuncTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Hooks
 */
class ItemsProcFuncTest extends UnitTestCase
{
    #[Test]
    public function theEventTypesAreListedWithTheBirthdayFirst(): void
    {
        $params = [];
        ItemsProcFunc::getEventTypes($params);

        self::assertSame([
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.birthday',
                'value' => 'birthday',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.baptism',
                'value' => 'baptism',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.confirmation',
                'value' => 'confirmation',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.death',
                'value' => 'death',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.divorce',
                'value' => 'divorce',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.firstdayatschool',
                'value' => 'firstDayAtSchool',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.other',
                'value' => 'other',
            ],
            [
                'label' => 'LLL:EXT:contact_book/Resources/Private/Language/Backend/Configuration/TCA/event.xlf:type.wedding',
                'value' => 'wedding',
            ],
        ], $params['items']);
    }
}
