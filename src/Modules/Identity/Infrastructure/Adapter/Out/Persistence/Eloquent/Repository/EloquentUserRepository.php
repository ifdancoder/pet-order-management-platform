<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\UserMapper;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;

final readonly class EloquentUserRepository implements IUserRepository
{
    public function __construct(
        private UserMapper $mapper,
    ) {}

    public function save(User $user): void
    {
        $model = UserModel::query()->find(
            $user->id()->value(),
        );

        $model ??= new UserModel;

        $this->mapper->mapToModel($user, $model);

        $model->save();
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::query()->find($id->value());

        return $model === null
            ? null
            : $this->mapper->toDomain($model);
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()
            ->where('email', $email->value())
            ->first();

        return $model === null
            ? null
            : $this->mapper->toDomain($model);
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::query()
            ->where('email', $email->value())
            ->exists();
    }
}
