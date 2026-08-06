<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

use DomainException;

final class CurrencyMismatch extends DomainException
{
    public static function between(string $left, string $right): self
    {
        return new self(sprintf(
            'Cannot combine %s and %s monetary values.',
            $left,
            $right,
        ));
    }
}
