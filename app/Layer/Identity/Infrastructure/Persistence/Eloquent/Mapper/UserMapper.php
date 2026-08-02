<?php

declare(strict_types=1);

namespace App\Layer\Identity\Infrastructure\Persistence\Eloquent\Mapper;

use App\Layer\Identity\Domain\Entity\User;
use App\Layer\Identity\Domain\Enum\UserStatus;
use App\Layer\Identity\Domain\ValueObject\Email;
use App\Layer\Identity\Domain\ValueObject\PasswordHash;
use App\Layer\Identity\Domain\ValueObject\UserId;
use App\Layer\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        return new User(
            id: new UserId($model->id),
            email: new Email($model->email),
            passwordHash: new PasswordHash($model->password_hash),
            status: UserStatus::from($model->status),
        );
    }

    public function toModel(User $user): UserModel
    {
        $model = new UserModel();

        $this->mapToModel($user, $model);

        return $model;
    }

    public function mapToModel(
        User $user,
        UserModel $model,
    ): void {
        $model->id = $user->id()->value();
        $model->email = $user->email()->value();
        $model->password_hash = $user->passwordHash()->value();
        $model->status = $user->status()->value;
    }
}
