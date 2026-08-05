<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RestoreUser;

final readonly class RestoreUserCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
