<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RefreshAccessToken;

use Modules\Identity\Application\Authentication\TokenPair;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<TokenPair> */
final readonly class RefreshAccessTokenCommand implements ICommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
