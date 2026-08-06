<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\UpdateUser;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<User> */
final readonly class UpdateUserCommand implements ICommand
{
    public function __construct(
        public string $userId,
        public ?string $email = null,
        public ?string $password = null,
    ) {}
}
