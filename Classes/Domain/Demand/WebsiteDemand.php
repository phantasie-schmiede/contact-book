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
 * Class WebsiteDemand
 *
 * @package PSB\PsbContacts\Domain\Demand
 */
class WebsiteDemand
{
    protected string $url = '';

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
