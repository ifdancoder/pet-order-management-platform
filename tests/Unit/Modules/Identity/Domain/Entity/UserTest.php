<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\Exception\InvalidUserStatusTransition;
use Modules\Identity\Domain\Exception\UserDisabled;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;

function identityUser(UserStatus $status = UserStatus::Pending): User
{
    return new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('password-hash'),
        status: $status,
    );
}

it('registers a pending user', function () {
    $user = User::register(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('password-hash'),
    );

    expect($user->status())->toBe(UserStatus::Pending);
});

it('moves through the active and suspended lifecycle', function () {
    $user = identityUser();

    $user->activate();
    expect($user->status())->toBe(UserStatus::Active);

    $user->suspend();
    expect($user->status())->toBe(UserStatus::Suspended);

    $user->restore();
    expect($user->status())->toBe(UserStatus::Active);

    $user->disable();
    expect($user->status())->toBe(UserStatus::Disabled);
});

it('rejects suspension while pending', function () {
    identityUser()->suspend();
})->throws(InvalidUserStatusTransition::class);

it('requires restore for a suspended user', function () {
    identityUser(UserStatus::Suspended)->activate();
})->throws(InvalidUserStatusTransition::class);

it('rejects disabling a pending user', function () {
    identityUser()->disable();
})->throws(InvalidUserStatusTransition::class);

it('prevents account changes after disabling', function () {
    $user = identityUser(UserStatus::Disabled);

    $user->changeEmail(new Email('changed@example.com'));
})->throws(UserDisabled::class);

it('changes password as user behavior', function () {
    $user = identityUser(UserStatus::Active);

    $user->changePassword(new PasswordHash('new-password-hash'));

    expect($user->passwordHash()->value())->toBe('new-password-hash');
});
