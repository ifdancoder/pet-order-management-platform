<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\SuspendUser;

use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Transaction\ITransactionManager;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\UserId;

final readonly class SuspendUserHandler
{
    public function __construct(
        private IUserRepository $users,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(SuspendUserCommand $command): User
    {
        return $this->transaction->run(
            function () use ($command): User {
                $user = $this->users->findById(
                    new UserId($command->userId),
                );

                if ($user === null) {
                    throw UserNotFound::withId($command->userId);
                }

                $user->suspend();
                $this->users->save($user);

                return $user;
            },
        );
    }
}
