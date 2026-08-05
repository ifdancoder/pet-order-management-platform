<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Query\GetUser;

use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\UserId;

final readonly class GetUserHandler
{
    public function __construct(
        private IUserRepository $users,
    ) {}

    public function __invoke(GetUserQuery $query): User
    {
        $user = $this->users->findById(
            new UserId($query->userId),
        );

        if ($user === null) {
            throw UserNotFound::withId($query->userId);
        }

        return $user;
    }
}
