<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav\Dto;

use PSBits\FrontendUsers\Domain\Model\Typo3\FrontendUser;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AuthenticationResult
 *
 * @package PSBits\ContactBook\CardDav\Dto
 */
readonly class AuthenticationResult
{
    public function __construct(
        private ?ResponseInterface $response = null,
        private string             $username = '',
        private ?FrontendUser      $user = null,
    ) {
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getUser(): ?FrontendUser
    {
        return $this->user;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
