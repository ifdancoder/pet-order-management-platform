<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RegisterUser;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<User> */
final readonly class RegisterUserCommand implements ICommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
