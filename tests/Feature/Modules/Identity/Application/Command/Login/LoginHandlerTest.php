<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Application\Command\Login\LoginCommand;
use Modules\Identity\Application\Command\Login\LoginHandler;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

it('issues a token pair for active user credentials', function () {
    $user = UserModel::factory()->create([
        'email' => 'user@example.com',
        'status' => UserStatus::Active->value,
    ]);
    app()->instance(IAccessTokenService::class, new FakeAccessTokenService);
    $handler = app(LoginHandler::class);

    $tokens = $handler(new LoginCommand(
        email: 'USER@example.com',
        password: 'password',
    ));

    expect($tokens->accessToken->token)
        ->toBe('access-token-'.$user->getKey())
        ->and($tokens->refreshToken->token)->not->toBeEmpty();
    $this->assertDatabaseHas('identity_refresh_tokens', [
        'user_id' => $user->getKey(),
        'revoked_at' => null,
    ]);
});

it('rejects an invalid password without issuing tokens', function () {
    UserModel::factory()->create([
        'email' => 'user@example.com',
        'status' => UserStatus::Active->value,
    ]);
    app()->instance(IAccessTokenService::class, new FakeAccessTokenService);
    $handler = app(LoginHandler::class);

    expect(fn () => $handler(new LoginCommand(
        email: 'user@example.com',
        password: 'wrong-password',
    )))->toThrow(InvalidCredentials::class);
    expect(DB::table('identity_refresh_tokens')->count())->toBe(0);
});

it('rejects a user that is not active', function () {
    UserModel::factory()->create([
        'email' => 'user@example.com',
        'status' => UserStatus::Suspended->value,
    ]);
    app()->instance(IAccessTokenService::class, new FakeAccessTokenService);
    $handler = app(LoginHandler::class);

    $handler(new LoginCommand(
        email: 'user@example.com',
        password: 'password',
    ));
})->throws(InvalidCredentials::class);
