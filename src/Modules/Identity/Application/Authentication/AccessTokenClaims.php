<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Authentication;

use DateTimeImmutable;

final readonly class AccessTokenClaims
{
    public function __construct(
        public string $userId,
        public string $tokenId,
        public DateTimeImmutable $expiresAt,
    ) {}
}
