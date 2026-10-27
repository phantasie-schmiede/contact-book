<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Tests\Unit\Fixtures;

use ArrayIterator;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * A query result that is backed by a plain array of records.
 */
final class ArrayQueryResult extends ArrayIterator implements QueryResultInterface
{
    public function setQuery(QueryInterface $query): void
    {
    }

    public function getQuery(): ?QueryInterface
    {
        return null;
    }

    public function getFirst(): ?object
    {
        $this->rewind();

        return $this->valid() ? $this->current() : null;
    }

    public function toArray(): array
    {
        return $this->getArrayCopy();
    }
}
