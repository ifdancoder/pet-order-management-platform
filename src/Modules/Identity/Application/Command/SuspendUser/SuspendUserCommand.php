<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\SuspendUser;

final readonly class SuspendUserCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
