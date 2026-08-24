<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav\Dto;

/**
 * Class CardDavResourceType
 *
 * @package PSBits\ContactBook\CardDav\Dto
 */
enum CardDavResourceType: string
{
    case Card       = 'card';
    case Collection = 'collection';
    case Home       = 'home';
    case Principal  = 'principal';
    case Root       = 'root';
}
