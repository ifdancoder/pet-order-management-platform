<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Authentication;

final readonly class TokenPair
{
    public function __construct(
        public IssuedAccessToken $accessToken,
        public IssuedRefreshToken $refreshToken,
    ) {}
}
