<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\Event;

use PSBits\ContactBook\Domain\Model\Contact;
use Psr\Http\Message\ServerRequestInterface;

use function count;

class ModifyTabsEvent
{
    protected Contact                $contact;
    protected ServerRequestInterface $request;

    /** @var array<int, array<string, mixed>> */
    protected array $tabs = [];

    /**
     * @param array<int, array<string, mixed>> $tabs
     */
    public function __construct(Contact $contact, ServerRequestInterface $request, array $tabs = [])
    {
        $this->contact = $contact;
        $this->request = $request;
        $this->tabs    = $tabs;
    }

    public function addTab(
        string $content,
        string $id,
        string $label,
        ?int   $position = null,
    ): static {
        $newTab = [
            'content' => $content,
            'id'      => $id,
            'label'   => $label,
        ];

        if (null !== $position && count($this->tabs) > $position) {
            array_splice($this->tabs, $position, 0, [$newTab]);

            return $this;
        }

        $this->tabs[] = $newTab;

        return $this;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /** @return array<int, array<string, mixed>> */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /** @param array<int, array<string, mixed>> $tabs */
    public function setTabs(array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }
}
