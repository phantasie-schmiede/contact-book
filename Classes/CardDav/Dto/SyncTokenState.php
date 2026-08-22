<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav\Dto;

readonly class SyncTokenState
{
    public function __construct(
        private int $maxContactTimestamp,
        private int $maxTombstoneTimestamp,
    ) {
    }

    public function getMaxContactTimestamp(): int
    {
        return $this->maxContactTimestamp;
    }

    public function getMaxTombstoneTimestamp(): int
    {
        return $this->maxTombstoneTimestamp;
    }
}
