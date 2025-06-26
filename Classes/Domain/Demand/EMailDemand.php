<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Domain\Demand;

/**
 * Class EMailDemand
 *
 * @package PSBits\ContactBook\Domain\Demand
 */
class EMailDemand
{
    protected string $eMailAddress = '';

    public function getEMailAddress(): string
    {
        return $this->eMailAddress;
    }

    public function setEMailAddress(string $eMailAddress): self
    {
        $this->eMailAddress = $eMailAddress;

        return $this;
    }
}
