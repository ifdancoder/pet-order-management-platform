<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);
});

it('rotates the refresh token and issues a new access token', function () {
    $user = UserModel::factory()->create(['status' => UserStatus::Active->value]);
    $refreshToken = app(IRefreshTokenService::class)->issue(
        new UserId((string) $user->getKey()),
    );

    $response = $this->postJson(route('identity.auth.refresh'), [
        'refresh_token' => $refreshToken->token,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.access_token', 'access-token-'.$user->getKey())
        ->assertJsonPath('data.token_type', 'Bearer');

    expect($response->json('data.refresh_token'))
        ->toBeString()
        ->not->toBe($refreshToken->token)
        ->and(RefreshTokenModel::query()->whereNotNull('consumed_at')->count())
        ->toBe(1)
        ->and(RefreshTokenModel::query()->count())
        ->toBe(2);
});

it('returns an authentication error for an invalid refresh token', function () {
    $response = $this->postJson(route('identity.auth.refresh'), [
        'refresh_token' => 'invalid-refresh-token',
    ]);

    $response->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_refresh_token',
                'message' => 'Refresh token is invalid.',
            ],
        ]);
});
