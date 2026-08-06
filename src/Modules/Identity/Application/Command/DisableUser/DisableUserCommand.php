<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\DisableUser;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<User> */
final readonly class DisableUserCommand implements ICommand
{
    public function __construct(
        public string $userId,
    ) {}
}
