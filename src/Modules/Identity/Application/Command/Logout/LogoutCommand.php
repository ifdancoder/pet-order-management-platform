<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\Logout;

use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<null> */
final readonly class LogoutCommand implements ICommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
