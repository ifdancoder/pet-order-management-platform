<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Exception;

use DomainException;

final class ReturnNotEligible extends DomainException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
