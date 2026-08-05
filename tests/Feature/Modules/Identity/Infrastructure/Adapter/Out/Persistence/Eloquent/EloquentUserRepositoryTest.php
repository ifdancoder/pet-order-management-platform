<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\UserMapper;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentUserRepository;

uses(LazilyRefreshDatabase::class);

it('persists and reconstitutes a user entity', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $user = new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('password-hash'),
        status: UserStatus::Active,
    );

    $repository->save($user);
    $persistedUser = $repository->findById($user->id());

    expect($persistedUser)->not->toBeNull()
        ->and($persistedUser->id()->equals($user->id()))->toBeTrue()
        ->and($persistedUser->email()->value())->toBe('user@example.com')
        ->and($persistedUser->passwordHash()->value())->toBe('password-hash')
        ->and($persistedUser->status())->toBe(UserStatus::Active);
});

it('enforces email uniqueness in the database', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $repository->save(new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('first-hash'),
        status: UserStatus::Active,
    ));

    $repository->save(new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f17'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('second-hash'),
        status: UserStatus::Active,
    ));
})->throws(EmailAlreadyExists::class);
