<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;

/**
 * Class CardDavMiddleware
 *
 * @package PSBits\ContactBook\CardDav
 */
readonly class CardDavMiddleware implements MiddlewareInterface
{
    public function __construct(
    ) {
    }

    /**
     * @throws Exception
     * @throws InvalidPasswordHashException
     * @throws InvalidQueryException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $cardDavHandler = GeneralUtility::makeInstance(CardDavHandler::class);
        $response = $cardDavHandler->handle($request);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return $handler->handle($request);
    }
}
