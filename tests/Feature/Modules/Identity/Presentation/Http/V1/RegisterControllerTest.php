<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;

uses(LazilyRefreshDatabase::class);

it('registers a pending user', function () {
    $response = $this->postJson(route('identity.auth.register'), [
        'email' => 'new.user@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'new.user@example.com')
        ->assertJsonPath('data.status', UserStatus::Pending->value)
        ->assertJsonMissingPath('data.password_hash');

    $user = UserModel::query()
        ->where('email', 'new.user@example.com')
        ->sole();

    expect(Hash::check('StrongPassword123!', $user->password_hash))->toBeTrue();
});

it('rejects invalid registration input', function () {
    $response = $this->postJson(route('identity.auth.register'), [
        'email' => 'not-an-email',
        'password' => 'weak',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('returns a conflict for an existing email', function () {
    UserModel::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson(route('identity.auth.register'), [
        'email' => 'existing@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertConflict()
        ->assertExactJson([
            'error' => [
                'code' => 'email_already_exists',
                'message' => 'A user with this email already exists.',
            ],
        ]);
});
