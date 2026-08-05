<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Authentication;

use Modules\Identity\Domain\ValueObject\UserId;

final readonly class RotatedRefreshToken
{
    public function __construct(
        public UserId $userId,
        public IssuedRefreshToken $refreshToken,
    ) {}
}
