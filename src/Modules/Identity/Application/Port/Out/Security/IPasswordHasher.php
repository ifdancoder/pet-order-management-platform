<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Security;

use Modules\Identity\Domain\ValueObject\PasswordHash;

interface IPasswordHasher
{
    public function hash(string $plainPassword): PasswordHash;

    public function verify(
        string $plainPassword,
        PasswordHash $hash,
    ): bool;
}
