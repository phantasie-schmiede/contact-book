<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Utility;

use PSBits\ContactBook\Domain\Enum\ContactType;
use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Utility\ContactUtility;
use PSBits\Qa\Testing\Support\LanguageStackSeeder;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ContactUtilityTest
 *
 * @package PSBits\ContactBook\Tests\Unit\Utility
 */
class ContactUtilityTest extends UnitTestCase
{
    use LanguageStackSeeder;

    // The language service factory is a pre-seeded singleton, which must not leak between tests.
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The enumeration separators come from the frontend templates language file, which the core language stack
         * cannot load in unit tests. The seeder pre-seeds a language service factory that serves them instead.
         */
        $labels = static function (string $key): string {
            $position = strrpos($key, ':');
            $key      = false === $position ? $key : substr($key, $position + 1);

            return match ($key) {
                'conjunction' => 'and',
                'separator'   => ', ',
                default       => $key,
            };
        };
        $this->seedLanguageStack($labels);
    }

    #[Test]
    public function theEnumerationIsCombinedWithAConjunction(): void
    {
        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Moritz', 'Mustermann'),
            $this->createContact('Anna', 'Auer'),
        ];

        self::assertSame('Max and Moritz Mustermann and Anna Auer', ContactUtility::createEnumeration($contacts));
    }

    #[Test]
    public function theSingleContactIsReturnedAsIs(): void
    {
        $contacts = [
            $this->createContact('Anna', 'Auer'),
        ];

        self::assertSame('Anna Auer', ContactUtility::createEnumeration($contacts));
    }

    #[Test]
    public function theContactsAreLinkedWithTheUriBuilder(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('reset')
            ->willReturnSelf();
        $uriBuilder->method('uriFor')
            ->willReturn('https://example.com/contact');

        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Moritz', 'Mustermann'),
        ];

        self::assertSame(
            '<a class="text-decoration-none" href="https://example.com/contact">Max</a> and '
            . '<a class="text-decoration-none" href="https://example.com/contact">Moritz Mustermann</a>',
            ContactUtility::createEnumeration($contacts, $uriBuilder)
        );
    }

    #[Test]
    public function theContactsAreSortedByTheirListName(): void
    {
        $aerger = $this->createContact('Heinz', 'Ärger');
        $becker = $this->createContact('Erika', 'Becker');
        $mueller = $this->createContact('Fritz', 'Müller');

        self::assertSame([$aerger, $becker, $mueller], ContactUtility::sortByListName($mueller, $becker, $aerger));
    }

    private function createContact(string $firstName, string $lastName): Contact
    {
        $contact = new Contact();
        $contact->setFirstName($firstName)
            ->setLastName($lastName)
            ->setType(ContactType::Person);

        return $contact;
    }
}
