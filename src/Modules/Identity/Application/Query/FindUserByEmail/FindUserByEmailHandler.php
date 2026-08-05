<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Query\FindUserByEmail;

use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;

final readonly class FindUserByEmailHandler
{
    public function __construct(
        private IUserRepository $users,
    ) {}

    public function __invoke(FindUserByEmailQuery $query): User
    {
        $email = new Email($query->email);
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            throw UserNotFound::withEmail($email->value());
        }

        return $user;
    }
}
