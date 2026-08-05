<?php

declare(strict_types=1);

namespace Tests\Fakes\Identity;

use DateTimeImmutable;
use Modules\Identity\Application\Authentication\AccessTokenClaims;
use Modules\Identity\Application\Authentication\IssuedAccessToken;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Entity\User;

final class FakeAccessTokenService implements IAccessTokenService
{
    public function issue(User $user): IssuedAccessToken
    {
        return new IssuedAccessToken(
            token: 'access-token-'.$user->id()->value(),
            expiresAt: new DateTimeImmutable('2030-01-01T00:15:00+00:00'),
        );
    }

    public function verify(string $token): AccessTokenClaims
    {
        $prefix = 'access-token-';

        if (! str_starts_with($token, $prefix)) {
            throw InvalidAccessToken::create();
        }

        return new AccessTokenClaims(
            userId: substr($token, strlen($prefix)),
            tokenId: 'test-token-id',
            expiresAt: new DateTimeImmutable('2030-01-01T00:15:00+00:00'),
        );
    }
}
