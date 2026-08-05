<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\DisableUser;

use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Transaction\ITransactionManager;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\UserId;

final readonly class DisableUserHandler
{
    public function __construct(
        private IUserRepository $users,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(DisableUserCommand $command): User
    {
        return $this->transaction->run(
            function () use ($command): User {
                $user = $this->users->findByIdForUpdate(
                    new UserId($command->userId),
                );

                if ($user === null) {
                    throw UserNotFound::withId($command->userId);
                }

                $user->disable();
                $this->users->save($user);

                return $user;
            },
        );
    }
}
