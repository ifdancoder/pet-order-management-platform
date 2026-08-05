<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);
});

it('returns a user resource backed by the domain entity', function () {
    $user = UserModel::factory()->create([
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        'email' => 'user@example.com',
        'password_hash' => 'password-hash',
        'status' => UserStatus::Active->value,
    ]);

    $response = $this->withToken('access-token-'.$user->getKey())
        ->getJson(route('identity.users.show', [
            'userId' => $user->getKey(),
        ]));

    $response->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
                'email' => 'user@example.com',
                'status' => UserStatus::Active->value,
            ],
        ]);
});

it('returns 404 when the user does not exist', function () {
    $authenticatedUser = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);

    $response = $this->withToken('access-token-'.$authenticatedUser->getKey())
        ->getJson(route('identity.users.show', [
            'userId' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        ]));

    $response->assertNotFound()
        ->assertExactJson([
            'error' => [
                'code' => 'user_not_found',
                'message' => 'User was not found.',
            ],
        ]);
});

it('rejects a missing access token', function () {
    $user = UserModel::factory()->create();

    $this->getJson(route('identity.users.show', ['userId' => $user->getKey()]))
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_access_token',
                'message' => 'Access token is invalid.',
            ],
        ]);
});

it('rejects an access token belonging to a suspended user', function () {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Suspended->value,
    ]);

    $this->withToken('access-token-'.$user->getKey())
        ->getJson(route('identity.users.show', ['userId' => $user->getKey()]))
        ->assertUnauthorized();
});
