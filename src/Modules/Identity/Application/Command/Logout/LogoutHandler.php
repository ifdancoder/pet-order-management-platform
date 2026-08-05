<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\Logout;

use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;

final readonly class LogoutHandler
{
    public function __construct(
        private IRefreshTokenService $refreshTokens,
    ) {}

    public function __invoke(LogoutCommand $command): void
    {
        $this->refreshTokens->revoke($command->refreshToken);
    }
}
