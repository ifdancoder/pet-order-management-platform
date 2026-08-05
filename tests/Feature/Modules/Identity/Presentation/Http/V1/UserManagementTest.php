<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);

    $this->authenticatedUser = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
});

it('updates email and password and returns a user resource', function () {
    $user = UserModel::factory()->create([
        'email' => 'before@example.com',
        'status' => UserStatus::Active->value,
    ]);

    $response = $this->withToken('access-token-'.$this->authenticatedUser->getKey())
        ->patchJson(route('identity.users.update', ['userId' => $user->getKey()]), [
            'email' => 'after@example.com',
            'password' => 'NewStrongPassword123!',
        ]);

    $response->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $user->getKey(),
                'email' => 'after@example.com',
                'status' => UserStatus::Active->value,
            ],
        ]);

    $user->refresh();

    expect(Hash::check('NewStrongPassword123!', $user->password_hash))->toBeTrue();
});

it('requires at least one editable field', function () {
    $user = UserModel::factory()->create();

    $this->withToken('access-token-'.$this->authenticatedUser->getKey())
        ->patchJson(route('identity.users.update', ['userId' => $user->getKey()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('runs the complete user lifecycle through explicit endpoints', function () {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Pending->value,
    ]);
    $headers = ['Authorization' => 'Bearer access-token-'.$this->authenticatedUser->getKey()];

    $this->postJson(
        route('identity.users.activate', ['userId' => $user->getKey()]),
        headers: $headers,
    )->assertOk()->assertJsonPath('data.status', UserStatus::Active->value);

    $this->postJson(
        route('identity.users.suspend', ['userId' => $user->getKey()]),
        headers: $headers,
    )->assertOk()->assertJsonPath('data.status', UserStatus::Suspended->value);

    $this->postJson(
        route('identity.users.restore', ['userId' => $user->getKey()]),
        headers: $headers,
    )->assertOk()->assertJsonPath('data.status', UserStatus::Active->value);

    $this->deleteJson(
        route('identity.users.disable', ['userId' => $user->getKey()]),
        headers: $headers,
    )->assertOk()->assertJsonPath('data.status', UserStatus::Disabled->value);
});

it('maps invalid lifecycle transitions to a conflict response', function () {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);

    $this->withToken('access-token-'.$this->authenticatedUser->getKey())
        ->postJson(route('identity.users.activate', ['userId' => $user->getKey()]))
        ->assertConflict()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_user_status_transition',
                'message' => 'User is already active.',
            ],
        ]);
});
