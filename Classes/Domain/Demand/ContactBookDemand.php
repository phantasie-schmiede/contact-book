<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;

/**
 * Class ContactBookDemand
 *
 * @package PSBits\ContactBook\Domain\Demand
 */
class ContactBookDemand
{
    protected ?FrontendUser $owner              = null;
    protected bool          $respectStoragePage = true;

    public function getOwner(): ?FrontendUser
    {
        return $this->owner;
    }

    public function isRespectStoragePage(): bool
    {
        return $this->respectStoragePage;
    }

    public function setOwner(?FrontendUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function setRespectStoragePage(bool $respectStoragePage): static
    {
        $this->respectStoragePage = $respectStoragePage;

        return $this;
    }
}
