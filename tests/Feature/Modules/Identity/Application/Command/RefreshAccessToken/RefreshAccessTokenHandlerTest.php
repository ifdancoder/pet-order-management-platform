<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenCommand;
use Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenHandler;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

it('rotates the refresh token and issues a new access token', function () {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    app()->instance(IAccessTokenService::class, new FakeAccessTokenService);
    $refreshTokens = app(IRefreshTokenService::class);
    $issuedToken = $refreshTokens->issue(
        new UserId((string) $user->getKey()),
    );
    $handler = app(RefreshAccessTokenHandler::class);

    $tokens = $handler(new RefreshAccessTokenCommand($issuedToken->token));

    expect($tokens->accessToken->token)
        ->toBe('access-token-'.$user->getKey())
        ->and($tokens->refreshToken->token)->not->toBe($issuedToken->token);
    expect(RefreshTokenModel::query()->whereNotNull('consumed_at')->count())
        ->toBe(1);
});

it('revokes the rotated family when the user is not active', function () {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Disabled->value,
    ]);
    app()->instance(IAccessTokenService::class, new FakeAccessTokenService);
    $refreshTokens = app(IRefreshTokenService::class);
    $issuedToken = $refreshTokens->issue(
        new UserId((string) $user->getKey()),
    );
    $handler = app(RefreshAccessTokenHandler::class);

    expect(fn () => $handler(
        new RefreshAccessTokenCommand($issuedToken->token),
    ))->toThrow(InvalidCredentials::class);
    expect(RefreshTokenModel::query()->whereNull('revoked_at')->count())
        ->toBe(0);
});
