<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\ActivateUser;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<User> */
final readonly class ActivateUserCommand implements ICommand
{
    public function __construct(
        public string $userId,
    ) {}
}
