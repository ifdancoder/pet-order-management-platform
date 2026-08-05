<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
