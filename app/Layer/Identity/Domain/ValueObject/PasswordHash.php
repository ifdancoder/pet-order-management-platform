<?php

declare(strict_types=1);

namespace App\Layer\Identity\Domain\ValueObject;

use App\Layer\Identity\Domain\Exception\InvalidPasswordHash;
use Stringable;

final readonly class PasswordHash implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ($value === '') {
            throw InvalidPasswordHash::empty();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
