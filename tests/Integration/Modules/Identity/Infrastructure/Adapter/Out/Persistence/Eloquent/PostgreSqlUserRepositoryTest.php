<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\UserMapper;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentUserRepository;

uses(DatabaseMigrations::class);

it('enforces the user status constraint', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('users')->insert([
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        'email' => 'invalid-status@example.com',
        'password_hash' => 'password-hash',
        'status' => 99,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);

it('holds a row lock while loading a user for update', function () {
    $repository = new EloquentUserRepository(new UserMapper);
    $user = new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f17'),
        email: new Email('locked-user@example.com'),
        passwordHash: new PasswordHash('password-hash'),
        status: UserStatus::Active,
    );
    $repository->save($user);

    $primaryConnection = DB::connection();
    $primaryConnection->beginTransaction();

    try {
        expect($repository->findByIdForUpdate($user->id()))->not->toBeNull();

        Config::set(
            'database.connections.pgsql_lock_contender',
            Config::get('database.connections.pgsql'),
        );

        $contender = DB::connection('pgsql_lock_contender');
        $contender->statement("SET lock_timeout TO '250ms'");

        try {
            UserModel::on('pgsql_lock_contender')
                ->whereKey($user->id()->value())
                ->lockForUpdate()
                ->first();

            test()->fail('The competing connection acquired a locked user row.');
        } catch (QueryException $exception) {
            expect((string) $exception->getCode())->toBe('55P03');
        } finally {
            DB::disconnect('pgsql_lock_contender');
        }
    } finally {
        $primaryConnection->rollBack();
    }
});
