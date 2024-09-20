<?php
declare(strict_types=1);

/*
 * This file is part of PSB Contacts.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSB\PsbContacts\Domain\Demand;

/**
 * Class EMailDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
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
