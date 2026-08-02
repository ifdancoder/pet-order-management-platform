<?php

declare(strict_types=1);

namespace App\Layer\Identity\Domain\ValueObject;

use App\Layer\Identity\Domain\Exception\InvalidEmail;
use Stringable;

final readonly class Email implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = mb_strtolower(trim($value));

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::fromValue($value);
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
