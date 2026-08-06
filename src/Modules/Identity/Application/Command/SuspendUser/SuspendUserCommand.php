<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\SuspendUser;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<User> */
final readonly class SuspendUserCommand implements ICommand
{
    public function __construct(
        public string $userId,
    ) {}
}
