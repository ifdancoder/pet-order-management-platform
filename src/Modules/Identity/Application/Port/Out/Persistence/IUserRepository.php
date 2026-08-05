<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Persistence;

use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\UserId;

interface IUserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function existsByEmail(Email $email): bool;
}
