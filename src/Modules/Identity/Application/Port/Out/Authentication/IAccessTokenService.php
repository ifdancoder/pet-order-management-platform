<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Authentication;

use Modules\Identity\Application\Authentication\AccessTokenClaims;
use Modules\Identity\Application\Authentication\IssuedAccessToken;
use Modules\Identity\Domain\Entity\User;

interface IAccessTokenService
{
    public function issue(User $user): IssuedAccessToken;

    public function verify(string $token): AccessTokenClaims;
}
