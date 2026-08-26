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
 * Class CardDavResource
 *
 * @package PSBits\ContactBook\CardDav\Dto
 */
readonly class CardDavResource
{
    public function __construct(
        private CardDavResourceType $type,
        private ?string             $uuid = null,
    ) {
    }

    public function getType(): CardDavResourceType
    {
        return $this->type;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }
}
