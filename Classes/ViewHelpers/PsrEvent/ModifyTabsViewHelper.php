<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\ViewHelpers\PsrEvent;

use PSBits\ContactBook\Domain\Model\Contact;
use PSBits\ContactBook\Event\ModifyTabsEvent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ModifyTabsViewHelper
 *
 * @package PSBits\ContactBook\ViewHelpers\PsrEvent
 */
class ModifyTabsViewHelper extends AbstractViewHelper
{
    public function __construct(
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'contact',
            Contact::class,
            'the reference contact for which the tabs should be modified',
            true
        );
        $this->registerArgument(
            'tabs',
            'array',
            'tabs to be modified by the event',
            true,
            []
        );
    }

    public function render(): array
    {
        $event = new ModifyTabsEvent($this->arguments['contact'], $this->getRequest(), $this->arguments['tabs']);

        return $this->eventDispatcher->dispatch($event)
            ->getTabs();
    }

    private function getRequest(): ?ServerRequestInterface
    {
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }

        return null;
    }
}
