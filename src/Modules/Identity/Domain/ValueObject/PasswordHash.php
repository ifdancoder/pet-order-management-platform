<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\ValueObject;

use Modules\Identity\Domain\Exception\InvalidPasswordHash;
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
