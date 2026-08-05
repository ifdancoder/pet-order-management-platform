<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Authentication;

use Modules\Identity\Application\Authentication\IssuedRefreshToken;
use Modules\Identity\Application\Authentication\RotatedRefreshToken;
use Modules\Identity\Domain\ValueObject\UserId;

interface IRefreshTokenService
{
    public function issue(UserId $userId): IssuedRefreshToken;

    public function rotate(string $token): RotatedRefreshToken;

    public function revoke(string $token): void;
}
