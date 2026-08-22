<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Contact Book.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\ContactBook\CardDav\Dto;

use Psr\Http\Message\ResponseInterface;

readonly class AuthenticationResult
{
    /**
     * @param array<string, mixed>|null $user
     */
    public function __construct(
        private ?ResponseInterface $response = null,
        private string $username = '',
        private ?array $user = null,
    ) {
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
