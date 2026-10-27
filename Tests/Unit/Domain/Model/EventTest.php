<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Domain\Model;

use DateTime;
use PSBits\ContactBook\Domain\Enum\EventType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Domain\Model\Event;
use PSBits\Qa\Testing\Support\LanguageStackSeeder;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class EventTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Domain\Model
 */
class EventTest extends UnitTestCase
{
    use LanguageStackSeeder;

    // The language service factory is a pre-seeded singleton, which must not leak between tests.
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The labels come from the extension language file, which the core language stack cannot load in unit tests.
         * The seeder pre-seeds a language service factory that serves them instead.
         */
        $labels = static function (string $key): string {
            $position = strrpos($key, ':');
            $key      = false === $position ? $key : substr($key, $position + 1);

            return match ($key) {
                'birthday'             => 'Birthday',
                'birthday.anniversary' => 'Birthday (anniversary)',
                'death'                => 'Death',
                'other'                => 'Other',
                'wedding'              => 'Wedding',
                default                => $key,
            };
        };
        $this->seedLanguageStack($labels);
    }

    #[Test]
    public function theAnniversaryIsZeroWithoutAYear(): void
    {
        self::assertSame(0, $this->createEvent(null, 5, 1)->getAnniversaryForYear(2026));
    }

    #[Test]
    public function theAnniversaryIsZeroBeforeTheGivenYearIsReached(): void
    {
        self::assertSame(0, $this->createEvent(2026, 5, 1)->getAnniversaryForYear(2026));
    }

    #[Test]
    public function theAnniversaryIsTheDifferenceOfTheYears(): void
    {
        self::assertSame(36, $this->createEvent(1990, 5, 1)->getAnniversaryForYear(2026));
    }

    #[Test]
    public function theDateIsCompleteWhenAllPartsAreGiven(): void
    {
        self::assertTrue($this->createEvent(1990, 5, 1)->isDateComplete());
        self::assertFalse($this->createEvent(1990, null, 1)->isDateComplete());
        self::assertFalse($this->createEvent(1990, 5, null)->isDateComplete());
        self::assertFalse($this->createEvent(null, 5, 1)->isDateComplete());
    }

    #[Test]
    public function theMonthAndDayAreGivenWithoutAYear(): void
    {
        self::assertTrue($this->createEvent(null, 5, 1)->isMonthAndDayGiven());
        self::assertFalse($this->createEvent(1990, 5, null)->isMonthAndDayGiven());
        self::assertFalse($this->createEvent(null, null, 1)->isMonthAndDayGiven());
    }

    #[Test]
    public function theDateIsNullWithoutCompleteInformationAndAFallback(): void
    {
        self::assertNull($this->createEvent(null, 5, 1)->getDate());
    }

    #[Test]
    public function theDateUsesFallbacksWhenIncomplete(): void
    {
        self::assertSame('0000-05-01', $this->createEvent(null, 5, 1)->getDate(true)?->format('Y-m-d'));
    }

    #[Test]
    public function theDateIsFormattedWithAllPartsGiven(): void
    {
        self::assertSame('01.05.1990', $this->createEvent(1990, 5, 1)->getDateFormatted());
    }

    #[Test]
    public function theDateIsFormattedWithoutAYear(): void
    {
        self::assertSame('01.05.', $this->createEvent(null, 5, 1)->getDateFormatted());
    }

    #[Test]
    public function theDateIsFormattedWithoutADay(): void
    {
        self::assertSame('05.1990', $this->createEvent(1990, 5, null)->getDateFormatted());
    }

    #[Test]
    public function theDateIsFormattedWithoutAMonth(): void
    {
        self::assertSame('01.??.1990', $this->createEvent(1990, null, 1)->getDateFormatted());
    }

    #[Test]
    public function theDateIsFormattedAsYearWithoutMonthAndDay(): void
    {
        self::assertSame('1990', $this->createEvent(1990, null, null)->getDateFormatted());
    }

    #[Test]
    public function theDateIsFormattedAsMonthWithoutYearAndDay(): void
    {
        self::assertSame('Jan', $this->createEvent(null, 1, null)->getDateFormatted());
    }

    #[Test]
    public function theDateIsNotFormattedWithoutAnyPart(): void
    {
        self::assertNull($this->createEvent(null, null, null)->getDateFormatted());
    }

    #[Test]
    public function theLabelIsTheTypeLabel(): void
    {
        $event = $this->createEvent((int)date('Y'), 5, 1);
        $event->setType(EventType::Wedding);

        self::assertSame('Wedding', $event->getLabel());
    }

    #[Test]
    public function theLabelIsTheNotesOfOtherEvents(): void
    {
        $event = $this->createEvent(null, 5, 1);
        $event->setType(EventType::Other)
            ->setNotes('Retirement');

        self::assertSame('Retirement', $event->getLabel());
    }

    #[Test]
    public function theLabelIsTheAnniversaryForPastYears(): void
    {
        self::assertSame(
            '36. Birthday (anniversary)',
            $this->createEvent(1990, 5, 1)->getLabel(2026)
        );
    }

    #[Test]
    public function theLabelIsTheAnniversaryLabelForAnniversaries(): void
    {
        self::assertSame('Birthday (anniversary)', $this->createEvent(1990, 5, 1)->getLabel());
    }

    #[Test]
    public function theLabelIsTheRegularLabelWithoutAnniversary(): void
    {
        self::assertSame('Birthday', $this->createEvent((int)date('Y'), 5, 1)->getLabel());
    }

    #[Test]
    public function theEventIsAnAnniversaryWithoutAYear(): void
    {
        self::assertTrue($this->createEvent(null, 5, 1)->isAnniversary());
    }

    #[Test]
    public function theEventIsAnAnniversaryForPastYears(): void
    {
        self::assertTrue($this->createEvent(1990, 5, 1)->isAnniversary());
    }

    #[Test]
    public function theEventIsNotAnAnniversaryInTheYearOfTheEvent(): void
    {
        self::assertFalse($this->createEvent((int)date('Y'), 5, 1)->isAnniversary());
    }

    #[Test]
    public function theNextAnniversaryIsZeroWithoutCompleteInformation(): void
    {
        self::assertSame(0, $this->createEvent(null, 5, 1)->getNextAnniversary());
    }

    #[Test]
    public function theNextAnniversaryOfADistantBirthdayIsCounted(): void
    {
        $anniversary = $this->createEvent(1900, 1, 1)->getNextAnniversary();

        self::assertGreaterThanOrEqual(125, $anniversary);
        self::assertLessThanOrEqual(130, $anniversary);
    }

    #[Test]
    public function theDeceasedStatusOfAContactIsDetected(): void
    {
        $event = $this->createEvent(2020, 5, 1);
        $event->setType(EventType::Death);
        $contact = new Contact();
        $contact->addEvents($event);
        $event->addContact($contact);

        self::assertTrue($event->hasDeceasedContact());
    }

    #[Test]
    public function theDeceasedStatusIsFalseWithoutDeathEvents(): void
    {
        $event = $this->createEvent(1990, 5, 1);
        $contact = new Contact();
        $contact->addEvents($event);
        $event->addContact($contact);

        self::assertFalse($event->hasDeceasedContact());
    }

    #[Test]
    public function theLocationIsCombinedWithCommas(): void
    {
        $event = $this->createEvent(1990, 5, 1);
        $event->setLocation("Stadthalle\nBerlin");

        self::assertSame('Stadthalle, Berlin', $event->getLocation());
    }

    #[Test]
    public function theDatePartsAreReturned(): void
    {
        $event = $this->createEvent(1990, 5, 1);

        self::assertSame(1, $event->getDay());
        self::assertSame(5, $event->getMonth());
        self::assertSame(1990, $event->getYear());
    }

    #[Test]
    public function theEventTypeDefaultsToBirthday(): void
    {
        self::assertSame(EventType::Birthday, (new Event())->getType());
    }

    private function createEvent(?int $year, ?int $month, ?int $day): Event
    {
        $event = new Event();
        $event->setDay($day)
            ->setMonth($month)
            ->setYear($year);

        return $event;
    }
}
