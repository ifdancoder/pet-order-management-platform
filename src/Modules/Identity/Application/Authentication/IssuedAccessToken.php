<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Authentication;

use DateTimeImmutable;

final readonly class IssuedAccessToken
{
    public function __construct(
        public string $token,
        public DateTimeImmutable $expiresAt,
    ) {}
}
