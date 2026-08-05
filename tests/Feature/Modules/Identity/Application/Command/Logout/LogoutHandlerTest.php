<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Command\Logout\LogoutCommand;
use Modules\Identity\Application\Command\Logout\LogoutHandler;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;

uses(LazilyRefreshDatabase::class);

it('revokes the refresh token family', function () {
    $user = UserModel::factory()->create();
    $refreshTokens = app(IRefreshTokenService::class);
    $issuedToken = $refreshTokens->issue(
        new UserId((string) $user->getKey()),
    );
    $handler = app(LogoutHandler::class);

    $handler(new LogoutCommand($issuedToken->token));

    expect(RefreshTokenModel::query()->whereNull('revoked_at')->count())
        ->toBe(0);
});
