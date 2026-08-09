<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RegisterUser;

use Modules\Identity\Application\Event\UserRegisteredIntegrationEvent;
use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Application\Port\Out\Identity\IUserIdGenerator;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RegisterUserHandler
{
    public function __construct(
        private IUserRepository $users,
        private IPasswordHasher $passwordHasher,
        private IUserIdGenerator $idGenerator,
        private ITransactionManager $transaction,
        private IOutboxWriter $outbox,
    ) {}

    public function __invoke(RegisterUserCommand $command): User
    {
        return $this->transaction->run(
            function () use ($command): User {
                $email = new Email($command->email);

                if ($this->users->existsByEmail($email)) {
                    throw EmailAlreadyExists::withEmail($email->value());
                }

                $user = User::register(
                    id: $this->idGenerator->generate(),
                    email: $email,
                    passwordHash: $this->passwordHasher->hash(
                        $command->password,
                    ),
                );

                $this->users->save($user);
                $this->outbox->record(
                    UserRegisteredIntegrationEvent::fromUser($user),
                );

                return $user;
            },
        );
    }
}
