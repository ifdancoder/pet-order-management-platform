<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RefreshAccessToken;

final readonly class RefreshAccessTokenCommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
