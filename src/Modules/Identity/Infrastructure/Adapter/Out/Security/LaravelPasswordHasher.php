<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Security;

use Illuminate\Contracts\Hashing\Hasher;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Domain\ValueObject\PasswordHash;

final readonly class LaravelPasswordHasher implements IPasswordHasher
{
    public function __construct(
        private Hasher $hasher,
    ) {}

    public function hash(string $plainPassword): PasswordHash
    {
        return new PasswordHash(
            $this->hasher->make($plainPassword),
        );
    }

    public function verify(
        string $plainPassword,
        PasswordHash $hash,
    ): bool {
        return $this->hasher->check(
            $plainPassword,
            $hash->value(),
        );
    }
}
