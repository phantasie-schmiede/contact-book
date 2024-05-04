<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Model;

use PSB\PsbFoundation\Attribute\TCA\ColumnType\Input;
use PSB\PsbFoundation\Attribute\TCA\ColumnType\Text;
use PSB\PsbFoundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Address
 *
 * @package PSB\PsbContacts\Domain\Model
 */
#[Ctrl(label        : 'lastName', labelAlt: [
    'firstName',
    'companyName',
],     labelAltForce: true, searchFields: [
    'companyName',
    'firstName',
    'lastName',
])]
class Address extends AbstractEntity
{
    #[Input]
    protected string $city = '';

    #[Input]
    protected string $country = '';

    #[Input]
    protected string $county = '';

    #[Input]
    protected string $houseNumber = '';

    #[Input]
    protected string $street = '';

    #[Input]
    protected string $zipCode = '';
}
