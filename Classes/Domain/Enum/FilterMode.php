<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Enum;

/**
 * Class FilterMode
 *
 * @package PSBits\ContactBook\Domain\Enum
 */
enum FilterMode: string
{
    case Categories   = 'categories';
    case ContactGroup = 'contactGroup';
    case Contacts     = 'contacts';
}
