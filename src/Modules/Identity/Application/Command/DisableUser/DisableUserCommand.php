<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\DisableUser;

final readonly class DisableUserCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
