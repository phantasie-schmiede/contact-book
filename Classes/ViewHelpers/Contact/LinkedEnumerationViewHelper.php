<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\ViewHelpers\Contact;

use InvalidArgumentException;
use JsonException;
use PSBits\ContactBook\Utility\ContactUtility;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class LinkedEnumerationViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
    ) {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'contacts',
            'array',
            'the contacts to be listed',
            true
        );
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function render(): string
    {
        $uriBuilder = null;

        if ($this->renderingContext->hasAttribute(RequestInterface::class)) {
            $this->uriBuilder->setRequest($this->renderingContext->getAttribute(RequestInterface::class));
            $uriBuilder = $this->uriBuilder;
        }

        return ContactUtility::createEnumeration($this->arguments['contacts'], $uriBuilder);
    }
}
