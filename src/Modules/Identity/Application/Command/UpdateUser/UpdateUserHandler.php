<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\UpdateUser;

use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\UserId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class UpdateUserHandler
{
    public function __construct(
        private IUserRepository $users,
        private IPasswordHasher $passwordHasher,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(UpdateUserCommand $command): User
    {
        return $this->transaction->run(
            function () use ($command): User {
                $user = $this->users->findByIdForUpdate(
                    new UserId($command->userId),
                );

                if ($user === null) {
                    throw UserNotFound::withId($command->userId);
                }

                if ($command->email !== null) {
                    $email = new Email($command->email);

                    if (
                        ! $user->email()->equals($email)
                        && $this->users->existsByEmail($email)
                    ) {
                        throw EmailAlreadyExists::withEmail($email->value());
                    }

                    $user->changeEmail($email);
                }

                if ($command->password !== null) {
                    $user->changePassword(
                        $this->passwordHasher->hash($command->password),
                    );
                }

                $this->users->save($user);

                return $user;
            },
        );
    }
}
