<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Application\Command\ActivateUser\ActivateUserCommand;
use Modules\Identity\Application\Command\ActivateUser\ActivateUserHandler;
use Modules\Identity\Application\Command\DisableUser\DisableUserCommand;
use Modules\Identity\Application\Command\DisableUser\DisableUserHandler;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use Modules\Identity\Application\Command\RestoreUser\RestoreUserCommand;
use Modules\Identity\Application\Command\RestoreUser\RestoreUserHandler;
use Modules\Identity\Application\Command\SuspendUser\SuspendUserCommand;
use Modules\Identity\Application\Command\SuspendUser\SuspendUserHandler;
use Modules\Identity\Application\Command\UpdateUser\UpdateUserCommand;
use Modules\Identity\Application\Command\UpdateUser\UpdateUserHandler;
use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

uses(LazilyRefreshDatabase::class);

it('registers a pending user with a hashed password', function () {
    $handler = app(RegisterUserHandler::class);

    $user = $handler(new RegisterUserCommand(
        email: 'user@example.com',
        password: 'plain-password',
    ));

    expect($user->status())->toBe(UserStatus::Pending)
        ->and($user->email()->value())->toBe('user@example.com')
        ->and(Hash::check('plain-password', $user->passwordHash()->value()))->toBeTrue();
    $this->assertDatabaseHas('users', [
        'id' => $user->id()->value(),
        'email' => 'user@example.com',
        'status' => UserStatus::Pending->value,
    ]);
    $this->assertDatabaseHas('outbox_messages', [
        'event_name' => 'user.registered.v1',
        'aggregate_id' => $user->id()->value(),
    ]);
});

it('rolls back registration when its integration event cannot be stored', function (): void {
    app()->bind(IOutboxWriter::class, static fn (): IOutboxWriter => new class implements IOutboxWriter
    {
        public function record(IIntegrationEvent $event): void
        {
            throw new RuntimeException('Outbox persistence failed.');
        }
    });

    expect(fn () => app(RegisterUserHandler::class)(new RegisterUserCommand(
        email: 'user@example.com',
        password: 'plain-password',
    )))->toThrow(RuntimeException::class, 'Outbox persistence failed.');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('outbox_messages', 0);
});

it('rejects a duplicate email during registration', function () {
    UserModel::factory()->create([
        'email' => 'user@example.com',
    ]);
    $handler = app(RegisterUserHandler::class);

    $handler(new RegisterUserCommand(
        email: 'USER@example.com',
        password: 'plain-password',
    ));
})->throws(EmailAlreadyExists::class);

it('updates email and password in one use case', function () {
    $userModel = UserModel::factory()->create([
        'email' => 'before@example.com',
        'status' => UserStatus::Active->value,
    ]);
    $handler = app(UpdateUserHandler::class);

    $user = $handler(new UpdateUserCommand(
        userId: (string) $userModel->getKey(),
        email: 'after@example.com',
        password: 'new-password',
    ));

    expect($user->email()->value())->toBe('after@example.com')
        ->and(Hash::check('new-password', $user->passwordHash()->value()))->toBeTrue();
    $this->assertDatabaseHas('users', [
        'id' => $userModel->getKey(),
        'email' => 'after@example.com',
    ]);
});

it('persists each account lifecycle transition', function () {
    $userModel = UserModel::factory()->create([
        'status' => UserStatus::Pending->value,
    ]);
    $userId = (string) $userModel->getKey();

    $activatedUser = app(ActivateUserHandler::class)(new ActivateUserCommand($userId));
    expect($activatedUser->status())->toBe(UserStatus::Active);

    $suspendedUser = app(SuspendUserHandler::class)(new SuspendUserCommand($userId));
    expect($suspendedUser->status())->toBe(UserStatus::Suspended);

    $restoredUser = app(RestoreUserHandler::class)(new RestoreUserCommand($userId));
    expect($restoredUser->status())->toBe(UserStatus::Active);

    $disabledUser = app(DisableUserHandler::class)(new DisableUserCommand($userId));
    expect($disabledUser->status())->toBe(UserStatus::Disabled);
    $this->assertDatabaseHas('users', [
        'id' => $userId,
        'status' => UserStatus::Disabled->value,
    ]);
});
