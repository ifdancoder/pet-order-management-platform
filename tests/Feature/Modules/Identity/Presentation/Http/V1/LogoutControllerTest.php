<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;

uses(LazilyRefreshDatabase::class);

it('revokes the refresh token family', function () {
    $user = UserModel::factory()->create();
    $refreshToken = app(IRefreshTokenService::class)->issue(
        new UserId((string) $user->getKey()),
    );

    $this->postJson(route('identity.auth.logout'), [
        'refresh_token' => $refreshToken->token,
    ])->assertNoContent();

    expect(RefreshTokenModel::query()->whereNotNull('revoked_at')->count())
        ->toBe(1);
});

it('keeps logout idempotent when the token is unknown', function () {
    $this->postJson(route('identity.auth.logout'), [
        'refresh_token' => 'unknown-refresh-token',
    ])->assertNoContent();
});
