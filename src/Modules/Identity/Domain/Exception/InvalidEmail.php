<?php

declare(strict_types=1);

namespace src\Layer\Identity\Domain\Exception;

use DomainException;

final class InvalidEmail extends DomainException
{
    public static function fromValue(string $value): self
    {
        return new self(
            sprintf('"%s" is not a valid email address.', $value),
        );
    }
}
