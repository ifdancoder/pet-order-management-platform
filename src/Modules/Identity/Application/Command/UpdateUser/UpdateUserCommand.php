<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public ?string $email = null,
        public ?string $password = null,
    ) {}
}
