<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
