<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);
});

it('issues an access and refresh token for an active user', function () {
    $user = UserModel::factory()->create([
        'email' => 'active@example.com',
        'status' => UserStatus::Active->value,
    ]);

    $response = $this->postJson(route('identity.auth.login'), [
        'email' => 'active@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.access_token', 'access-token-'.$user->getKey())
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'token_type',
                'access_expires_at',
                'refresh_expires_at',
            ],
        ]);

    expect(RefreshTokenModel::query()->where('user_id', $user->getKey())->count())
        ->toBe(1);
});

it('returns an authentication error for invalid credentials', function () {
    UserModel::factory()->create(['email' => 'active@example.com']);

    $response = $this->postJson(route('identity.auth.login'), [
        'email' => 'active@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_credentials',
                'message' => 'The supplied credentials are invalid.',
            ],
        ]);

    expect(RefreshTokenModel::query()->count())->toBe(0);
});
